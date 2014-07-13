<?php

/*
Copyright (c) 2005, Till Brehm, projektfarm Gmbh
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
$app->auth->check_module_permissions('sites');

$server_id = intval($_GET["server_id"]);
$client_group_id = intval($_GET["client_group_id"]);
$ip_type = $app->db->quote($_GET['ip_type']);

if($_SESSION["s"]["user"]["typ"] == 'admin' or $app->auth->has_clients($_SESSION['s']['user']['userid'])) {

	$sql = "SELECT ip_address FROM server_ip WHERE ip_type = '$ip_type' AND server_id = $server_id";
	$ips = $app->db->queryAllRecords($sql);
	// $ip_select = "<option value=''></option>";
	if($ip_type == 'IPv4'){
		$ip_select = "*";
	} else {
		$ip_select = "";
	}
	if(is_array($ips)) {
		foreach( $ips as $ip) {
			//$selected = ($ip["ip_address"] == $this->dataRecord["ip_address"])?'SELECTED':'';
			$ip_select .= "#$ip[ip_address]";
		}
	}
	unset($tmp);
	unset($ips);
}

echo $ip_select;
?>