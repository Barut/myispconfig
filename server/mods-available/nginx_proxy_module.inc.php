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

class nginx_proxy_module {
	
	var $module_name = 'nginx_proxy_module';
	var $class_name = 'nginx_proxy_module';
	var $actions_available = array(	'nginx_domain_insert',
									'nginx_domain_update',
									'nginx_domain_delete',
									'nginx_folder_insert',
									'nginx_folder_update',
									'nginx_folder_delete',
									'nginx_ip_insert',
									'nginx_ip_update',
									'nginx_ip_delete',
									'nginx_folder_user_insert',
									'nginx_folder_user_update',
									'nginx_folder_user_delete');
	
	//* This function is called during ispconfig installation to determine
	//  if a symlink shall be created for this plugin.
	function onInstall() {
		global $conf;
		
		if($conf['services']['web'] == true) {
			return true;
		} else {
			return false;
		}
		
	}
	
	/*
		Функция вызывается когда загружается модуль
	*/
	
	function onLoad() {
		global $app;
		
		/*

		Анонсирование действий которые воспроизводятся этим модулем, так что плагины
		могут зарегистрировать их.
		*/
		
		$app->plugins->announceEvents($this->module_name,$this->actions_available);
		
		/*

		Как мы хотим получать уведомления о любых изменения в некоторых таблицах базы данных,
		мы регистрируем их.

		Следующая функция регистрирует функцию "functionname"
		которая выполнится когда запись для таблицы "dbtable" в процессе
		обработки в sys_datalog. "classname" название класса который содержит 
		функцию "functionname"
		*/
		
		$app->modules->registerTableHook('web_domain',$this->module_name,'process');
		$app->modules->registerTableHook('web_folder',$this->module_name,'process');
		$app->modules->registerTableHook('web_folder_user',$this->module_name,'process');
		$app->modules->registerTableHook('server_ip', $this->module_name, 'process');
		
		// Register service
		$app->services->registerService('nginx',$this->module_name,'restartNginx');
		
	}
	
	/*
	 Функция вызывается когда обнаружены изменения в какой-либо из зарегистрированных таблиц.
	 В функции вызываются события плагинов. 
	*/

	function process($tablename,$action,$data) {
		global $app;
		
		switch ($tablename) {
			case 'web_domain':
				if($action == 'i') $app->plugins->raiseEvent('nginx_domain_insert',$data);
				if($action == 'u') $app->plugins->raiseEvent('nginx_domain_update',$data);
				if($action == 'd') $app->plugins->raiseEvent('nginx_domain_delete',$data);
			break;
			case 'web_folder':
				if($action == 'i') $app->plugins->raiseEvent('nginx_folder_insert',$data);
				if($action == 'u') $app->plugins->raiseEvent('nginx_folder_update',$data);
				if($action == 'd') $app->plugins->raiseEvent('nginx_folder_delete',$data);
			break;
			case 'web_folder_user':
				if($action == 'i') $app->plugins->raiseEvent('nginx_folder_user_insert',$data);
				if($action == 'u') $app->plugins->raiseEvent('nginx_folder_user_update',$data);
				if($action == 'd') $app->plugins->raiseEvent('nginx_folder_user_delete',$data);
			break;
			case 'server_ip': 
				if($action == 'i') $app->plugins->raiseEvent('nginx_ip_insert',$data);
				if($action == 'u') $app->plugins->raiseEvent('nginx_ip_update',$data);
				if($action == 'd') $app->plugins->raiseEvent('nginx_ip_delete',$data);
			break;
		} // end switch
	} // end function
	
	
	// This function is used
	function restartNginx($action = 'reload') {
		global $app,$conf;
		
		if ($action=='reload'){
			$app->log('Try reload Nginx server');
			exec('service nginx reload');
		}
		if ($action=='restart'){
			$app->log('Try restart Nginx server');
			exec('service nginx reload');
		}
		
		
		
		
	}

} // end class

?>