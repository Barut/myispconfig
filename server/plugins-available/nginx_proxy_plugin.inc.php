<?php

/*
Copyright (c) 2012, Alex Beljnasky
All rights reserved.

Redistribution and use in source and binary forms, with or without modification,
are permitted provided that the following conditions are met:

    * Redistributions of source code must retain the above copyright notice,
      this list of conditions and the following disclaimer.
    * Redistributions in binary form must reproduce the above copyright notice,
      this list of conditions and the following disclaimer in the documentation
      and/or other materials provided with the distribution.
    * Neither the name of ISPConfig nor the names of its contributors
      may be used to endorse or promote products derived from this software without
      specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT,
INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY
OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE,
EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

class nginx_proxy_plugin {

	var $plugin_name = 'nginx_proxy_plugin';
	var $class_name = 'nginx_proxy_plugin';

	// This function is called during ispconfig installation to determine
	// if a symlink shall be created for this plugin.
	function onInstall() {
		global $conf;

		if($conf['services']['web'] == true) {
			return true;
		} else {
			return false;
		}

	}

	//
	//Функция вызывается при загрузке плагина
	//

	function onLoad() {
		global $app;

		//
		//	Регистрация событий
		//
		
		$app->plugins->registerEvent('nginx_domain_insert',$this->plugin_name,'insert');
		$app->plugins->registerEvent('nginx_domain_update',$this->plugin_name,'update');
		$app->plugins->registerEvent('nginx_domain_delete',$this->plugin_name,'delete');

		$app->plugins->registerEvent('nginx_ip_insert',$this->plugin_name,'server_ip');
		$app->plugins->registerEvent('nginx_ip_update',$this->plugin_name,'server_ip');
		$app->plugins->registerEvent('nginx_ip_delete',$this->plugin_name,'server_ip');
		
		$app->plugins->registerEvent('nginx_folder_user_insert',$this->plugin_name,'web_folder_user');
		$app->plugins->registerEvent('nginx_folder_user_update',$this->plugin_name,'web_folder_user');
		$app->plugins->registerEvent('nginx_folder_user_delete',$this->plugin_name,'web_folder_user');
		
		$app->plugins->registerEvent('nginx_folder_update',$this->plugin_name,'web_folder_update');
		$app->plugins->registerEvent('nginx_folder_delete',$this->plugin_name,'web_folder_delete');
				
	}
	
////////////////////////////////////////////////////////////////////////////////////////
///////////// Генерация конифигурационного файла
/////////////
////////////////////////////////////////////////////////////////////////////////////////
	function _generateNginxConfig($domain_id){
		global $app, $conf;
		
		$params=$app->db->queryOneRecord('SELECT * FROM web_domain WHERE domain_id=\''.$domain_id.'\'');
		if ($params['type']=='vhost'){
			$app->log('Generate Nginx VHost');
			$aliases=$this->_getDomainAliases($domain_id);
			$tpl = new tpl();
			$tpl->newTemplate('nginx_proxy_vhost.conf.master');
			if (isset($params['document_root'])) $params['document_root']=$params['document_root'].'/public_html';
			if ($aliases) $params['alias']=implode(' ', $aliases);
			foreach ($params as $key => $data){
				$tpl->setVar($key, $data);
			}			
			$app->log($tpl->grab());
			return $tpl->grab();
		}
		
	}
////////////////////////////////////////////////////////////////////////////////////////
///////////// Получение имени поддомена из имени домена
/////////////
////////////////////////////////////////////////////////////////////////////////////////	
	function _getSubdomainFromDomain($subdomain, $domain=''){
		$sub=FALSE;
		if ($domain!=''){
			$sub=str_replace('.'.$domain, '', $subdomain);
		}
		return $sub;
	}
////////////////////////////////////////////////////////////////////////////////////////
///////////// Генерация конифигурационного файла поддомена
/////////////
////////////////////////////////////////////////////////////////////////////////////////
	function _generateNginxConfigSub($domain_id){
		global $app, $conf;
		
		$app->log('Generate Nginx Subdomain');
	
		$sub=$app->db->queryOneRecord('SELECT * FROM web_domain WHERE domain_id=\''.$domain_id.'\' AND type=\'subdomain\'');
		if (isset($sub['domain']) && isset($sub['parent_domain_id'])){
			$params=$app->db->queryOneRecord('SELECT * FROM web_domain WHERE domain_id=\''.$sub['parent_domain_id'].'\'');
			$sub_name=$this->_getSubdomainFromDomain($sub['domain'], $params['domain']);
			
			$app->log('We find subdomain name - '.$sub_name);
			
			$tpl = new tpl();
			$tpl->newTemplate('nginx_proxy_sub.conf.master');
			
			if (isset($params['document_root']) && $sub_name!='') $params['document_root']=$params['document_root'].'/public_html/'.$sub_name;
			if ($params['is_subdomainwww']==1) $params['alias']='www.'.$sub_name.'.'.$params['domain'];
			$params['sub']=$sub_name;
			foreach ($params as $key => $data){
				$tpl->setVar($key, $data);
			}
			$app->log($tpl->grab());
			return $tpl->grab();
		}
	}
////////////////////////////////////////////////////////////////////////////////////////
///////////// Получение списка всех алиасов домена
/////////////
////////////////////////////////////////////////////////////////////////////////////////
	function _getDomainAliases($domain_id){
		global $app, $conf;
		$result=FALSE;
		
		$app->log('Nginx : Get domain aliases');
		
		$aliases=array();
		//Получаем из базы был ли домен с www
		$alias=$app->db->queryOneRecord('SELECT `domain`,`is_subdomainwww` FROM `web_domain` WHERE `domain_id`=\''.$domain_id.'\'');
		if ($alias['is_subdomainwww']) $aliases[]='www.'.$alias['domain']; 
		// Получаем из базы список всех алиасов привязанных к домену
		$db_aliases=$app->db->queryAllRecords('SELECT `domain`,`is_subdomainwww` FROM `web_domain` WHERE `parent_domain_id`=\''.
				$domain_id.'\' AND type=\'alias\'');
		// Если алиасы есть, то добавляем их в массив
		if (is_array($db_aliases)){
			foreach ($db_aliases as $data){
				$aliases[]=$data['domain'];
				if ($data['is_subdomainwww']==1) $aliases[]='www.'.$data['domain'];
			}
			if (count($aliases)!=0){
				$result=$aliases;
			}
		}
		return $result; 
	}
////////////////////////////////////////////////////////////////////////////////////////
///////////// Удаление файла конфигурации и симлинка
/////////////
////////////////////////////////////////////////////////////////////////////////////////
	function _removeNginxDomain($domain_name){
		global $app, $conf;
		
		if (is_link($conf['nginx_vhost_enable'].'/'.$domain_name.'.conf')){
			$app->log('Try delete symlink file - '.$conf['nginx_vhost_enable'].'/'.$domain_name.'.conf');
			unlink($conf['nginx_vhost_enable'].'/'.$domain_name.'.conf');
		}
		
		if (is_file($conf['nginx_vhost_available'].'/'.$domain_name.'.conf')){
			$app->log('Try delete config file - '.$conf['nginx_vhost_available'].'/'.$domain_name.'.conf');
			unlink($conf['nginx_vhost_available'].'/'.$domain_name.'.conf');
		}
		
	}
////////////////////////////////////////////////////////////////////////////////////////
///////////// Создание файла конфигурации и симлинка
/////////////
////////////////////////////////////////////////////////////////////////////////////////
	function _createNginxDomain($domain_name, $config){
		global $app, $conf;
		
		$app->log('Nginx : Write Nginx domain');
		
		file_put_contents($conf['nginx_vhost_available'].'/'.$domain_name.'.conf', $config);
		if (!is_link($conf['nginx_vhost_enable'].'/'.$domain_name.'.conf')){
			$app->log('Try create symlink');
			symlink($conf['nginx_vhost_available'].'/'.$domain_name.'.conf',
					$conf['nginx_vhost_enable'].'/'.$domain_name.'.conf');
		}
	}
////////////////////////////////////////////////////////////////////////////////////////
///////////// Получаем информацию по домену
/////////////
////////////////////////////////////////////////////////////////////////////////////////	
	function _getDomainInfo($domain_id){
		global $app, $conf;
		
		$app->log('Try get info about domain : '.$domain_id);
		
		$params=$app->db->queryOneRecord('SELECT * FROM web_domain WHERE domain_id=\''.$domain_id.'\'');
		return (count($params)!=0 ? $params : FALSE);
	}
////////////////////////////////////////////////////////////////////////////////////////
///////////// Обработчик события вставки домена(алиаса,поддомена)
/////////////
////////////////////////////////////////////////////////////////////////////////////////
	function insert($event_name, $data) {
		global $app, $conf;
	
		$app->log('Nginx insert Event',LOGLEVEL_DEBUG);
		//$app->log_array($data['new']);
		
		if ($data['new']['type']=='vhost'){
////////////////////////////////////////////////////////////////////////////////////
			$app->log('--- Nginx vhost inserting');
			
			if ($data[$new]['is_subdomainwww']==1) $aliases='www'.$data['new']['domain'];
			$tpl = new tpl();
			$tpl->newTemplate('nginx_proxy_vhost.conf.master');
			$tpl->setVar('ip_address',$data['new']['ip_address']);
			$tpl->setVar('domain',$data['new']['domain']);
			$tpl->setVar('alias', $aliases);
			$tpl->setVar('document_root', $data['new']['document_root'].'/public_html');
			
			
			$app->log($tpl->grab());
			
			file_put_contents($conf['nginx_vhost_available'].'/'.$data['new']['domain'].'.conf', $tpl->grab());
			
			if (!is_link($conf['nginx_vhost_enable'].'/'.$data['new']['domain'].'.conf')){	
				$app->log('Nginx : Try create symlink');
				symlink($conf['nginx_vhost_available'].'/'.$data['new']['domain'].'.conf',
						$conf['nginx_vhost_enable'].'/'.$data['new']['domain'].'.conf');
			}
			
			unset($tpl);
		} elseif ($data['new']['type']=='alias'){
////////////////////////////////////////////////////////////////////////////////////
			$app->log('--- Nginx alias inserting');
			$aliases=array();
			// Получаем главный домен
			$p_domain=$app->db->queryOneRecord('SELECT * FROM `web_domain` WHERE `domain_id`=\''.$data['new']['parent_domain_id'].'\'');
			// Если в домене автоалиас www, добавляем его в массив алиасов
			if ($p_domain['is_subdomainwww']==1) $aliases[]='www.'.$p_domain['domain'];
			// Получаем из базы список всех алиасов привязанных к домену
			$db_aliases=$app->db->queryAllRecords('SELECT `domain`,`is_subdomainwww` FROM `web_domain` WHERE `parent_domain_id`=\''.
												$p_domain['domain_id'].'\' AND type=\'alias\'');
			// Если алиасы есть, то добавляем их в массив
			if (is_array($db_aliases)){
				foreach ($db_aliases as $data){
					$aliases[]=$data['domain'];
					if ($data['is_subdomainwww']==1) $aliases[]='www.'.$data['domain'];
				}
			}
			// Инициализируем шаблон
			$tpl = new tpl();
			$tpl->newTemplate('nginx_proxy_vhost.conf.master');
			// Присваиваем переменные
			$tpl->setVar('ip_address',$p_domain['ip_address']);
			$tpl->setVar('domain',$p_domain['domain']);
			$tpl->setVar('alias', implode(' ', $aliases));
			$tpl->setVar('document_root', $p_domain['document_root'].'/public_html');
			
			// Вывод конфигурации виртуального хоста в лог 	
			$app->log($tpl->grab());
			
			//
			file_put_contents($conf['nginx_vhost_available'].'/'.$p_domain['domain'].'.conf', $tpl->grab());
				
			if (!is_link($conf['nginx_vhost_enable'].'/'.$p_domain['domain'].'.conf')){
				$app->log('Nginx : Try create symlink');
				symlink($conf['nginx_vhost_available'].'/'.$p_domain['domain'].'.conf',
						$conf['nginx_vhost_enable'].'/'.$p_domain['domain'].'.conf');
			}
			
			unset($tpl);
			
		} elseif ($data['new']['type']=='subdomain'){
////////////////////////////////////////////////////////////////////////////////////
			$app->log('--- Nginx subdomain inserting');
			$g_conf=$this->_generateNginxConfigSub($data['new']['domain_id']);
			$this->_createNginxDomain($data['new']['domain'].'.sub', $g_conf);
////////////////////////////////////////////////////////////////////////////////////
		}
		$app->services->restartServiceDelayed('nginx','reload');
	}
////////////////////////////////////////////////////////////////////////////////////////
///////////// Обработчик события обновления домена(алиаса,поддомена)
/////////////
////////////////////////////////////////////////////////////////////////////////////////
	function update($event_name,$data) {
		global $app, $conf;
		
		$app->log('Nginx Update Event');
		//$app->log_array($data['new']);
		//$app->log_array($data['old']);
		
		if ($data['new']['type']=='vhost'){
			$app->log('Update type: VirtualHost');
			$g_conf=$this->_generateNginxConfig($data['new']['domain_id']);
			$this->_createNginxDomain($data['new']['domain'], $g_conf);
			if ($data['new']['domain']!=$data['old']['domain']){
				$this->_removeNginxDomain($data['old']['domain']);
			}
		} elseif ($data['new']['type']=='alias') {
			$app->log('Update type: Alias');
			$app->log('New parent id='.$data['new']['parent_domain_id'].' Old parent id='.$data['old']['parent_domain_id']);
			$params=$this->_getDomainInfo($data['new']['parent_domain_id']);
			if ($params) {
				$g_conf=$this->_generateNginxConfig($params['domain_id']);
				$this->_createNginxDomain($params['domain'], $g_conf);
			}
		} elseif ($data['new']['type']=='subdomain') {
			$app->log('Update type: Subdomain',LOGLEVEL_DEBUG);
			$g_conf=$this->_generateNginxConfigSub($data['new']['domain_id']);
			$this->_createNginxDomain($data['new']['domain'].'.sub', $g_conf);			
			if ($data['new']['parent_domain_id']!=$data['old']['parent_domain_id'] ||
				$data['new']['domain']!=$data['old']['domain']){
				$app->log('Need remove old subdomain');
				$this->_removeNginxDomain($data['old']['domain'].'.sub');
			}
			
			$app->services->restartServiceDelayed('nginx','reload');
		}
	}
////////////////////////////////////////////////////////////////////////////////////////
///////////// Обработчик события удаления домена(алиаса,поддомена)
/////////////
////////////////////////////////////////////////////////////////////////////////////////
	function delete($event_name,$data) {
		global $app, $conf;
		
		$app->log('Nginx Delete Event',LOGLEVEL_DEBUG);
		
		if ($data['old']['type']=='subdomain'){
			$this->_removeNginxDomain($data['old']['domain'].'.sub');
		} else {
			$this->_removeNginxDomain($data['old']['domain']);
		}
		
		$app->services->restartServiceDelayed('nginx','reload');
	}
////////////////////////////////////////////////////////////////////////////////////////
///////////// Обработчик события добавления(удаления,обновления) ip-адреса
/////////////
////////////////////////////////////////////////////////////////////////////////////////
	function server_ip($event_name,$data) {
		global $app, $conf;

		$app->log('Nginx Server IP Event ',LOGLEVEL_DEBUG);
		$app->log('Event Name : '.$event_name,LOGLEVEL_DEBUG);
		
		// load the server configuration options
		$app->uses('getconf');
		$web_config = $app->getconf->get_server_config($conf['server_id'], 'web');

		$app->load('tpl');

		
		$records = $app->db->queryAllRecords('SELECT * FROM server_ip WHERE server_id = '.$conf['server_id']." AND virtualhost = 'y'");
		
		$records_out= array();
		
		if(is_array($records)) {
			foreach($records as $rec) {
				$ip_address = $rec['ip_address'];
				$ports = explode(',',$rec['virtualhost_port']);
				// перебираем порты и засовываем их в массив
				if(is_array($ports)) {
					foreach($ports as $port) {
						$port = intval($port);
						if($port > 0 && $port < 65536 && $ip_address != '') {
							$records_out[] = array('ip_address' => $ip_address, 'port' => $port);
						}
					}
				}
				
				$tpl = new tpl();
				$tpl->newTemplate('nginx_upstream_ispconfig.conf.master');
				// проверяем не нулевой ли массив
				$tpl->setVar('ip', $ip_address);
				if(count($records_out) > 0) {
					// устанавливаем цикл для шаблона и записи которые нужно добавить
					$tpl->setLoop('ip_adresses', $records_out);
				}
				
				//$app->log($tpl->grab());
				
				$app->log('Nginx DEBUG - '.$conf['nginx_upstream_available']);
				$app->log('Nginx : Upstream file for ip '.$ip_address);
				$app->log('Nginx : Try regenerate it');
				file_put_contents($conf['nginx_upstream_available'].'/'.$ip_address.'.conf',
							          $tpl->grab());
				if (!is_link($conf['nginx_upstream_enable'].'/'.$ip_address.'.conf')){
					$app->log('Nginx : Try create symlink');
					symlink($conf['nginx_upstream_available'].'/'.$ip_address.'.conf', 
							$conf['nginx_upstream_enable'].'/'.$ip_address.'.conf');
				}
				
				$records_out=array();
				unset($tpl);
			}
		}
		
		
	}
	
////////////////////////////////////////////////////////////////////////////////////////
///////////// Обработчик события добавления(удаления,обновления) пользователя
/////////////
////////////////////////////////////////////////////////////////////////////////////////	

	function web_folder_user($event_name,$data) {
		global $app, $conf;


		
	}
	
////////////////////////////////////////////////////////////////////////////////////////
///////////// Обработчик события удаления директории
/////////////
////////////////////////////////////////////////////////////////////////////////////////
	
	function web_folder_delete($event_name,$data) {
		global $app, $conf;
		

	}
	
////////////////////////////////////////////////////////////////////////////////////////
///////////// Обработчик события обновления директории
/////////////
////////////////////////////////////////////////////////////////////////////////////////

	function web_folder_update($event_name,$data) {
		global $app, $conf;
				
		
	}
	
////////////////////////////////////////////////////////////////////////////////////////
///////////// Функция для того чтобы дебажить _exec
/////////////
////////////////////////////////////////////////////////////////////////////////////////
	
	private function _exec($command) {
		global $app;
		$app->log('exec: '.$command,LOGLEVEL_DEBUG);
		exec($command);
	}
	

} // end class

?>