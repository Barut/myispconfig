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


/******************************************
* Begin Form configuration
******************************************/

$tform_def_file = "form/web_subdomain.tform.php";

/******************************************
* End Form configuration
******************************************/

require_once('../../lib/config.inc.php');
require_once('../../lib/app.inc.php');

//* Check permissions for module
$app->auth->check_module_permissions('sites');

// Loading classes
$app->uses('tpl,tform,tform_actions');
$app->load('tform_actions');

class page_action extends tform_actions {
	
	var $parent_domain_record;
	
	function onShowNew() {
		global $app, $conf;
		
		// we will check only users, not admins
		if($_SESSION["s"]["user"]["typ"] == 'user') {
			if(!$app->tform->checkClientLimit('limit_web_subdomain',"type = 'subdomain'")) {
				$app->error($app->tform->wordbook["limit_web_subdomain_txt"]);
			}
			if(!$app->tform->checkResellerLimit('limit_web_subdomain',"type = 'subdomain'")) {
				$app->error('Reseller: '.$app->tform->wordbook["limit_web_subdomain_txt"]);
			}
		}
		
		parent::onShowNew();
	}
	
	function onShowEnd() {
		global $app, $conf;
		
		// Get the record of the parent domain
		$parent_domain = $app->db->queryOneRecord("select * FROM web_domain WHERE domain_id = ".intval(@$this->dataRecord["parent_domain_id"]));
		
		// remove the parent domain part of the domain name before we show it in the text field.
		$this->dataRecord["domain"] = str_replace('.'.$parent_domain["domain"],'',$this->dataRecord["domain"]);
		$app->tpl->setVar("domain",$this->dataRecord["domain"]);
		
		parent::onShowEnd();
		
	}

	function onSubmit() {
		global $app, $conf;
		
		// Get the record of the parent domain
		$parent_domain = $app->db->queryOneRecord("select * FROM web_domain WHERE domain_id = ".intval(@$this->dataRecord["parent_domain_id"]));
		
		// Set a few fixed values
		$this->dataRecord["type"] = 'subdomain';
		$this->dataRecord["server_id"] = $parent_domain["server_id"];
		$this->dataRecord["domain"] = $this->dataRecord["domain"].'.'.$parent_domain["domain"];
		
		$this->parent_domain_record = $parent_domain;
		
		//* make sure that the email domain is lowercase
		if(isset($this->dataRecord["domain"])) $this->dataRecord["domain"] = strtolower($this->dataRecord["domain"]);
		
		parent::onSubmit();
	}
	
	function onAfterInsert() {
		global $app, $conf;
		
		$app->db->query('UPDATE web_domain SET sys_groupid = '.intval($this->parent_domain_record['sys_groupid']).' WHERE domain_id = '.$this->id);
		
	}
	
	
}

$page = new page_action;
$page->onLoad();

?>