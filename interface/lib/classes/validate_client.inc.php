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

class validate_client {
	
	/*
		Validator function to check if a username is unique.
	*/
	function username_unique($field_name, $field_value, $validator) {
		global $app;
		
		if(isset($app->remoting_lib->primary_id)) {
			$client_id = $app->remoting_lib->primary_id;
		} else {
			$client_id = $app->tform->primary_id;
		}
		
		if($client_id == 0) {
        	$num_rec = $app->db->queryOneRecord("SELECT count(*) as number FROM sys_user WHERE username = '".$app->db->quote($field_value)."'");
            	if($num_rec["number"] > 0) {
                	$errmsg = $validator['errmsg'];
					if(isset($app->tform->wordbook[$errmsg])) {
                    	return $app->tform->wordbook[$errmsg]."<br>\r\n";
					} else {
						return $errmsg."<br>\r\n";
					}
                }
        } else {
        	$num_rec = $app->db->queryOneRecord("SELECT count(*) as number FROM sys_user WHERE username = '".$app->db->quote($field_value)."' AND client_id != ".$client_id);
			if($num_rec["number"] > 0) {
            	$errmsg = $validator['errmsg'];
                if(isset($app->tform->wordbook[$errmsg])) {
                	return $app->tform->wordbook[$errmsg]."<br>\r\n";
				} else {
					return $errmsg."<br>\r\n";
				}
			}
		}
	}
	
	function username_collision($field_name, $field_value, $validator) {
		global $app;
		
		if(isset($app->remoting_lib->primary_id)) {
			$client_id = $app->remoting_lib->primary_id;
		} else {
			$client_id = $app->tform->primary_id;
		}
		
		$app->uses('getconf');
		$global_config = $app->getconf->get_global_config('sites');
		
		if((trim($field_value) == 'web' || preg_match('/^web[0-9]/',$field_value)) && 
		  ($global_config['ftpuser_prefix'] == '[CLIENTNAME]' || 
		   $global_config['ftpuser_prefix'] == '' ||
		   $global_config['shelluser_prefix'] == '[CLIENTNAME]' ||
		   $global_config['shelluser_prefix'] == '' )) {
			$errmsg = $validator['errmsg'];
			if(isset($app->tform->wordbook[$errmsg])) {
				return $app->tform->wordbook[$errmsg]."<br>\r\n";
			} else {
				return $errmsg."<br>\r\n";
			}
		}
		
		
		
		
	}
	
	
	
	
}