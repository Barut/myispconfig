<?php
/*
Copyright (c) 2008, Till Brehm, projektfarm Gmbh
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

$tform_def_file = "form/database.tform.php";

/******************************************
* End Form configuration
******************************************/

require_once('../../lib/config.inc.php');
require_once('../../lib/app.inc.php');
require_once('tools.inc.php');

//* Check permissions for module
$app->auth->check_module_permissions('sites');

// Loading classes
$app->uses('tpl,tform,tform_actions');
$app->load('tform_actions');

class page_action extends tform_actions {

	function onShowNew() {
		global $app, $conf;

		// we will check only users, not admins
		if($_SESSION["s"]["user"]["typ"] == 'user') {
			if(!$app->tform->checkClientLimit('limit_database')) {
				$app->error($app->tform->wordbook["limit_database_txt"]);
			}
			if(!$app->tform->checkResellerLimit('limit_database')) {
				$app->error('Reseller: '.$app->tform->wordbook["limit_database_txt"]);
			}
		}

		parent::onShowNew();
	}

	function onShowEnd() {
		global $app, $conf, $interfaceConf;

		if($_SESSION["s"]["user"]["typ"] != 'admin' && !$app->auth->has_clients($_SESSION['s']['user']['userid'])) {

			// Get the limits of the client
			$client_group_id = $_SESSION["s"]["user"]["default_group"];
			$client = $app->db->queryOneRecord("SELECT default_dbserver FROM sys_group, client WHERE sys_group.client_id = client.client_id and sys_group.groupid = $client_group_id");

			// Set the webserver to the default server of the client
			$tmp = $app->db->queryOneRecord("SELECT server_name FROM server WHERE server_id = $client[default_dbserver]");
			$app->tpl->setVar("server_id","<option value='$client[default_dbserver]'>$tmp[server_name]</option>");
			unset($tmp);

		} elseif ($_SESSION["s"]["user"]["typ"] != 'admin' && $app->auth->has_clients($_SESSION['s']['user']['userid'])) {

			// Get the limits of the client
			$client_group_id = $_SESSION["s"]["user"]["default_group"];
			$client = $app->db->queryOneRecord("SELECT client.client_id, limit_web_domain, default_webserver, contact_name FROM sys_group, client WHERE sys_group.client_id = client.client_id and sys_group.groupid = $client_group_id");
			
			// Set the webserver to the default server of the client
			$tmp = $app->db->queryOneRecord("SELECT server_name FROM server WHERE server_id = $client[default_webserver]");
			$app->tpl->setVar("server_id","<option value='$client[default_webserver]'>$tmp[server_name]</option>");
			unset($tmp);
			
			// Fill the client select field
			$sql = "SELECT groupid, name FROM sys_group, client WHERE sys_group.client_id = client.client_id AND client.parent_client_id = ".$client['client_id']." ORDER BY name";
			$clients = $app->db->queryAllRecords($sql);
			$tmp = $app->db->queryOneRecord("SELECT groupid FROM sys_group WHERE client_id = ".$client['client_id']);
			$client_select = '<option value="'.$tmp['groupid'].'">'.$client['contact_name'].'</option>';
			//$tmp_data_record = $app->tform->getDataRecord($this->id);
			if(is_array($clients)) {
				foreach( $clients as $client) {
					$selected = @(is_array($this->dataRecord) && ($client["groupid"] == $this->dataRecord['client_group_id'] || $client["groupid"] == $this->dataRecord['sys_groupid']))?'SELECTED':'';
					$client_select .= "<option value='$client[groupid]' $selected>$client[name]</option>\r\n";
				}
			}
			$app->tpl->setVar("client_group_id",$client_select);

		} else {

			// The user is admin
			if($this->id > 0) {
				$server_id = $this->dataRecord["server_id"];
			} else {
				// Get the first server ID
				$tmp = $app->db->queryOneRecord("SELECT server_id FROM server WHERE web_server = 1 ORDER BY server_name LIMIT 0,1");
				$server_id = $tmp['server_id'];
			}

			$sql = "SELECT ip_address FROM server_ip WHERE server_id = $server_id";
			$ips = $app->db->queryAllRecords($sql);
			$ip_select = "<option value='*'>*</option>";
			//$ip_select = "";
			if(is_array($ips)) {
				foreach( $ips as $ip) {
					$selected = ($ip["ip_address"] == $this->dataRecord["ip_address"])?'SELECTED':'';
					$ip_select .= "<option value='$ip[ip_address]' $selected>$ip[ip_address]</option>\r\n";
				}
			}
			$app->tpl->setVar("ip_address",$ip_select);
			unset($tmp);
			unset($ips);

			// Fill the client select field
			$sql = "SELECT groupid, name FROM sys_group WHERE client_id > 0 ORDER BY name";
			$clients = $app->db->queryAllRecords($sql);
			$client_select = "<option value='0'></option>";
			//$tmp_data_record = $app->tform->getDataRecord($this->id);
			if(is_array($clients)) {
				foreach( $clients as $client) {
					$selected = @(is_array($this->dataRecord) && ($client["groupid"] == $this->dataRecord['client_group_id'] || $client["groupid"] == $this->dataRecord['sys_groupid']))?'SELECTED':'';
					$client_select .= "<option value='$client[groupid]' $selected>$client[name]</option>\r\n";
				}
			}
			$app->tpl->setVar("client_group_id",$client_select);

		}

		/*
		 * If the names are restricted -> remove the restriction, so that the
		 * data can be edited
		 */
		
		//* Get the database name and database user prefix
		$app->uses('getconf');
		$global_config = $app->getconf->get_global_config('sites');
		$dbname_prefix = replacePrefix($global_config['dbname_prefix'], $this->dataRecord);
		$dbuser_prefix = replacePrefix($global_config['dbuser_prefix'], $this->dataRecord);
		
		if ($this->dataRecord['database_name'] != ""){
			/* REMOVE the restriction */
			$app->tpl->setVar("database_name", str_replace($dbname_prefix , '', $this->dataRecord['database_name']));
			$app->tpl->setVar("database_user", str_replace($dbuser_prefix , '', $this->dataRecord['database_user']));
		}
		
		if($_SESSION["s"]["user"]["typ"] == 'admin' || $app->auth->has_clients($_SESSION['s']['user']['userid'])) {
			$app->tpl->setVar("database_name_prefix", $global_config['dbname_prefix']);
			$app->tpl->setVar("database_user_prefix", $global_config['dbuser_prefix']);
		} else {
			$app->tpl->setVar("database_name_prefix", $dbname_prefix);
			$app->tpl->setVar("database_user_prefix", $dbuser_prefix);
		}
		
		if($this->id > 0) {
			//* we are editing a existing record
			$app->tpl->setVar("edit_disabled", 1);
			$app->tpl->setVar("server_id_value", $this->dataRecord["server_id"]);
			$app->tpl->setVar("database_charset_value", $this->dataRecord["database_charset"]);
		} else {
			$app->tpl->setVar("edit_disabled", 0);
		}

		parent::onShowEnd();
	}

