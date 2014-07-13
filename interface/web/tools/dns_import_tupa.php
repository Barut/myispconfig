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

require_once('../../lib/config.inc.php');
require_once('../../lib/app.inc.php');

//* Check permissions for module
$app->auth->check_module_permissions('admin');

//* This is only allowed for administrators
if(!$app->auth->is_admin()) die('only allowed for administrators.');

$app->uses('tpl,validate_dns');

$app->tpl->newTemplate('form.tpl.htm');
$app->tpl->setInclude('content_tpl', 'templates/dns_import_tupa.htm');
$msg = '';
$error = '';

// Resyncing dns zones
if(isset($_POST['start']) && $_POST['start'] == 1) {
	
	//* Set variable sin template
	$app->tpl->setVar('dbhost',$_POST['dbhost']);
	$app->tpl->setVar('dbname',$_POST['dbname']);
	$app->tpl->setVar('dbuser',$_POST['dbuser']);
	$app->tpl->setVar('dbpassword',$_POST['dbpassword']);
	
	//* Establish connection to external database
	$msg .= 'Connecting to external database...<br />';
	
	//* Backup DB login details
	$conf_bak['db_host'] = $conf['db_host'];
	$conf_bak['db_database'] = $conf['db_database'];
	$conf_bak['db_user'] = $conf['db_user'];
	$conf_bak['db_password'] = $conf['db_password'];
	
	//* Set external Login details
	$conf['db_host'] = $_POST['dbhost'];
	$conf['db_database'] = $_POST['dbname'];
	$conf['db_user'] = $_POST['dbuser'];
	$conf['db_password'] = $_POST['dbpassword'];
	
	//* create new db object
	$exdb = new db();
	
	$server_id = 1;
	$sys_userid = 1;
	$sys_groupid = 1;
	
	function addot($text) {
		return trim($text) . '.';
	}
	
	//* Connect to DB
	if($exdb->connect()) {
		$domains = $exdb->queryAllRecords("SELECT * FROM domains WHERE type = 'MASTER'");
		if(is_array($domains)) {
			foreach($domains as $domain) {
				$soa = $exdb->queryOneRecord("SELECT * FROM records WHERE type = 'SOA' AND domain_id = ".$domain['id']);
				if(is_array($soa)) {
					$parts = explode(' ',$soa['content']);
					$origin = addot($soa['name']);
					$ns = addot($parts[0]);
					$mbox = addot($parts[1]);
					$serial = $parts[2];
					$refresh = 7200;
					$retry =  540;
					$expire = 604800;
					$minimum = 86400;
					$ttl = $soa['ttl'];
					
					$insert_data = "(`sys_userid`, `sys_groupid`, `sys_perm_user`, `sys_perm_group`, `sys_perm_other`, `server_id`, `origin`, `ns`, `mbox`, `serial`, `refresh`, `retry`, `expire`, `minimum`, `ttl`, `active`, `xfer`) VALUES
					('$sys_userid', '$sys_groupid', 'riud', 'riud', '', '$server_id', '$origin', '$ns', '$mbox', '$serial', '$refresh', '$retry', '$expire', '$minimum', '$ttl', 'Y', '')";
					$dns_soa_id = $app->db->datalogInsert('dns_soa', $insert_data, 'id');
					unset($parts);
					$msg .= 'Import Zone: '.$soa['name'].'<br />';
					
					//* Process the other records
					$records = $exdb->queryAllRecords("SELECT * FROM records WHERE type != 'SOA' AND domain_id = ".$domain['id']);
					if(is_array($records)) {
						foreach($records as $rec) {
							$rr = array();
							
							$rr['name'] = addot($rec['name']);
							$rr['type'] = $rec['type'];
							$rr['aux'] = $rec['prio'];
							$rr['ttl'] = $rec['ttl'];
							
							if($rec['type'] == 'NS' || $rec['type'] == 'MX' || $rec['type'] == 'CNAME') {
								$rr['data'] = addot($rec['content']);
							} else {
								$rr['data'] = $rec['content'];
							}
							
							$insert_data = "(`sys_userid`, `sys_groupid`, `sys_perm_user`, `sys_perm_group`, `sys_perm_other`, `server_id`, `zone`, `name`, `type`, `data`, `aux`, `ttl`, `active`) VALUES
							('$sys_userid', '$sys_groupid', 'riud', 'riud', '', '$server_id', '$dns_soa_id', '$rr[name]', '$rr[type]', '$rr[data]', '$rr[aux]', '$rr[ttl]', 'Y')";
							$dns_rr_id = $app->db->datalogInsert('dns_rr', $insert_data, 'id');
							//$msg .= $insert_data.'<br />';
							
						}
					}
				}
				
			}
		}
		
		
		
	} else {
		$error .= $exdb->errorMessage;
	}
	
	//* restore db login details
	$conf['db_host'] = $conf_bak['db_host'];
	$conf['db_database'] = $conf_bak['db_database'];
	$conf['db_user'] = $conf_bak['db_user'];
	$conf['db_password'] = $conf_bak['db_password'];
	
}

$app->tpl->setVar('msg',$msg);
$app->tpl->setVar('error',$error);


$app->tpl_defaults();
$app->tpl->pparse();


?>