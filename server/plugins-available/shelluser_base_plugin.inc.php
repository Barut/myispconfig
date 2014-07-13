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

class shelluser_base_plugin {
	
	var $plugin_name = 'shelluser_base_plugin';
	var $class_name = 'shelluser_base_plugin';
	var $min_uid = 499;
	
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
		
		$app->plugins->registerEvent('shell_user_insert',$this->plugin_name,'insert');
		$app->plugins->registerEvent('shell_user_update',$this->plugin_name,'update');
		$app->plugins->registerEvent('shell_user_delete',$this->plugin_name,'delete');

		
	}
	
	
	function insert($event_name,$data) {
		global $app, $conf;
		
		$app->uses('system');
		
		//* Check if the resulting path is inside the docroot
		$web = $app->db->queryOneRecord("SELECT * FROM web_domain WHERE domain_id = ".intval($data['new']['parent_domain_id']));
		if(substr(realpath($data['new']['dir']),0,strlen($web['document_root'])) != $web['document_root']) {
			$app->log('Directory of the shell user is outside of website docroot.',LOGLEVEL_WARN);
			return false;
		}
		
		if($app->system->is_user($data['new']['puser'])) {
			// Get the UID of the parent user
			$uid = intval($app->system->getuid($data['new']['puser']));
			if($uid > $this->min_uid) {
				$command = 'useradd';
				$command .= ' -d '.escapeshellcmd($data['new']['dir']);
				$command .= ' -g '.escapeshellcmd($data['new']['pgroup']);
				$command .= ' -o '; // non unique
				if($data['new']['password'] != '') $command .= ' -p '.escapeshellcmd($data['new']['password']);
				$command .= ' -s '.escapeshellcmd($data['new']['shell']);
				$command .= ' -u '.escapeshellcmd($uid);
				$command .= ' '.escapeshellcmd($data['new']['username']);
			
				exec($command);
				$app->log("Executed command: ".$command,LOGLEVEL_DEBUG);
				$app->log("Added shelluser: ".$data['new']['username'],LOGLEVEL_DEBUG);
								
				// call the ssh-rsa update function
				$app->uses("getconf");
				$this->data = $data;
				$this->app = $app;
				$this->_setup_ssh_rsa();
				
				//* Create .bash_history file
				touch(escapeshellcmd($data['new']['dir']).'/.bash_history');
				chmod(escapeshellcmd($data['new']['dir']).'/.bash_history', 0755);
				chown(escapeshellcmd($data['new']['dir']).'/.bash_history', escapeshellcmd($data['new']['username']));
				chgrp(escapeshellcmd($data['new']['dir']).'/.bash_history', escapeshellcmd($data['new']['pgroup']));
				
				//* Disable shell user temporarily if we use jailkit
				if($data['new']['chroot'] == 'jailkit') {
					$command = 'usermod -s /bin/false -L '.escapeshellcmd($data['new']['username']);
					exec($command);
					$app->log("Disabling shelluser temporarily: ".$command,LOGLEVEL_DEBUG);
				}
			
			} else {
				$app->log("UID = $uid for shelluser:".$data['new']['username']." not allowed.",LOGLEVEL_ERROR);
			}
		} else {
			$app->log("Skipping insertion of user:".$data['new']['username'].", parent user ".$data['new']['puser']." does not exist.",LOGLEVEL_WARN);
		}
	}
	
	function update($event_name,$data) {
		global $app, $conf;
		
		$app->uses('system');
		
		//* Check if the resulting path is inside the docroot
		$web = $app->db->queryOneRecord("SELECT * FROM web_domain WHERE domain_id = ".intval($data['new']['parent_domain_id']));
		if(substr(realpath($data['new']['dir']),0,strlen($web['document_root'])) != $web['document_root']) {
			$app->log('Directory of the shell user is outside of website docroot.',LOGLEVEL_WARN);
			return false;
		}
		
		if($app->system->is_user($data['new']['puser'])) {
			// Get the UID of the parent user
			$uid = intval($app->system->getuid($data['new']['puser']));
			if($uid > $this->min_uid) {
				// Check if the user that we want to update exists, if not, we insert it
				if($app->system->is_user($data['old']['username'])) {
					$command = 'usermod';
					$command .= ' --home '.escapeshellcmd($data['new']['dir']);
					$command .= ' --gid '.escapeshellcmd($data['new']['pgroup']);
					// $command .= ' --non-unique ';
					$command .= ' --password '.escapeshellcmd($data['new']['password']);
					if($data['new']['chroot'] != 'jailkit') $command .= ' --shell '.escapeshellcmd($data['new']['shell']);
					// $command .= ' --uid '.escapeshellcmd($uid);
					$command .= ' --login '.escapeshellcmd($data['new']['username']);
					$command .= ' '.escapeshellcmd($data['old']['username']);
			
					exec($command);
					$app->log("Executed command: $command ",LOGLEVEL_DEBUG);
					$app->log("Updated shelluser: ".$data['old']['username'],LOGLEVEL_DEBUG);
									
					// call the ssh-rsa update function
					$app->uses("getconf");
					$this->data = $data;
					$this->app = $app;
					$this->_setup_ssh_rsa();
					
					//* Create .bash_history file
					if(!is_file($data['new']['dir']).'/.bash_history') {
						touch(escapeshellcmd($data['new']['dir']).'/.bash_history');
						chmod(escapeshellcmd($data['new']['dir']).'/.bash_history', 0755);
						chown(escapeshellcmd($data['new']['dir']).'/.bash_history',escapeshellcmd($data['new']['username']));
						chgrp(escapeshellcmd($data['new']['dir']).'/.bash_history',escapeshellcmd($data['new']['pgroup']));
					}
					
				} else {
					// The user does not exist, so we insert it now
					$this->insert($event_name,$data);
				}
			} else {
				$app->log("UID = $uid for shelluser:".$data['new']['username']." not allowed.",LOGLEVEL_ERROR);
			}
		} else {
			$app->log("Skipping update for user:".$data['new']['username'].", parent user ".$data['new']['puser']." does not exist.",LOGLEVEL_WARN);
		}
	}
	
	function delete($event_name,$data) {
		global $app, $conf;
		
		$app->uses('system');
		
		if($app->system->is_user($data['old']['username'])) {
			// Get the UID of the user
			$userid = intval($app->system->getuid($data['old']['username']));
			if($userid > $this->min_uid) {
				// We delete only non jailkit users, jailkit users will be deleted by the jailkit plugin.
				if ($data['old']['chroot'] != "jailkit") {
					$command = 'userdel -f';
					$command .= ' '.escapeshellcmd($data['old']['username']);
			
					exec($command);
					$app->log("Deleted shelluser: ".$data['old']['username'],LOGLEVEL_DEBUG);
				}
			
			} else {
				$app->log("UID = $userid for shelluser:".$data['old']['username']." not allowed.",LOGLEVEL_ERROR);
			}
		} else {
			$app->log("User:".$data['new']['username']." does not exist in in /etc/passwd, skipping delete.",LOGLEVEL_WARN);
		}
		
	}
	
	private function _setup_ssh_rsa() {
		global $app;
		$this->app->log("ssh-rsa setup shelluser_base",LOGLEVEL_DEBUG);
		// Get the client ID, username, and the key
		$domain_data = $this->app->db->queryOneRecord('SELECT sys_groupid FROM web_domain WHERE web_domain.domain_id = '.intval($this->data['new']['parent_domain_id']));
		$sys_group_data = $this->app->db->queryOneRecord('SELECT * FROM sys_group WHERE sys_group.groupid = '.intval($domain_data['sys_groupid']));
		$id = intval($sys_group_data['client_id']);
		$username= $sys_group_data['name'];
		$client_data = $this->app->db->queryOneRecord('SELECT * FROM client WHERE client.client_id = '.$id);
		$userkey = $client_data['ssh_rsa'];
		unset($domain_data);
		unset($client_data);
		
		// ssh-rsa authentication variables
		$sshrsa = $this->data['new']['ssh_rsa'];
		$usrdir = escapeshellcmd($this->data['new']['dir']);
		$sshdir = $usrdir.'/.ssh';
		$sshkeys= $usrdir.'/.ssh/authorized_keys';
		
		$app->uses('file');
		$sshrsa = $app->file->unix_nl($sshrsa);
		$sshrsa = $app->file->remove_blank_lines($sshrsa,0);
		
		// If this user has no key yet, generate a pair
		if ($userkey == '' && $id > 0){
			//Generate ssh-rsa-keys
			exec('ssh-keygen -t rsa -C '.$username.'-rsa-key-'.time().' -f /tmp/id_rsa -N ""');
			
			// use the public key that has been generated
			$userkey = file_get_contents('/tmp/id_rsa.pub');
			
			// save keypair in client table
			$this->app->db->query("UPDATE client SET created_at = ".time().", id_rsa = '".file_get_contents('/tmp/id_rsa')."', ssh_rsa = '".$userkey."' WHERE client_id = ".$id);
			
			exec('rm -f /tmp/id_rsa /tmp/id_rsa.pub');
			$this->app->log("ssh-rsa keypair generated for ".$username,LOGLEVEL_DEBUG);
		};

		if (!file_exists($sshkeys)){
			// add root's key
			$app->file->mkdirs($sshdir, '0700');
			if(is_file('/root/.ssh/authorized_keys')) file_put_contents($sshkeys, file_get_contents('/root/.ssh/authorized_keys'));
		
			// Remove duplicate keys
			$existing_keys = @file($sshkeys);
			$new_keys = explode("\n", $userkey);
			$final_keys_arr = @array_merge($existing_keys, $new_keys);
			$new_final_keys_arr = array();
			if(is_array($final_keys_arr) && !empty($final_keys_arr)){
				foreach($final_keys_arr as $key => $val){
					$new_final_keys_arr[$key] = trim($val);
				}
			}
			$final_keys = implode("\n", array_flip(array_flip($new_final_keys_arr)));
			
			// add the user's key
			file_put_contents($sshkeys, $final_keys);
			$app->file->remove_blank_lines($sshkeys);
			$this->app->log("ssh-rsa authorisation keyfile created in ".$sshkeys,LOGLEVEL_DEBUG);
		}
			
		//* Get the keys
		$existing_keys = file($sshkeys);
		$new_keys = explode("\n", $sshrsa);
		$old_keys = explode("\n",$this->data['old']['ssh_rsa']);
			
		//* Remove all old keys
		if(is_array($old_keys)) {
			foreach($old_keys as $key => $val) {
				$k = array_search(trim($val),$existing_keys);
				unset($existing_keys[$k]);
			}
		}
			
		//* merge the remaining keys and the ones fom the ispconfig database.
		if(is_array($new_keys)) {
			$final_keys_arr = array_merge($existing_keys, $new_keys);
		} else {
			$final_keys_arr = $existing_keys;
		}
			
		$new_final_keys_arr = array();
		if(is_array($final_keys_arr) && !empty($final_keys_arr)){
			foreach($final_keys_arr as $key => $val){
				$new_final_keys_arr[$key] = trim($val);
			}
		}
		$final_keys = implode("\n", array_flip(array_flip($new_final_keys_arr)));
			
		// add the custom key 
		file_put_contents($sshkeys, $final_keys);
		$app->file->remove_blank_lines($sshkeys);
		$this->app->log("ssh-rsa key updated in ".$sshkeys,LOGLEVEL_DEBUG);
		
		// set proper file permissions
		exec("chown -R ".escapeshellcmd($this->data['new']['puser']).":".escapeshellcmd($this->data['new']['pgroup'])." ".$sshdir);
		exec("chmod 600 '$sshkeys'");
		
	}
	

} // end class

?>