	function onSubmit() {
		global $app, $conf;

		if($_SESSION["s"]["user"]["typ"] != 'admin') {
			// Get the limits of the client
			$client_group_id = $_SESSION["s"]["user"]["default_group"];
			$client = $app->db->queryOneRecord("SELECT default_dbserver, limit_database FROM sys_group, client WHERE sys_group.client_id = client.client_id and sys_group.groupid = $client_group_id");

			// When the record is updated
			if($this->id > 0) {
				// restore the server ID if the user is not admin and record is edited
				$tmp = $app->db->queryOneRecord("SELECT server_id FROM web_database WHERE database_id = ".intval($this->id));
				$this->dataRecord["server_id"] = $tmp["server_id"];
				unset($tmp);
				// When the record is inserted
			} else {
				// set the server ID to the default dbserver of the client
				$this->dataRecord["server_id"] = $client["default_dbserver"];


				// Check if the user may add another database
				if($client["limit_database"] >= 0) {
					$tmp = $app->db->queryOneRecord("SELECT count(database_id) as number FROM web_database WHERE sys_groupid = $client_group_id");
					if($tmp["number"] >= $client["limit_database"]) {
						$app->error($app->tform->wordbook["limit_database_txt"]);
					}
				}

			}

			// Clients may not set the client_group_id, so we unset them if user is not a admin and the client is not a reseller
			if(!$app->auth->has_clients($_SESSION['s']['user']['userid'])) unset($this->dataRecord["client_group_id"]);
		}


		parent::onSubmit();
	}

