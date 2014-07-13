<?php
/*
Copyright (c) 2005 - 2009, Till Brehm, projektfarm Gmbh
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


/******************************************
* Begin Form configuration
******************************************/

$tform_def_file = "form/mail_user.tform.php";

/******************************************
* End Form configuration
******************************************/

require_once('../../lib/config.inc.php');
require_once('../../lib/app.inc.php');

//* Check permissions for module
$app->auth->check_module_permissions('mail');

// Loading classes
$app->uses('tpl,tform,tform_actions');
$app->load('tform_actions');

class page_action extends tform_actions {
	
	
	function onShowNew() {
		global $app, $conf;
		
		// we will check only users, not admins
		if($_SESSION["s"]["user"]["typ"] == 'user') {
			if(!$app->tform->checkClientLimit('limit_mailbox')) {
				$app->error($app->tform->wordbook["limit_mailbox_txt"]);
			}
			if(!$app->tform->checkResellerLimit('limit_mailbox')) {
				$app->error('Reseller: '.$app->tform->wordbook["limit_mailbox_txt"]);
			}
		}
		
		parent::onShowNew();
	}
	
	function onShowEnd() {
		global $app, $conf;
		
		$email = $this->dataRecord["email"];
		$email_parts = explode("@",$email);
		$app->tpl->setVar("email_local_part",$email_parts[0]);
		
		// Getting Domains of the user
		// $sql = "SELECT domain, server_id FROM mail_domain WHERE ".$app->tform->getAuthSQL('r').' ORDER BY domain';
		$sql = "SELECT domain, server_id FROM mail_domain WHERE domain NOT IN (SELECT SUBSTR(source,2) FROM mail_forwarding WHERE type = 'aliasdomain') AND ".$app->tform->getAuthSQL('r')." ORDER BY domain";
		$domains = $app->db->queryAllRecords($sql);
		$domain_select = '';
		if(is_array($domains)) {
			foreach( $domains as $domain) {
				$selected = ($domain["domain"] == @$email_parts[1])?'SELECTED':'';
				$domain_select .= "<option value='$domain[domain]' $selected>$domain[domain]</option>\r\n";
			}
		}
		$app->tpl->setVar("email_domain",$domain_select);
		unset($domains);
		unset($domain_select);
		
		// Get the spamfilter policys for the user
		$tmp_user = $app->db->queryOneRecord("SELECT policy_id FROM spamfilter_users WHERE email = '".$this->dataRecord["email"]."'");
		$sql = "SELECT id, policy_name FROM spamfilter_policy WHERE ".$app->tform->getAuthSQL('r');
		$policys = $app->db->queryAllRecords($sql);
		$policy_select = "<option value='0'>".$app->tform->lng("no_policy")."</option>";
		if(is_array($policys)) {
			foreach( $policys as $p) {
				$selected = ($p["id"] == $tmp_user["policy_id"])?'SELECTED':'';
				$policy_select .= "<option value='$p[id]' $selected>$p[policy_name]</option>\r\n";
			}
		}
		$app->tpl->setVar("policy",$policy_select);
		unset($policys);
		unset($policy_select);
		unset($tmp_user);
		
		// Convert quota from Bytes to MB
		if($this->dataRecord["quota"] != -1) $app->tpl->setVar("quota",$this->dataRecord["quota"] / 1024 / 1024);
		
		// Is autoresponder set?
		if ($this->dataRecord['autoresponder'] == 'y') {
			$app->tpl->setVar("ar_active", 'checked="checked"');
		} else {
			$app->tpl->setVar("ar_active", '');
		}
		
    $app->uses('getconf');
    $mail_config = $app->getconf->get_global_config('mail');
		if($mail_config["enable_custom_login"] == "y") {
		    $app->tpl->setVar("enable_custom_login", 1);
		} else {
		    $app->tpl->setVar("enable_custom_login", 0);
		}
		
		parent::onShowEnd();
	}
	
