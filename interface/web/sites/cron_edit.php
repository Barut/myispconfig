<?php

/*
Copyright (c) 2007, Till Brehm, projektfarm Gmbh
Modified 2009, Marius Cramer, pixcept KG
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

$tform_def_file = "form/cron.tform.php";

/******************************************
* End Form configuration
******************************************/

require_once('../../lib/config.inc.php');
require_once('../../lib/app.inc.php');

//* Check permissions for module
$app->auth->check_module_permissions('sites');

// Loading classes
$app->uses('tpl,tform,tform_actions,validate_cron');
$app->load('tform_actions');

class page_action extends tform_actions {
	
	function onShowNew() {
		global $app, $conf;
		
		// we will check only users, not admins
		if($_SESSION["s"]["user"]["typ"] == 'user') {
			if(!$app->tform->checkClientLimit('limit_cron')) {
				$app->error($app->tform->wordbook["limit_cron_txt"]);
			}
			if(!$app->tform->checkResellerLimit('limit_cron')) {
				$app->error('Reseller: '.$app->tform->wordbook["limit_cron_txt"]);
			}
		}
		
		parent::onShowNew();
	}
	
	function onShowEnd() {
		global $app, $conf;
		
        if($this->id > 0) {
            //* we are editing a existing record
            $app->tpl->setVar("edit_disabled", 1);
            $app->tpl->setVar("parent_domain_id_value", $this->dataRecord["parent_domain_id"]);
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
			$client = $app->db->queryOneRecord("SELECT limit_cron, limit_cron_type FROM sys_group, client WHERE sys_group.client_id = client.client_id and sys_group.groupid = $client_group_id");
		
			// When the record is updated
			if($this->id > 0) {
			// When the record is inserted
			} else {
				// Check if the user may add another cron job.
				if($client["limit_cron"] >= 0) {
					$tmp = $app->db->queryOneRecord("SELECT count(id) as number FROM cron WHERE sys_groupid = $client_group_id");
					if($tmp["number"] >= $client["limit_cron"]) {
						$app->error($app->tform->wordbook["limit_cron_txt"]);
					}
				}
			}
		}
		
        // Get the record of the parent domain
        $parent_domain = $app->db->queryOneRecord("select * FROM web_domain WHERE domain_id = ".intval(@$this->dataRecord["parent_domain_id"]));
        
        // Set fixed values
        $this->dataRecord["server_id"] = $parent_domain["server_id"];

        //* get type of command
        $command = $this->dataRecord["command"];
        if(preg_match("'^http(s)?:\/\/'i", $command)) {
            $this->dataRecord["type"] = 'url';
        } else {
            $domain_owner = $app->db->queryOneRecord("SELECT limit_cron_type FROM sys_group, client WHERE sys_group.client_id = client.client_id and sys_group.groupid = ".intval($parent_domain["sys_groupid"]));
            if($domain_owner["limit_cron_type"] == 'full') $this->dataRecord["type"] = 'full';
            else $this->dataRecord["type"] = 'chrooted';
        }
        
        parent::onSubmit();
	}
	
    function onUpdateSave($sql) {
        global $app;
        
        $has_error = false;
        //* last chance to stop this, so check frequency limit!
        if($_SESSION["s"]["user"]["typ"] != 'admin') {
            // Get the limits of the client
            $client_group_id = $_SESSION["s"]["user"]["default_group"];
            $client = $app->db->queryOneRecord("SELECT limit_cron_frequency FROM sys_group, client WHERE sys_group.client_id = client.client_id and sys_group.groupid = $client_group_id");
        
            if($client["limit_cron_frequency"] > 1) {
                if($app->tform->cron_min_freq < $client["limit_cron_frequency"]) {
                    $app->error($app->tform->wordbook["limit_cron_frequency_txt"]);
                    $has_error = true;
                }
            }
        }
        
        if($has_error == true) {
            parent::onError();
            exit;
        }
        else parent::onUpdateSave($sql);
    }
    
    function onInsertSave($sql) {
        global $app;
    
        $has_error = false;
        //* last chance to stop this, so check frequency limit!
        if($_SESSION["s"]["user"]["typ"] != 'admin') {
            // Get the limits of the client
            $client_group_id = $_SESSION["s"]["user"]["default_group"];
            $client = $app->db->queryOneRecord("SELECT limit_cron_frequency FROM sys_group, client WHERE sys_group.client_id = client.client_id and sys_group.groupid = $client_group_id");
        
            if($client["limit_cron_frequency"] > 1) {
                if($app->tform->cron_min_freq < $client["limit_cron_frequency"]) {
                    $app->error($app->tform->wordbook["limit_cron_frequency_txt"]);
                    $has_error = true;
                }
            }
        }
        
        if($has_error == true) {
            parent::onError();
            exit;
        } else {
			return parent::onInsertSave($sql);
		}
    }
    
	function onAfterInsert() {
		global $app, $conf;
		
        $web = $app->db->queryOneRecord("SELECT * FROM web_domain WHERE domain_id = ".intval($this->dataRecord["parent_domain_id"]));
        $server_id = $web["server_id"];
        
        // The cron shall be owned by the same group then the website
        $sys_groupid = $web['sys_groupid'];
        
        $sql = "UPDATE shell_user SET server_id = $server_id, sys_groupid = '$sys_groupid' WHERE id = ".$this->id;
        $app->db->query($sql);
	}
	
	function onAfterUpdate() {
		global $app, $conf;
		
		
	}
    
    function getClientName() {
        global $app, $conf;
    
        if($_SESSION["s"]["user"]["typ"] != 'admin') {
            // Get the group-id of the user
            $client_group_id = $_SESSION["s"]["user"]["default_group"];
        } else {
            // Get the group-id from the data itself
            $web = $app->db->queryOneRecord("SELECT sys_groupid FROM web_domain WHERE domain_id = ".intval($this->dataRecord['parent_domain_id']));
            $client_group_id = $web['sys_groupid'];
        }
        /* get the name of the client */
        $tmp = $app->db->queryOneRecord("SELECT name FROM sys_group WHERE groupid = " . $client_group_id);
        $clientName = $tmp['name'];
        if ($clientName == "") $clientName = 'default';
        $clientName = convertClientName($clientName);
        
        return $clientName;
    
    }
	
}

$page = new page_action;
$page->onLoad();

?>