	function onBeforeUpdate() {
		global $app, $conf, $interfaceConf;

		/*
		* If the names should be restricted -> do it!
		*/
		
		
		//* Get the database name and database user prefix
		$app->uses('getconf');
		$global_config = $app->getconf->get_global_config('sites');
		$dbname_prefix = replacePrefix($global_config['dbname_prefix'], $this->dataRecord);
		$dbuser_prefix = replacePrefix($global_config['dbuser_prefix'], $this->dataRecord);

		//* Prevent that the database name and charset is changed
		$old_record = $app->tform->getDataRecord($this->id);
		if($old_record["database_name"] != $dbname_prefix . $this->dataRecord["database_name"]) {
			$app->tform->errorMessage .= $app->tform->wordbook["database_name_change_txt"].'<br />';
		}
		if($old_record["database_charset"] != $this->dataRecord["database_charset"]) {
			$app->tform->errorMessage .= $app->tform->wordbook["database_charset_change_txt"].'<br />';
		}
		
		//* Database username and database name shall not be empty
		if($this->dataRecord['database_name'] == '') $app->tform->errorMessage .= $app->tform->wordbook["database_name_error_empty"].'<br />';
		if($this->dataRecord['database_user'] == '') $app->tform->errorMessage .= $app->tform->wordbook["database_user_error_empty"].'<br />';

		//* Check if the server has been changed
		// We do this only for the admin or reseller users, as normal clients can not change the server ID anyway
		if($_SESSION["s"]["user"]["typ"] == 'admin' || $app->auth->has_clients($_SESSION['s']['user']['userid'])) {
			if($old_record["server_id"] != $this->dataRecord["server_id"]) {
				//* Add a error message and switch back to old server
				$app->tform->errorMessage .= $app->lng('The Server can not be changed.');
				$this->dataRecord["server_id"] = $rec['server_id'];
			}
		}
		unset($old_record);
		
		if(strlen($dbname_prefix . $this->dataRecord['database_name']) > 64) $app->tform->errorMessage .= str_replace('{db}',$dbname_prefix . $this->dataRecord['database_name'],$app->tform->wordbook["database_name_error_len"]).'<br />';
		if(strlen($dbuser_prefix . $this->dataRecord['database_user']) > 16) $app->tform->errorMessage .= str_replace('{user}',$dbuser_prefix . $this->dataRecord['database_user'],$app->tform->wordbook["database_user_error_len"]).'<br />';
		
		//* Check database name and user against blacklist
		$dbname_blacklist = array($conf['db_database'],'mysql');
		if(in_array($dbname_prefix . $this->dataRecord['database_name'],$dbname_blacklist)) {
			$app->tform->errorMessage .= $app->lng('Database name not allowed.').'<br />';
		}
		
		$dbuser_blacklist = array($conf['db_user'],'mysql','root');
		if(in_array($dbname_prefix . $this->dataRecord['database_user'],$dbname_blacklist)) {
			$app->tform->errorMessage .= $app->lng('Database user not allowed.').'<br />';
		}
		
		if ($app->tform->errorMessage == ''){
			/* restrict the names if there is no error */
            /* crop user and db names if they are too long -> mysql: user: 16 chars / db: 64 chars */
			$this->dataRecord['database_name'] = substr($dbname_prefix . $this->dataRecord['database_name'], 0, 64);
			$this->dataRecord['database_user'] = substr($dbuser_prefix . $this->dataRecord['database_user'], 0, 16);
		}
		
		//* Check for duplicates
		$tmp = $app->db->queryOneRecord("SELECT count(database_id) as dbnum FROM web_database WHERE database_name = '".$this->dataRecord['database_name']."' AND server_id = '".$this->dataRecord["server_id"]."' AND database_id != '".$this->id."'");
		if($tmp['dbnum'] > 0) $app->tform->errorMessage .= $app->lng('database_name_error_unique').'<br />';
		
		parent::onBeforeUpdate();
	}

