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

class custom_datasource {
	
	function master_templates($field, $record) {
		global $app, $conf;
		$records = $app->db->queryAllRecords("SELECT template_id,template_name FROM client_template WHERE template_type ='m'");
		$records_new[0] = $app->lng('Custom');
		foreach($records as $rec) {
			$key = $rec['template_id'];
			$records_new[$key] = $rec['template_name'];
		}
		return $records_new;
	}
	
	function dns_servers($field, $record) {
		global $app, $conf;
		
		if($_SESSION["s"]["user"]["typ"] == 'user') {
			// Get the limits of the client
			$client_group_id = $_SESSION["s"]["user"]["default_group"];
			$client = $app->db->queryOneRecord("SELECT default_dnsserver FROM sys_group, client WHERE sys_group.client_id = client.client_id and sys_group.groupid = $client_group_id");
			$sql = "SELECT server_id,server_name FROM server WHERE server_id = ".$client['default_dnsserver'];
		} else {
			$sql = "SELECT server_id,server_name FROM server WHERE dns_server = 1 ORDER BY server_name";
		}
		$records = $app->db->queryAllRecords($sql);
		$records_new = array();
		if(is_array($records)) {
			foreach($records as $rec) {
				$key = $rec['server_id'];
				$records_new[$key] = $rec['server_name'];
			}
		}
		return $records_new;
	}
	
	function webdav_domains($field, $record) {
		global $app, $conf;
		
		$servers = $app->db->queryAllRecords("SELECT * FROM server WHERE active = 1 AND mirror_server_id = 0");
		$server_ids = array();
		$app->uses('getconf');
		if(is_array($servers) && !empty($servers)){
			foreach($servers as $server){
				$web_config = $app->getconf->get_server_config($server['server_id'], 'web');
				if($web_config['server_type'] != 'nginx') $server_ids[] = $server['server_id'];
			}
		}
		$server_ids = implode(',', $server_ids);
		$records = $app->db->queryAllRecords("SELECT domain_id,domain FROM web_domain WHERE type = 'vhost' AND server_id IN (".$server_ids.") AND ".$app->tform->getAuthSQL('r')." ORDER BY domain");
		
		$records_new = array();
		if(is_array($records)) {
			foreach($records as $rec) {
				$key = $rec['domain_id'];
				$records_new[$key] = $rec['domain'];
			}
		}
		return $records_new;
	}
	
	
	function client_servers($field, $record) {
		global $app, $conf;
		
		$server_type = $field['name'];
		
		switch($server_type) {
			case 'default_mailserver':
				$field = 'mail_server';
			break;
			case 'default_webserver':
				$field = 'web_server';
			break;
			case 'default_dnsserver':
				$field = 'dns_server';
			break;
			case 'default_fileserver':
				$field = 'file_server';
			break;
			case 'default_dbserver':
				$field = 'db_server';
			break;
			case 'default_vserverserver':
				$field = 'vserver_server';
			break;
			default:
				$field = 'web_server';
			break;
		}
		
		if($_SESSION["s"]["user"]["typ"] == 'user') {
			// Get the limits of the client
			$client_group_id = $_SESSION["s"]["user"]["default_group"];
			$sql = "SELECT $server_type as server_id FROM sys_group, client WHERE sys_group.client_id = client.client_id and sys_group.groupid = $client_group_id";
			$client = $app->db->queryOneRecord($sql);
			if($client['server_id'] > 0) {
				//* Select the default server for the client
				$sql = "SELECT server_id,server_name FROM server WHERE server_id = ".$client['server_id'];
			} else {
				//* Not able to find the clients defaults, use this as fallback and add a warning message to the log
				$app->log('Unable to find default server for client in custom_datasource.inc.php',1);
				$sql = "SELECT server_id,server_name FROM server WHERE $field = 1 ORDER BY server_name";
			}
		} else {
			//* The logged in user is admin, so we show him all available servers of a specific type.
			$sql = "SELECT server_id,server_name FROM server WHERE $field = 1 ORDER BY server_name";
		}
		
		$records = $app->db->queryAllRecords($sql);
		$records_new = array();
		if(is_array($records)) {
			foreach($records as $rec) {
				$key = $rec['server_id'];
				$records_new[$key] = $rec['server_name'];
			}
		}
		return $records_new;
	}
	
	

}

?>