	function onSubmit() {
		global $app, $conf;
		
		// Проверяем принадлежит ли домен пользователю
		if(isset($_POST["email_domain"])) {
			$domain = $app->db->queryOneRecord("SELECT server_id, domain, sys_userid, sys_groupid FROM mail_domain WHERE domain = '".$app->db->quote($_POST["email_domain"])."' AND ".$app->tform->getAuthSQL('r'));
			if($domain["domain"] != $_POST["email_domain"]) $app->tform->errorMessage .= $app->tform->lng("no_domain_perm");
		}
		// Получаем системных пользователей владельцев домена
		$sys=array();
		$sys['group']=$app->db->queryOneRecord('SELECT * FROM `sys_group` WHERE `groupid`=\''.$domain['sys_groupid'].'\'');
		$sys['user']=$app->db->queryOneRecord('SELECT * FROM sys_user WHERE `client_id`=\''.$sys['group']['client_id'].'\'');
		
		// Если это вставка проверяем не пустое ли поле с паролем
		if($this->id == 0 && $_POST["password"] == '') {
			$app->tform->errorMessage .= $app->tform->lng("error_no_pwd")."<br>";
		}
		
		// Проверяем ограничения пользователя если он не админ
		if($_SESSION["s"]["user"]["typ"] != 'admin') { // если пользователь не админ
			// Получаем ограничения по клиенту
			$client_group_id = $_SESSION["s"]["user"]["default_group"];
			$client = $app->db->queryOneRecord("SELECT limit_mailbox, limit_mailquota, username FROM sys_group, client WHERE sys_group.client_id = client.client_id and sys_group.groupid = $client_group_id");
			

			// Проверяем может ли пользователь добавлять другой почтовый ящик
			if($this->id == 0 && $client["limit_mailbox"] >= 0) {
				$tmp = $app->db->queryOneRecord("SELECT count(mailuser_id) as number FROM mail_user WHERE sys_groupid = $client_group_id");
				if($tmp["number"] >= $client["limit_mailbox"]) {
					$app->tform->errorMessage .= $app->tform->lng("limit_mailbox_txt")."<br>";
				}
				unset($tmp);
			}
			
			// Проверяем квоту и применяем
			if(isset($_POST["quota"]) && $client["limit_mailquota"] >= 0) {
				$tmp = $app->db->queryOneRecord("SELECT sum(quota) as mailquota FROM mail_user WHERE mailuser_id != ".intval($this->id)." AND ".$app->tform->getAuthSQL('u'));
				$mailquota = $tmp["mailquota"] / 1024 / 1024;
				$new_mailbox_quota = intval($this->dataRecord["quota"]);
				if(($mailquota + $new_mailbox_quota > $client["limit_mailquota"]) || ($new_mailbox_quota == 0 && $client["limit_mailquota"] != -1)) {
					$max_free_quota = $client["limit_mailquota"] - $mailquota;
					$app->tform->errorMessage .= $app->tform->lng("limit_mailquota_txt").": ".$max_free_quota."<br>";
					// Set the quota field to the max free space
					$this->dataRecord["quota"] = $max_free_quota;
				}
				unset($tmp);
				unset($tmp_quota);
			}
		} // end if пользователь не админ
		

    $app->uses('getconf');
    $mail_config = $app->getconf->get_server_config($domain["server_id"],'mail');
		
		//* compose the email field
		if(isset($_POST["email_local_part"]) && isset($_POST["email_domain"])) {
			$this->dataRecord["email"] = strtolower($_POST["email_local_part"]."@".$_POST["email_domain"]);
		
			// Set the server id of the mailbox = server ID of mail domain.
			$this->dataRecord["server_id"] = $domain["server_id"];
		
			unset($this->dataRecord["email_local_part"]);
			unset($this->dataRecord["email_domain"]);
		
			// Convert quota from MB to Bytes
			if($this->dataRecord["quota"] != -1) $this->dataRecord["quota"] = $this->dataRecord["quota"] * 1024 * 1024;
		
			// setting Maildir, Homedir, UID and GID
			// MY CHANGES IN CODE

			//$sys_user_info=$app->system->get_user_attributes($client["username"]);
			
			$maildir = str_replace("[username]",$sys['user']['username'],$mail_config["maildir_path"]);
			$maildir = str_replace("[domain]",$domain["domain"],$maildir);
			$maildir = str_replace("[localpart]",strtolower($_POST["email_local_part"]),$maildir);
						
			$this->dataRecord["maildir"] = $maildir;
			$this->dataRecord["homedir"] = str_replace('[username]', $sys['user']['username'],$mail_config["homedir_path"]);
			$this->dataRecord["uid"] = $mail_config['mailuser_uid'];
			$this->dataRecord["gid"] = $mail_config['mailuser_gid'];
			
			//* Check if there is no alias or forward with this address
			$tmp = $app->db->queryOneRecord("SELECT count(forwarding_id) as number FROM mail_forwarding WHERE active = 'y' AND source = '".$app->db->quote($this->dataRecord["email"])."'");
			if($tmp['number'] > 0) $app->tform->errorMessage .= $app->tform->lng("duplicate_alias_or_forward_txt")."<br>";
			unset($tmp);
			
		}
		
    $sys_config = $app->getconf->get_global_config('mail');
    if($sys_config["enable_custom_login"] == "y") {
        if(!isset($_POST["login"])) $this->dataRecord["login"] = $this->dataRecord["email"];
        elseif(strpos($_POST["login"], '@') !== false && $_POST["login"] != $this->dataRecord["email"]) $app->tform->errorMessage .= $app->tform->lng("error_login_email_txt")."<br>";
		} else {
        $this->dataRecord["login"] = $this->dataRecord["email"];
		}
		//* if autoresponder checkbox not selected, do not save dates
		if (!isset($_POST['autoresponder']) && array_key_exists('autoresponder_start_date', $_POST)) {
			$this->dataRecord['autoresponder_start_date'] = array_map(create_function('$item','return 0;'), $this->dataRecord['autoresponder_start_date']);
			$this->dataRecord['autoresponder_end_date'] = array_map(create_function('$item','return 0;'), $this->dataRecord['autoresponder_end_date']);
		}
		
		parent::onSubmit();
	}
	
