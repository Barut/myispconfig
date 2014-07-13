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

class mail_plugin {
	
	var $plugin_name = 'mail_plugin';
	var $class_name  = 'mail_plugin';
	
	//* This function is called during ispconfig installation to determine
	//  if a symlink shall be created for this plugin.
	function onInstall() {
		global $conf;
		
		if($conf['services']['mail'] == true) {
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
		
		//* Mailboxes
		$app->plugins->registerEvent('mail_user_insert',$this->plugin_name,'user_insert');
		$app->plugins->registerEvent('mail_user_update',$this->plugin_name,'user_update');
		$app->plugins->registerEvent('mail_user_delete',$this->plugin_name,'user_delete');
		
		//* Mail Domains
		//$app->plugins->registerEvent('mail_domain_insert',$this->plugin_name,'domain_insert');
		//$app->plugins->registerEvent('mail_domain_update',$this->plugin_name,'domain_update');
		$app->plugins->registerEvent('mail_domain_delete',$this->plugin_name,'domain_delete');
		
		//* Mail transports
		$app->plugins->registerEvent('mail_transport_insert',$this->plugin_name,'transport_update');
		$app->plugins->registerEvent('mail_transport_update',$this->plugin_name,'transport_update');
		$app->plugins->registerEvent('mail_transport_delete',$this->plugin_name,'transport_update');
		
	}
	
	
	function user_insert($event_name,$data) {
		global $app, $conf;
		
		// get the config
		$app->uses('getconf,system');
		$mail_config = $app->getconf->get_server_config($conf['server_id'], 'mail');

		// convert to lower case - it could cause problems if some directory above has upper case name
		// $data['new']['maildir'] = strtolower($data['new']['maildir']);
		
		$uid=$app->system->getuid($data['new']['u_name']);
		//$gid=
		$app->dbmaster->query('UPDATE `mail_user` SET `uid`=\''.$uid.'\' WHERE `mailuser_id`=\''.
										$data['new']['mailuser_id'].'\'');
		
		// Создание почтового ящика пользователя
		// нужно поменять владельца и указать реального пользователя
		//		
		$rec=$app->dbmaster->queryOneRecord('SELECT `username` FROM `sys_group`, `sys_user` 
											WHERE `groupid`=\''.$data['new']['sys_groupid'].'\' 
											AND sys_user.client_id=sys_group.client_id');
		$username=$rec['username'];
		
		$mail_config['mailuser_name']=str_replace('[username]', $username, $mail_config['mailuser_name']);
		
		$app->log('Set system user for mail dirs : '.$mail_config['mailuser_name'],LOGLEVEL_DEBUG);
		
		$maildomain_path = $data['new']['maildir'];
		$tmp_basepath = $data['new']['maildir'];
		$tmp_basepath_parts = explode('/',$tmp_basepath);
		unset($tmp_basepath_parts[count($tmp_basepath_parts)-1]);
		$base_path = implode('/',$tmp_basepath_parts);

		// Create the mail domain directory, if it does not exist
		// создаем директорию почтового домена если она еще не существует
		if(!empty($base_path) && !is_dir($base_path)) {
			//exec("su -c 'mkdir -p ".escapeshellcmd($base_path)."' ".$mail_config['mailuser_name']);
			$app->system->mkdirpath($base_path, 0700, $mail_config['mailuser_name'], $mail_config['mailuser_group']);
			$app->log('Created Directory: '.$base_path,LOGLEVEL_DEBUG);
		}
		
		// Dovecot uses a different mail layout with a separate 'Maildir' subdirectory.
		//
		//
		if($mail_config['pop3_imap_daemon'] == 'dovecot') {
			//exec("su -c 'mkdir -p ".escapeshellcmd($maildomain_path)."' ".$mail_config['mailuser_name']);
			$app->system->mkdirpath($maildomain_path, 0700, $mail_config['mailuser_name'], $mail_config['mailuser_group']);
			$app->log('Created Directory: '.$maildomain_path,LOGLEVEL_DEBUG);
			$maildomain_path .= '/Maildir';
		}
	
		// When the mail user dir exists but it is not a valid maildir, remove it
		//
		//
		if(!empty($maildomain_path) && is_dir($maildomain_path) && !is_dir($maildomain_path.'/new') && !is_dir($maildomain_path.'/cur')) {
			exec("su -c 'rm -rf ".escapeshellcmd($data['new']['maildir'])."' vmail");
			$app->log('Removed invalid maildir and rebuild it: '.escapeshellcmd($data['new']['maildir']),LOGLEVEL_WARN);
		}

		// Create the maildir, if it doesn not exist, set permissions, set quota.
		//
		//
		if(!empty($maildomain_path) && !is_dir($maildomain_path)) {

			//exec("su -c 'maildirmake ".escapeshellcmd($maildomain_path)."' ".$mail_config['mailuser_name']);
			$app->system->maildirmake($maildomain_path,$mail_config['mailuser_name']);

			exec('chown -R '.$mail_config['mailuser_name'].':'.$mail_config['mailuser_group'].' '.escapeshellcmd($data['new']['maildir']));
			$app->log('Set ownership on '.escapeshellcmd($data['new']['maildir']),LOGLEVEL_DEBUG);

			// This is to fix the maildrop quota not being rebuilt after the quota is changed.
			if($mail_config['pop3_imap_daemon'] != 'dovecot') {
				exec("su -c 'maildirmake -q ".$data['new']['quota']."S ".escapeshellcmd($maildomain_path)."' ".$mail_config['mailuser_name']); // Avoid maildirmake quota bug, see debian bug #214911
				$app->log('Created Maildir: '."su -c 'maildirmake -q ".$data['new']['quota']."S ".escapeshellcmd($maildomain_path)."' ".$mail_config['mailuser_name'],LOGLEVEL_DEBUG);
			}
		}
		
		if(!is_dir($data['new']['maildir'].'/.Sent')) {
			//exec("su -c 'maildirmake -f Sent ".escapeshellcmd($maildomain_path)."' ".$mail_config['mailuser_name']);
			//$app->log('Created submaildir Sent: '."su -c 'maildirmake -f Sent ".escapeshellcmd($maildomain_path)."' ".$mail_config['mailuser_name'],LOGLEVEL_DEBUG);
			$app->system->maildirmake($maildomain_path,$mail_config['mailuser_name'],'Sent');
		}
		if(!is_dir($data['new']['maildir'].'/.Drafts')) {
			//exec("su -c 'maildirmake -f Drafts ".escapeshellcmd($maildomain_path)."' ".$mail_config['mailuser_name']);
			//$app->log('Created submaildir Drafts: '."su -c 'maildirmake -f Drafts ".escapeshellcmd($maildomain_path)."' ".$mail_config['mailuser_name'],LOGLEVEL_DEBUG);
			$app->system->maildirmake($maildomain_path,$mail_config['mailuser_name'],'Drafts');
		}
		if(!is_dir($data['new']['maildir'].'/.Trash')) {
			//exec("su -c 'maildirmake -f Trash ".escapeshellcmd($maildomain_path)."' ".$mail_config['mailuser_name']);
			//$app->log('Created submaildir Trash: '."su -c 'maildirmake -f Trash ".escapeshellcmd($maildomain_path)."' ".$mail_config['mailuser_name'],LOGLEVEL_DEBUG);
			$app->system->maildirmake($maildomain_path,$mail_config['mailuser_name'],'Trash');
		}
		if(!is_dir($data['new']['maildir'].'/.Junk')) {
			//exec("su -c 'maildirmake -f Junk ".escapeshellcmd($maildomain_path)."' ".$mail_config['mailuser_name']);
			//$app->log('Created submaildir Junk: '."su -c 'maildirmake -f Junk ".escapeshellcmd($maildomain_path)."' ".$mail_config['mailuser_name'],LOGLEVEL_DEBUG);
			$app->system->maildirmake($maildomain_path,$mail_config['mailuser_name'],'Junk');
		}
		
		// Set the maildir quota
		if(is_dir($data['new']['maildir'].'/new') && $mail_config['pop3_imap_daemon'] != 'dovecot') {
			if($data['new']['quota'] > 0) {
				exec("su -c 'maildirmake -q ".$data['new']['quota']."S ".escapeshellcmd($data['new']['maildir'])."' ".$mail_config['mailuser_name']);
				$app->log('Set Maildir quota: '."su -c 'maildirmake -q ".$data['new']['quota']."S ".escapeshellcmd($data['new']['maildir'])."' ".$mail_config['mailuser_name'],LOGLEVEL_DEBUG);
			}
		}
		
		// Send the welcome email message
		if(file_exists($conf['rootpath'].'/conf-custom/mail/welcome_email_'.$conf['language'].'.txt')) {
			$lines = file($conf['rootpath'].'/conf-custom/mail/welcome_email_'.$conf['language'].'.txt');
		} elseif(file_exists($conf['rootpath'].'/conf-custom/mail/welcome_email_en.txt')) {
			$lines = file($conf['rootpath'].'/conf-custom/mail/welcome_email_en.txt');
		} elseif(file_exists($conf['rootpath'].'/conf/mail/welcome_email_'.$conf['language'].'.txt')) {
			$lines = file($conf['rootpath'].'/conf/mail/welcome_email_'.$conf['language'].'.txt');
		} else {
			$lines = file($conf['rootpath'].'/conf/mail/welcome_email_en.txt');
		}
		
		// Get from address
		$parts = explode(':',trim($lines[0]));
		unset($parts[0]);
		$welcome_mail_from  = implode(':',$parts);
		unset($lines[0]);
		
		// Get subject
		$parts = explode(':',trim($lines[1]));
		unset($parts[0]);
		$welcome_mail_subject  = implode(':',$parts);
		unset($lines[1]);
		
		// Get message
		$welcome_mail_message = trim(implode($lines));
		unset($tmp);
		
		$mailHeaders      = "MIME-Version: 1.0" . "\n";
		$mailHeaders     .= "Content-type: text/plain; charset=utf-8" . "\n";
		$mailHeaders     .= "Content-Transfer-Encoding: 8bit" . "\n";
		$mailHeaders     .= "From: $welcome_mail_from" . "\n";
		$mailHeaders     .= "Reply-To: $welcome_mail_from" . "\n";
		$mailTarget       = $data["new"]["email"];
		$mailSubject      = "=?utf-8?B?".base64_encode($welcome_mail_subject)."?=";

		mail($mailTarget, $mailSubject, $welcome_mail_message, $mailHeaders);
		
	}
	
	function user_update($event_name,$data) {
		global $app, $conf;
		
		// get the config
		$app->uses('getconf,system');
		$mail_config = $app->getconf->get_server_config($conf['server_id'], 'mail');

		// convert to lower case - it could cause problems if some directory above has upper case name
		// $data['new']['maildir'] = strtolower($data['new']['maildir']);
		
		// Create the maildir, if it does not exist
		/*
		if(!is_dir($data['new']['maildir'])) {
			mkdir(escapeshellcmd($data['new']['maildir']), 0, true);
			chown(escapeshellcmd($data['new']['maildir']), $mail_config['mailuser_name']);
			chgrp(escapeshellcmd($data['new']['maildir']), $mail_config['mailuser_group']);
			$app->log('Created Maildir: '.$data['new']['maildir'],LOGLEVEL_DEBUG);
		}
		*/
		
		
		$uid=$app->system->getuid($data['new']['u_name']);
		//$gid=
		$app->dbmaster->query('UPDATE `mail_user` SET `uid`=\''.$uid.'\' WHERE `mailuser_id`=\''.
				$data['new']['mailuser_id'].'\'');
		
		/**/
		$rec=$app->dbmaster->queryOneRecord('SELECT `username` FROM `sys_group`, `sys_user`
				WHERE `groupid`=\''.$data['new']['sys_groupid'].'\'
				AND sys_user.client_id=sys_group.client_id');
		$username=$rec['username'];
		$mail_config['mailuser_name']=str_replace('[username]', $username, $mail_config['mailuser_name']);
		$app->log('Set system user for mail dirs : '.$mail_config['mailuser_name'],LOGLEVEL_DEBUG);
		
		
		$maildomain_path = $data['new']['maildir'];
		$tmp_basepath = $data['new']['maildir'];
		$tmp_basepath_parts = explode('/',$tmp_basepath);
		unset($tmp_basepath_parts[count($tmp_basepath_parts)-1]);
		$base_path = implode('/',$tmp_basepath_parts);

		//* Create the mail domain directory, if it does not exist
		if(!empty($base_path) && !is_dir($base_path)) {
			//exec("su -c 'mkdir -p ".escapeshellcmd($base_path)."' ".$mail_config['mailuser_name']);
			$app->system->mkdirpath($base_path, 0700, $mail_config['mailuser_name'], $mail_config['mailuser_group']);
			$app->log('Created Directory: '.$base_path,LOGLEVEL_DEBUG);
		}
		
		// Dovecot uses a different mail layout with a separate 'Maildir' subdirectory.
		if($mail_config['pop3_imap_daemon'] == 'dovecot') {
			$app->system->mkdirpath($maildomain_path, 0700, $mail_config['mailuser_name'], $mail_config['mailuser_group']);
			$app->log('Created Directory: '.$base_path,LOGLEVEL_DEBUG);
			$maildomain_path .= '/Maildir';
		}
		
		//* When the mail user dir exists but it is not a valid maildir, remove it
		if(!empty($maildomain_path) && is_dir($maildomain_path) && !is_dir($maildomain_path.'/new') && !is_dir($maildomain_path.'/cur')) {
			exec("su -c 'rm -rf ".escapeshellcmd($data['new']['maildir'])."' vmail");
			$app->log('Removed invalid maildir and rebuild it: '.escapeshellcmd($data['new']['maildir']),LOGLEVEL_WARN);
		}

		//* Create the maildir, if it doesn not exist, set permissions, set quota.
		if(!empty($maildomain_path) && !is_dir($maildomain_path.'/new')) {
			//exec("su -c 'maildirmake ".escapeshellcmd($maildomain_path)."' ".$mail_config['mailuser_name']);
			//$app->log("Created Maildir "."su -c 'maildirmake ".escapeshellcmd($maildomain_path)."' ".$mail_config['mailuser_name'],LOGLEVEL_DEBUG);
			$app->system->maildirmake($maildomain_path,$mail_config['mailuser_name']);

			exec('chown -R '.$mail_config['mailuser_name'].':'.$mail_config['mailuser_group'].' '.escapeshellcmd($data['new']['maildir']));
			$app->log('Set ownership on '.escapeshellcmd($data['new']['maildir']),LOGLEVEL_DEBUG);
			//* This is to fix the maildrop quota not being rebuilt after the quota is changed.
			if($mail_config['pop3_imap_daemon'] != 'dovecot') {
				if($data['new']['quota'] > 0) {
					exec("su -c 'maildirmake -q ".$data['new']['quota']."S ".escapeshellcmd($maildomain_path)."' ".$mail_config['mailuser_name']); // Avoid maildirmake quota bug, see debian bug #214911
					$app->log('Updated Maildir quota: '."su -c 'maildirmake -q ".$data['new']['quota']."S ".escapeshellcmd($maildomain_path)."' ".$mail_config['mailuser_name'],LOGLEVEL_DEBUG);
				} else {
					if(file_exists($data['new']['maildir'].'/maildirsize')) unlink($data['new']['maildir'].'/maildirsize');
					$app->log('Set Maildir quota to unlimited.',LOGLEVEL_DEBUG);
				}
			}
		}
		
		if(!is_dir($data['new']['maildir'].'/.Sent')) {
			//exec("su -c 'maildirmake -f Sent ".escapeshellcmd($maildomain_path)."' ".$mail_config['mailuser_name']);
			//$app->log('Created submaildir Sent: '."su -c 'maildirmake -f Sent ".escapeshellcmd($maildomain_path)."' ".$mail_config['mailuser_name'],LOGLEVEL_DEBUG);
			$app->system->maildirmake($maildomain_path,$mail_config['mailuser_name'],'Sent');
		}
		if(!is_dir($data['new']['maildir'].'/.Drafts')) {
			//exec("su -c 'maildirmake -f Drafts ".escapeshellcmd($maildomain_path)."' ".$mail_config['mailuser_name']);
			//$app->log('Created submaildir Drafts: '."su -c 'maildirmake -f Drafts ".escapeshellcmd($maildomain_path)."' ".$mail_config['mailuser_name'],LOGLEVEL_DEBUG);
			$app->system->maildirmake($maildomain_path,$mail_config['mailuser_name'],'Drafts');
		}
		if(!is_dir($data['new']['maildir'].'/.Trash')) {
			//exec("su -c 'maildirmake -f Trash ".escapeshellcmd($maildomain_path)."' ".$mail_config['mailuser_name']);
			//$app->log('Created submaildir Trash: '."su -c 'maildirmake -f Trash ".escapeshellcmd($maildomain_path)."' ".$mail_config['mailuser_name'],LOGLEVEL_DEBUG);
			$app->system->maildirmake($maildomain_path,$mail_config['mailuser_name'],'Trash');
		}
		if(!is_dir($data['new']['maildir'].'/.Junk')) {
			//exec("su -c 'maildirmake -f Junk ".escapeshellcmd($maildomain_path)."' ".$mail_config['mailuser_name']);
			//$app->log('Created submaildir Junk: '."su -c 'maildirmake -f Junk ".escapeshellcmd($maildomain_path)."' ".$mail_config['mailuser_name'],LOGLEVEL_DEBUG);
			$app->system->maildirmake($maildomain_path,$mail_config['mailuser_name'],'Junk');
		}
		
		// Move mailbox, if domain has changed and delete old mailbox
		if($data['new']['maildir'] != $data['old']['maildir'] && is_dir($data['old']['maildir'])) {
			if(is_dir($data['new']['maildir'])) {
				exec("rm -fr ".escapeshellcmd($data['new']['maildir']));
				//rmdir($data['new']['maildir']);
			}
			exec('mv -f '.escapeshellcmd($data['old']['maildir']).' '.escapeshellcmd($data['new']['maildir']));
			// exec('mv -f '.escapeshellcmd($data['old']['maildir']).'/* '.escapeshellcmd($data['new']['maildir']));
			// if(is_file($data['old']['maildir'].'.ispconfig_mailsize'))exec('mv -f '.escapeshellcmd($data['old']['maildir']).'.ispconfig_mailsize '.escapeshellcmd($data['new']['maildir']));
			// rmdir($data['old']['maildir']);
			$app->log('Moved Maildir from: '.$data['old']['maildir'].' to '.$data['new']['maildir'],LOGLEVEL_DEBUG);
		}
		//This is to fix the maildrop quota not being rebuilt after the quota is changed.
		// Courier Layout
		if(is_dir($data['new']['maildir'].'/new') && $mail_config['pop3_imap_daemon'] != 'dovecot') {
			if($data['new']['quota'] > 0) {
				exec("su -c 'maildirmake -q ".$data['new']['quota']."S ".escapeshellcmd($data['new']['maildir'])."' ".$mail_config['mailuser_name']);
				$app->log('Updated Maildir quota: '."su -c 'maildirmake -q ".$data['new']['quota']."S ".escapeshellcmd($data['new']['maildir'])."' ".$mail_config['mailuser_name'],LOGLEVEL_DEBUG);
			} else {
				if(file_exists($data['new']['maildir'].'/maildirsize')) unlink($data['new']['maildir'].'/maildirsize');
				$app->log('Set Maildir quota to unlimited.',LOGLEVEL_DEBUG);
			}
		}
	}
	
	function user_delete($event_name,$data) {
		global $app, $conf;
		
		// get the config
		$app->uses("getconf");
		$mail_config = $app->getconf->get_server_config($conf['server_id'], 'mail');
		
		$old_maildir_path = escapeshellcmd($data['old']['maildir']);
		if($old_maildir_path != $mail_config['homedir_path'] && strlen($old_maildir_path) > strlen($mail_config['homedir_path']) && !stristr($old_maildir_path,'..') && !stristr($old_maildir_path,'*') && strlen($old_maildir_path) >= 10) {
			exec('rm -rf '.escapeshellcmd($old_maildir_path));
			$app->log('Deleted the Maildir: '.$data['old']['maildir'],LOGLEVEL_DEBUG);
		} else {
			$app->log('Possible security violation when deleting the maildir: '.$data['old']['maildir'],LOGLEVEL_ERROR);
		}
	}
	
	function domain_delete($event_name,$data) {
		global $app, $conf;
		
		// get the config
		$app->uses("getconf");
		$mail_config = $app->getconf->get_server_config($conf['server_id'], 'mail');
		
		//* Delete maildomain path
		$old_maildomain_path = escapeshellcmd($mail_config['homedir_path'].'/'.$data['old']['domain']);
		if($old_maildomain_path != $mail_config['homedir_path'] && !stristr($old_maildomain_path,'//') && !stristr($old_maildomain_path,'..') && !stristr($old_maildomain_path,'*') && !stristr($old_maildomain_path,'&') && strlen($old_maildomain_path) >= 10) {
			exec('rm -rf '.escapeshellcmd($old_maildomain_path));
			$app->log('Deleted the mail domain directory: '.$old_maildomain_path,LOGLEVEL_DEBUG);
		} else {
			$app->log('Possible security violation when deleting the mail domain directory: '.$old_maildomain_path,LOGLEVEL_ERROR);
		}
		
		//* Delete mailfilter path
		$old_maildomain_path = escapeshellcmd($mail_config['homedir_path'].'/mailfilters/'.$data['old']['domain']);
		if($old_maildomain_path != $mail_config['homedir_path'].'/mailfilters/' && !stristr($old_maildomain_path,'//') && !stristr($old_maildomain_path,'..') && !stristr($old_maildomain_path,'*') && !stristr($old_maildomain_path,'&') && strlen($old_maildomain_path) >= 10) {
			exec('rm -rf '.escapeshellcmd($old_maildomain_path));
			$app->log('Deleted the mail domain mailfilter directory: '.$old_maildomain_path,LOGLEVEL_DEBUG);
		} else {
			$app->log('Possible security violation when deleting the mail domain mailfilter directory: '.$old_maildomain_path,LOGLEVEL_ERROR);
		}
	}
	
	function transport_update($event_name,$data) {
		global $app, $conf;
		
		exec($conf['init_scripts'] . '/' . 'postfix reload &> /dev/null');
		$app->log('Postfix config reloaded ',LOGLEVEL_DEBUG);
		
	}
	
	
	

} // end class

?>
