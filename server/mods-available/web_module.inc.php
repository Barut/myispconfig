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

class web_module {
	
	var $module_name = 'web_module';
	var $class_name = 'web_module';
	var $actions_available = array(	'web_domain_insert',
									'web_domain_update',
									'web_domain_delete',
									'ftp_user_insert',
									'ftp_user_update',
									'ftp_user_delete',
									'shell_user_insert',
									'shell_user_update',
									'shell_user_delete',
									'webdav_user_insert',
									'webdav_user_update',
									'webdav_user_delete',
									'web_folder_insert',
									'web_folder_update',
									'web_folder_delete',
									'web_folder_user_insert',
									'web_folder_user_update',
									'web_folder_user_delete');
	
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
	 	This function is called when the module is loaded
	*/
	
	function onLoad() {
		global $app;
		
		/*
		Annonce the actions that where provided by this module, so plugins 
		can register on them.
		*/
		
		$app->plugins->announceEvents($this->module_name,$this->actions_available);
		
		/*
		As we want to get notified of any changes on several database tables,
		we register for them.
		
		The following function registers the function "functionname"
 		to be executed when a record for the table "dbtable" is 
 		processed in the sys_datalog. "classname" is the name of the
 		class that contains the function functionname.
		*/
		
		$app->modules->registerTableHook('web_domain','web_module','process');
		$app->modules->registerTableHook('ftp_user','web_module','process');
		$app->modules->registerTableHook('shell_user','web_module','process');
		$app->modules->registerTableHook('webdav_user','web_module','process');
		$app->modules->registerTableHook('web_folder','web_module','process');
		$app->modules->registerTableHook('web_folder_user','web_module','process');
		
		// Register service
		$app->services->registerService('httpd','web_module','restartHttpd');
		
	}
	
	/*
	 This function is called when a change in one of the registered tables is detected.
	 The function then raises the events for the plugins.
	*/

	function process($tablename,$action,$data) {
		global $app;
		
		switch ($tablename) {
			case 'web_domain':
				if($action == 'i') $app->plugins->raiseEvent('web_domain_insert',$data);
				if($action == 'u') $app->plugins->raiseEvent('web_domain_update',$data);
				if($action == 'd') $app->plugins->raiseEvent('web_domain_delete',$data);
			break;
			case 'ftp_user':
				if($action == 'i') $app->plugins->raiseEvent('ftp_user_insert',$data);
				if($action == 'u') $app->plugins->raiseEvent('ftp_user_update',$data);
				if($action == 'd') $app->plugins->raiseEvent('ftp_user_delete',$data);
			break;
			case 'shell_user':
				if($action == 'i') $app->plugins->raiseEvent('shell_user_insert',$data);
				if($action == 'u') $app->plugins->raiseEvent('shell_user_update',$data);
				if($action == 'd') $app->plugins->raiseEvent('shell_user_delete',$data);
			break;
			case 'webdav_user':
				if($action == 'i') $app->plugins->raiseEvent('webdav_user_insert',$data);
				if($action == 'u') $app->plugins->raiseEvent('webdav_user_update',$data);
				if($action == 'd') $app->plugins->raiseEvent('webdav_user_delete',$data);
			break;
			case 'web_folder':
				if($action == 'i') $app->plugins->raiseEvent('web_folder_insert',$data);
				if($action == 'u') $app->plugins->raiseEvent('web_folder_update',$data);
				if($action == 'd') $app->plugins->raiseEvent('web_folder_delete',$data);
			break;
			case 'web_folder_user':
				if($action == 'i') $app->plugins->raiseEvent('web_folder_user_insert',$data);
				if($action == 'u') $app->plugins->raiseEvent('web_folder_user_update',$data);
				if($action == 'd') $app->plugins->raiseEvent('web_folder_user_delete',$data);
			break;
		} // end switch
	} // end function
	
	
	// This function is used
	function restartHttpd($action = 'restart') {
		global $app,$conf;
		
		// load the server configuration options
		$app->uses('getconf');
		$web_config = $app->getconf->get_server_config($conf['server_id'], 'web');
		
		$daemon = '';
		switch ($web_config['server_type']) {
			case 'nginx':
				$daemon = $web_config['server_type'];
				// Reload PHP-FPM as well
				$restart_second_service['daemon'] = $web_config['php_fpm_init_script'];
				$restart_second_service['action'] = 'reload';
				break;
			default:
				if(is_file($conf['init_scripts'] . '/' . 'httpd')) {
					$daemon = 'httpd';
				} else {
					$daemon = 'apache2';
				}
		}
		
		if($restart_second_service['daemon'] != '' && $restart_second_service['action'] != ''){
			exec($conf['init_scripts'] . '/' . $restart_second_service['daemon'] . ' ' . $restart_second_service['action']);
		}
		
		if($action == 'restart') {
			exec($conf['init_scripts'] . '/' . $daemon . ' restart');
		} else {
			exec($conf['init_scripts'] . '/' . $daemon . ' reload');
		}
		
	}

} // end class

?>