	function onAfterInsert() {
		global $app, $conf;
		
		// Set the domain owner as mailbox owner
		$domain = $app->db->queryOneRecord("SELECT sys_groupid, server_id FROM mail_domain WHERE domain = '".$app->db->quote($_POST["email_domain"])."' AND ".$app->tform->getAuthSQL('r'));
		$app->db->query("UPDATE mail_user SET sys_groupid = ".$domain["sys_groupid"]." WHERE mailuser_id = ".$this->id);
		
		//=================================KHEXT INSERT================================
		// Получаем системных пользователей владельцев домена
		$sys=array();
		$sys['group']=$app->db->queryOneRecord('SELECT * FROM `sys_group` WHERE `groupid`=\''.$domain['sys_groupid'].'\'');
		$sys['user']=$app->db->queryOneRecord('SELECT * FROM sys_user WHERE `client_id`=\''.$sys['group']['client_id'].'\'');
			
		//Смотрим каких пользователей и группу нужно прописывать
		$app->uses('getconf');
		$mail_config = $app->getconf->get_server_config($domain["server_id"],'mail');
		$mail_config['mailuser_name']=str_replace('[username]', $sys['user']['username'], $mail_config['mailuser_name']);
			
		// Обновляем системных группу и пользователя
		$app->db->query('UPDATE mail_user SET u_name = \''.$mail_config['mailuser_name'].'\',
				u_group = \''.$mail_config['mailuser_group'].'\'
				WHERE mailuser_id = '.$this->id);
		//=================================KHEXT INSERT================================
		
		
		// Spamfilter policy
		$policy_id = intval($this->dataRecord["policy"]);
		if($policy_id > 0) {
			$tmp_user = $app->db->queryOneRecord("SELECT id FROM spamfilter_users WHERE email = '".mysql_real_escape_string($this->dataRecord["email"])."'");
			if($tmp_user["id"] > 0) {
				// There is already a record that we will update
				$app->db->datalogUpdate('spamfilter_users', "policy_id = $policy_id", 'id', $tmp_user["id"]);
			} else {
				// We create a new record
				$insert_data = "(`sys_userid`, `sys_groupid`, `sys_perm_user`, `sys_perm_group`, `sys_perm_other`, `server_id`, `priority`, `policy_id`, `email`, `fullname`, `local`) 
				        VALUES (".$_SESSION["s"]["user"]["userid"].", ".$domain["sys_groupid"].", 'riud', 'riud', '', ".$domain["server_id"].", 10, ".$policy_id.", '".mysql_real_escape_string($this->dataRecord["email"])."', '".mysql_real_escape_string($this->dataRecord["email"])."', 'Y')";
				$app->db->datalogInsert('spamfilter_users', $insert_data, 'id');
			}
		}  // endif spamfilter policy
		
		
		// Set the fields for dovecot
		if(isset($this->dataRecord["email"])) {
			$disableimap = ($this->dataRecord["disableimap"])?'y':'n';
			$disablepop3 = ($this->dataRecord["disablepop3"])?'y':'n';
			$disabledeliver = ($this->dataRecord["postfix"] == 'y')?'n':'y';
			$disablesmtp = ($this->dataRecord["postfix"] == 'y')?'n':'y';
		
			$sql = "UPDATE mail_user SET disableimap = '$disableimap', disablesieve = '$disableimap', disablepop3 = '$disablepop3', disablesmtp = '$disablesmtp', disabledeliver = '$disabledeliver', disablelda = '$disabledeliver' WHERE mailuser_id = ".$this->id;
			$app->db->query($sql);
		}
	}
	
	function onAfterUpdate() {
		global $app, $conf;
		
		// Set the domain owner as mailbox owner
		if(isset($_POST["email_domain"])) {
			$domain = $app->db->queryOneRecord("SELECT sys_groupid, server_id FROM mail_domain WHERE domain = '".$app->db->quote($_POST["email_domain"])."' AND ".$app->tform->getAuthSQL('r'));
			$app->db->query("UPDATE mail_user SET sys_groupid = ".$domain["sys_groupid"]." WHERE mailuser_id = ".$this->id);

			// Spamfilter policy
			$policy_id = intval($this->dataRecord["policy"]);
			$tmp_user = $app->db->queryOneRecord("SELECT id FROM spamfilter_users WHERE email = '".mysql_real_escape_string($this->dataRecord["email"])."'");
			if($policy_id > 0) {
				if($tmp_user["id"] > 0) {
					// There is already a record that we will update
					$app->db->datalogUpdate('spamfilter_users', "policy_id = $policy_id", 'id', $tmp_user["id"]);
				} else {
					// We create a new record
					$insert_data = "(`sys_userid`, `sys_groupid`, `sys_perm_user`, `sys_perm_group`, `sys_perm_other`, `server_id`, `priority`, `policy_id`, `email`, `fullname`, `local`) 
				        	VALUES (".$_SESSION["s"]["user"]["userid"].", ".$domain["sys_groupid"].", 'riud', 'riud', '', ".$domain["server_id"].", 10, ".$policy_id.", '".mysql_real_escape_string($this->dataRecord["email"])."', '".mysql_real_escape_string($this->dataRecord["email"])."', 'Y')";
					$app->db->datalogInsert('spamfilter_users', $insert_data, 'id');
				}
			}else {
				if($tmp_user["id"] > 0) {
					// There is already a record but the user shall have no policy, so we delete it
					$app->db->datalogDelete('spamfilter_users', 'id', $tmp_user["id"]);
				}
			} // endif spamfilter policy
		}
		
		// Set the fields for dovecot
		if(isset($this->dataRecord["email"])) {
			$disableimap = ($this->dataRecord["disableimap"])?'y':'n';
			$disablepop3 = ($this->dataRecord["disablepop3"])?'y':'n';
			$disabledeliver = ($this->dataRecord["postfix"] == 'y')?'n':'y';
			$disablesmtp = ($this->dataRecord["postfix"] == 'y')?'n':'y';
		
			$sql = "UPDATE mail_user SET disableimap = '$disableimap', disablesieve = '$disableimap', disablepop3 = '$disablepop3', disablesmtp = '$disablesmtp', disabledeliver = '$disabledeliver', disablelda = '$disabledeliver' WHERE mailuser_id = ".$this->id;
			$app->db->query($sql);
		}
		
		
		//=================================KHEXT INSERT================================
		// Получаем системных пользователей владельцев домена
		$sys=array();
		$sys['group']=$app->db->queryOneRecord('SELECT * FROM `sys_group` WHERE `groupid`=\''.$domain['sys_groupid'].'\'');
		$sys['user']=$app->db->queryOneRecord('SELECT * FROM sys_user WHERE `client_id`=\''.$sys['group']['client_id'].'\'');
			
		//Смотрим каких пользователей и группу нужно прописывать
		$app->uses('getconf');
		$mail_config = $app->getconf->get_server_config($domain["server_id"],'mail');
		$mail_config['mailuser_name']=str_replace('[username]', $sys['user']['username'], $mail_config['mailuser_name']);
			
		// Обновляем системных группу и пользователя
		$app->db->query('UPDATE mail_user SET u_name = \''.$mail_config['mailuser_name'].'\', 
											  u_group = \''.$mail_config['mailuser_group'].'\' 
											WHERE mailuser_id = '.$this->id);
		//=================================KHEXT INSERT================================
		
		//** If the email address has been changed, change it in all aliases too
		if(isset($this->dataRecord['email']) && $this->oldDataRecord['email'] != $this->dataRecord['email']) {
		//if($this->oldDataRecord['email'] != $this->dataRecord['email']) {
			
			//* Update the aliases
			$forwardings = $app->db->queryAllRecords("SELECT * FROM mail_forwarding WHERE destination = '".$app->db->quote($this->oldDataRecord['email'])."'");
			if(is_array($forwardings)) {
				foreach($forwardings as $rec) {
					$destination = $app->db->quote($this->dataRecord['email']);
					$app->db->datalogUpdate('mail_forwarding', "destination = '$destination'", 'forwarding_id', $rec['forwarding_id']);
				}
			}
			
		} // end if email addess changed
		
	}
	
}

$app->tform_actions = new page_action;
$app->tform_actions->onLoad();

?>
