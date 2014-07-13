<?php

/*
Copyright (c) 2007, Till Brehm, projektfarm Gmbh
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

class ftpuser_base_plugin {
	
	var $plugin_name = 'ftpuser_base_plugin';
	var $class_name = 'ftpuser_base_plugin';
	
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
	 	This function is called when the plugin is loaded
	*/
	
	function onLoad() {
		global $app;
		
		/*
		Register for the events
		*/
		
		$app->plugins->registerEvent('ftp_user_insert',$this->plugin_name,'insert');
		$app->plugins->registerEvent('ftp_user_update',$this->plugin_name,'update');
		$app->plugins->registerEvent('ftp_user_delete',$this->plugin_name,'delete');
		
	}
	
	
	function insert($event_name,$data) {
		global $app, $conf;
		
		
		//$app->log_array($data['new']);
		$web = $app->db->queryOneRecord("SELECT * FROM web_domain WHERE domain_id = ".intval($data['new']['parent_domain_id']));
		$app->log('Apply uid and gid to FTP user - '.$web['uid'].' '.$web['gid']);
		
		//$app->db->queryOneRecord('UPDATE `ftp_user` SET `uid`='', gid='' WHERE ftp_user_id=$data['new']['ftp_user_id']);
		
		$app->db->queryOneRecord('UPDATE `ftp_user` SET `uid`=\''.$web['uid'].'\', gid=\''.$web['gid'].
								'\' WHERE ftp_user_id=\''.$data['new']['ftp_user_id'].'\'');
		$app->log('UPDATE `ftp_user` SET `uid`=\''.$web['uid'].'\', gid=\''.$web['gid'].
								'\' WHERE ftp_user_id=\''.$data['new']['ftp_user_id'].'\'');
		
		if(!is_dir($data['new']['dir'])) {
			$app->log("FTP User directory '".$data['new']['dir']."' does not exist. Creating it now.",LOGLEVEL_DEBUG);
			
			

			//* Check if the resulting path is inside the docroot
			if(substr(realpath($data['new']['dir']),0,strlen($web['document_root'])) != $web['document_root']) {
				$app->log('User dir is outside of docroot.',LOGLEVEL_WARN);
				return false;
			}
	  
			exec('mkdir -p '.escapeshellcmd($data['new']['dir']));
			exec('chown '.escapeshellcmd($web["system_user"]).':'.escapeshellcmd($web['system_group']).' '.$data['new']['dir']);
      
			$app->log("Added ftpuser_dir: ".$data['new']['dir'],LOGLEVEL_DEBUG);
		}
    
	}
	
	function update($event_name,$data) {
		global $app, $conf;
		
		//$app->log_array($data['new']);
		//$app->log_array($data['old']);
		
		$web = $app->db->queryOneRecord("SELECT * FROM web_domain WHERE domain_id = ".
				intval($data['new']['parent_domain_id']));
		$app->log('Apply uid and gid to FTP user - '.$web['uid'].' '.$web['gid']);
		
		$app->db->queryOneRecord('UPDATE `ftp_user` SET `uid`=\''.$web['uid'].'\', gid=\''.$web['gid'].
				'\' WHERE ftp_user_id=\''.$data['new']['ftp_user_id'].'\'');
		
		$app->log('UPDATE `ftp_user` SET `uid`=\''.$web['uid'].'\', gid=\''.$web['gid'].
				'\' WHERE ftp_user_id=\''.$data['new']['ftp_user_id'].'\'');
		
		if(!is_dir($data['new']['dir'])) {
			$app->log("FTP User directory '".$data['new']['dir']."' does not exist. Creating it now.",LOGLEVEL_DEBUG);

			$web = $app->db->queryOneRecord("SELECT * FROM web_domain WHERE domain_id = ".intval($data['new']['parent_domain_id']));
      
	 		//* Check if the resulting path is inside the docroot
			if(substr(realpath($data['new']['dir']),0,strlen($web['document_root'])) != $web['document_root']) {
				$app->log('User dir is outside of docroot.',LOGLEVEL_WARN);
				return false;
			}
	  
			exec('mkdir -p '.escapeshellcmd($data['new']['dir']));
			exec('chown '.escapeshellcmd($web["system_user"]).':'.escapeshellcmd($web['system_group']).' '.$data['new']['dir']);
      
			$app->log("Added ftpuser_dir: ".$data['new']['dir'],LOGLEVEL_DEBUG);
		}
	}
	
	function delete($event_name,$data) {
		global $app, $conf;
		
		//$app->log_array($data['new']);
		//$app->log_array($data['old']);
		$app->log("Ftpuser:".$data['new']['username']." deleted.",LOGLEVEL_DEBUG);
	}
	
	
	

} // end class

?>