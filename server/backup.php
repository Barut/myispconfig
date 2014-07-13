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


// Account Backup Tool

class ISPBackupTool {

	private $app;

	private $conf;
	
	private $clients;
	
	private $client;
	
	private $client_info=array();
	

	function __construct() {
		global $app, $conf;
		$this->app=$app;
		$this->conf=$conf;

		// Load required base-classes
		$this->app->uses('ini_parser,file,services,getconf');
	}
////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////	
	function getClients(){
		$this->app->log('Try get clients from Database');
		$this->clients=$this->app->db->queryAllRecords('SELECT * FROM client');
	}
	////////////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////////////	
	function getSysUser(){
		
		$this->app->log('Try get userid for client with client_id - '.
						$this->client['client_id'].' and username -'.$this->client['username']);
		
		$s_user=$this->app->db->queryOneRecord('SELECT userid FROM sys_user WHERE client_id=\''.
												$this->client['client_id'].'\'');
		
		isset($s_user['userid']) ? $this->client_info['userid']=$s_user['userid'] : die('Unable get userid');
	}
	////////////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////////////
	function getSysGroup(){
		$this->app->log('Try get groupid for client with client_id - '.
				$this->client['client_id'].' and username -'.$this->client['username']);
		
		$s_group=$this->app->db->queryOneRecord('SELECT groupid FROM sys_group WHERE client_id=\''.
				$this->client['client_id'].'\'');
		isset($s_group['groupid']) ? $this->client_info['groupid']=$s_group['groupid'] : die('Unable get groupid');
	}
	////////////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////////////
	function getClientWebDomains(){
		$this->app->log('Try get information about web domains for client - '.$this->client['username']);
		$this->app->log('userid - '.$this->client_info['userid'].' groupid - '.$this->client_info['groupid']);
		$domains=$this->app->db->queryAllRecords('SELECT * FROM web_domain WHERE sys_userid=\''.
										$this->client_info['userid'].'\' AND sys_groupid=\''.
										$this->client_info['groupid'].'\' AND type=\'vhost\'');
		//var_dump($domains);
		count($domains)!=0 ? $this->client_info['web']=$domains : $this->app->log('Web domain not found');
	}
	////////////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////////////
	function getClientDatabase()
	{
		$this->app->log('Try get information about databases for client - '.$this->client['username']);
		$this->app->log('userid - '.$this->client_info['userid'].' groupid - '.$this->client_info['groupid']);
		$dbases=$this->app->db->queryAllRecords('SELECT * FROM web_database WHERE sys_userid=\''.
										$this->client_info['userid'].'\' AND sys_groupid=\''.
										$this->client_info['groupid'].'\' AND type=\'mysql\'');
		//var_dump($dbases);
		count($dbases)!=0 ? $this->client_info['db']=$dbases : $this->app->log('Database not found');
	}
	////////////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////////////
	function getClientIMAP(){
		$this->app->log('Try get information about IMAP domains for client - '.$this->client['username']);
		$this->app->log('userid - '.$this->client_info['userid'].' groupid - '.$this->client_info['groupid']);
		$imap=$this->app->db->queryAllRecords('SELECT * FROM mail_domain WHERE sys_userid=\''.
										$this->client_info['userid'].'\' AND sys_groupid=\''.
										$this->client_info['groupid'].'\'');
		count($imap)!=0 ? $this->client_info['imap']=$imap : $this->app->log('IMAP not found');
	}
	////////////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////////////
	function getClientInfo(){
		$this->client_info=array();
		$this->getSysGroup();
		$this->getSysUser();
		$this->getClientWebDomains();
		$this->getClientDatabase();
		$this->getClientIMAP();
	}
	////////////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////////////
	function clientBackupInit(){
		//Здесь будет описание того что нужно сделать во временной директории
		//до начала бекапа
		
		if (mkdir($this->conf['backup']['tmp'].'/'.$this->client['username'])) 
			$this->app->log('We create userdir '.$this->conf['backup']['tmp'].'/'.$this->client['username']);
		
	}
	////////////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////////////
	function clientBackupOver(){
		// Удаляем директорию с файлами бекапа
		if (is_dir($this->conf['backup']['tmp'].'/'.$this->client['username'])){
			$this->_exec('rm -rf '.$this->conf['backup']['tmp'].'/'.$this->client['username']);
		} else {
			$this->app->log('Client backup dir doesn\'t exist');
		}
		// Удаляем файл архива
		if (is_file($this->conf['backup']['tmp'].'/'.$this->client['username'].'.tar.gz')){
			$this->_exec('rm -rf '.$this->conf['backup']['tmp'].'/'.$this->client['username'].'.tar.gz');
		} else {
			$this->app->log('Client backup archive doesn\'t exist');
		}
	}
	////////////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////////////
	function backupWebDomains(){
		$this->app->log('---== Backup Web Domains ==---');
		if (isset($this->client_info['web'])){
			foreach ($this->client_info['web'] as $web_domain){
				$this->app->log('Document Root - '.$web_domain['document_root']);
				$this->app->log('System User - '.$web_domain['system_user']);
				$this->app->log('System Group - '.$web_domain['system_group']);
			
				//print_r($out);
				if (mkdir($this->conf['backup']['tmp'].'/'.$this->client['username'].'/web/'.$web_domain['domain'], 0777,TRUE)){
					foreach ($this->conf['backup']['web_backup_dir'] as $d_dir){
						if (is_dir($web_domain['document_root'].'/'.$d_dir)){
							$this->app->log('Found dir for backup '.$web_domain['document_root'].'/'.$d_dir);
							$this->_exec('cp -r '.$web_domain['document_root'].'/'.$d_dir.' '.
						        $this->conf['backup']['tmp'].'/'.$this->client['username'].'/web/'.$web_domain['domain']);
						}
					}
				}
			}
		}
	}
	////////////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////////////
	function backupImapDomains(){
		$this->app->log('---== Backup IMAP Domains ==---');
		if (isset($this->client_info['imap'])){
			if (mkdir($this->conf['backup']['tmp'].'/'.$this->client['username'].'/web/'.$web_domain['domain'], 0777,TRUE)){
				foreach ($this->client_info['imap'] as $imap_domain){
					$this->app->log('---------');
					if (is_dir('/home/'.$this->client['username'].'/imap/domains/'.$imap_domain['domain'])
							&& $imap_domain['active']=='y'){
						$this->app->log('Found IMAP domain - '.$imap_domain['domain']);
						$this->_exec('cp -r '.'/home/'.$this->client['username'].'/imap/domains/'.$imap_domain['domain'].' '.
								$this->conf['backup']['tmp'].'/'.$this->client['username'].'/imap');
					}
				}	
			}
		}
	}
	////////////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////////////
	function backupDatabases(){
		include SCRIPT_PATH.'/lib/mysql_clientdb.conf';
		$this->app->log('---== Backup Databases ==---');
		if (mkdir($this->conf['backup']['tmp'].'/'.$this->client['username'].'/db/', 0777,TRUE)){
			foreach ($this->client_info['db'] as $dbase){
				$this->app->log('Database - '.$dbase['database_name']);
				$this->app->log('User - '.$dbase['database_user']);
				/*
				$this->_exec('mysqldump -u '.$clientdb_user.' -p'.
						$clientdb_password.' '.$dbase['database_name'].' > '.
						$this->conf['backup']['tmp'].'/'.$this->client['username'].'/db/'.$dbase['database_name'].'.sql');
				*/
				
				$this->_exec('mysqlhotcopy -u '.$clientdb_user.' -p '.$clientdb_password.' '.$dbase['database_name'].
							$this->conf['backup']['tmp'].'/'.$this->client['username'].'/db/');
			}	
		}
	}
	////////////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////////////
	function archiveBackup(){
		$this->app->log('---== Archive all backup files ==---');
		if (is_dir($this->conf['backup']['tmp'].'/'.$this->client['username'])){
			$this->app->log('Try to create archive');
			$this->_exec('tar -czvf '.$this->conf['backup']['tmp'].'/'.$this->client['username'].'.tar.gz'.
						' -C '.$this->conf['backup']['tmp'].' '.$this->client['username']);
		}
	}
	////////////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////////////
	function putToFtp(){
		if (isset($this->conf['backup']['remote_ftp'])){
			$this->app->log('---== Try put files to FTP-server ==---');
			if (is_file($this->conf['backup']['tmp'].'/'.$this->client['username'].'.tar.gz')){
				$this->app->log('Archive file found. Next, try execute ncftpput');
				$this->_exec('ncftpput -u '.$this->conf['backup']['remote_ftp']['user'].
							' -p '.$this->conf['backup']['remote_ftp']['password'].
							' '.$this->conf['backup']['remote_ftp']['host'].' / '.
							$this->conf['backup']['tmp'].'/'.$this->client['username'].'.tar.gz');
			}
		}
	}
	////////////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////////////
	function _exec($command, &$out=''){
		$this->app->log($command);
		exec($command, $out);
	}
	////////////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////////////
	function Start(){
		$this->getClients();
		if (is_array($this->clients)){
			foreach ($this->clients as $this->client){
				$this->getClientInfo();
				$this->clientBackupInit();
				//==========================================
				if (isset($this->client_info['web'])) $this->backupWebDomains();
				if (isset($this->client_info['db'])) $this->backupDatabases();
				if (isset($this->client_info['imap'])) $this->backupImapDomains();
				//==========================================
				$this->archiveBackup();
				$this->putToFtp();
				$this->clientBackupOver();
			}
		}
	}
	////////////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////////////
}



$backup_tool=new ISPBackupTool();
$backup_tool->Start();

?>