	function onBeforeInsert() {
		global $app, $conf, $interfaceConf;
		
		//* Database username and database name shall not be empty
		if($this->dataRecord['database_name'] == '') $app->tform->errorMessage .= $app->tform->wordbook["database_name_error_empty"].'<br />';
		if($this->dataRecord['database_user'] == '') $app->tform->errorMessage .= $app->tform->wordbook["database_user_error_empty"].'<br />';

		//* Get the database name and database user prefix
		$app->uses('getconf');
		$global_config = $app->getconf->get_global_config('sites');
		$dbname_prefix = replacePrefix($global_config['dbname_prefix'], $this->dataRecord);
		$dbuser_prefix = replacePrefix($global_config['dbuser_prefix'], $this->dataRecord);
		
		if(strlen($dbname_prefix . $this->dataRecord['database_name']) > 64) $app->tform->errorMessage .= str_replace('{db}',$dbname_prefix . $this->dataRecord['database_name'],$app->tform->wordbook["database_name_error_len"]).'<br />';
		if(strlen($dbuser_prefix . $this->dataRecord['database_user']) > 16) $app->tform->errorMessage .= str_replace('{user}',$dbuser_prefix . $this->dataRecord['database_user'],$app->tform->wordbook["database_user_error_len"]).'<br />';
		
		//* Check database name and user against blacklist
		$dbname_blacklist = array($conf['db_database'],'mysql');
		if(in_array($dbname_prefix . $this->dataRecord['database_name'],$dbname_blacklist)) {
			$app->tform->errorMessage .= $app->lng('Database name not allowed.').'<br />';
		}
		
		$dbuser_blacklist = array($conf['db_user'],'mysql','root');
		if(in_array($dbname_prefix . $this->dataRecord['database_user'],$dbname_blacklist)) {
			$app->tform->errorMessage .= $app->lng('Database user not allowed.').'<br />';
		}

		/* restrict the names */
        /* crop user and db names if they are too long -> mysql: user: 16 chars / db: 64 chars */
		if ($app->tform->errorMessage == ''){
			$this->dataRecord['database_name'] = substr($dbname_prefix . $this->dataRecord['database_name'], 0, 64);
			$this->dataRecord['database_user'] = substr($dbuser_prefix . $this->dataRecord['database_user'], 0, 16);
		}
		
		//* Check for duplicates
		$tmp = $app->db->queryOneRecord("SELECT count(database_id) as dbnum FROM web_database WHERE database_name = '".$this->dataRecord['database_name']."' AND server_id = '".$this->dataRecord["server_id"]."'");
		if($tmp['dbnum'] > 0) $app->tform->errorMessage .= $app->tform->lng('database_name_error_unique').'<br />';

		parent::onBeforeInsert();
	}

	function onAfterInsert() {
		global $app, $conf;

		// make sure that the record belongs to the clinet group and not the admin group when a dmin inserts it
		// also make sure that the user can not delete domain created by a admin
		if($_SESSION["s"]["user"]["typ"] == 'admin' && isset($this->dataRecord["client_group_id"])) {
			$client_group_id = intval($this->dataRecord["client_group_id"]);
			$app->db->query("UPDATE web_database SET sys_groupid = $client_group_id, sys_perm_group = 'ru' WHERE database_id = ".$this->id);
		}
		if($app->auth->has_clients($_SESSION['s']['user']['userid']) && isset($this->dataRecord["client_group_id"])) {
			$client_group_id = intval($this->dataRecord["client_group_id"]);
			$app->db->query("UPDATE web_database SET sys_groupid = $client_group_id, sys_perm_group = 'riud' WHERE database_id = ".$this->id);
		}
	}

	function onAfterUpdate() {
		global $app, $conf;

		// make sure that the record belongs to the client group and not the admin group when a admin inserts it
		// also make sure that the user can not delete domain created by a admin
		if($_SESSION["s"]["user"]["typ"] == 'admin' && isset($this->dataRecord["client_group_id"])) {
			$client_group_id = intval($this->dataRecord["client_group_id"]);
			$app->db->query("UPDATE web_database SET sys_groupid = $client_group_id, sys_perm_group = 'ru' WHERE database_id = ".$this->id);
		}
		if($app->auth->has_clients($_SESSION['s']['user']['userid']) && isset($this->dataRecord["client_group_id"])) {
			$client_group_id = intval($this->dataRecord["client_group_id"]);
			$app->db->query("UPDATE web_database SET sys_groupid = $client_group_id, sys_perm_group = 'riud' WHERE database_id = ".$this->id);
		}

	}

}

$page = new page_action;
$page->onLoad();

?>