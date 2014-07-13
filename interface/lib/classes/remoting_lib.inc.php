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

--UPDATED 08.2009--
Full SOAP support for ISPConfig 3.1.4 b
Updated by Arkadiusz Roch & Artur Edelman
Copyright (c) Tri-Plex technology

*/

/**
* Formularbehandlung
*
* Funktionen zur Umwandlung von Formulardaten
* sowie zum vorbereiten von HTML und SQL
* Ausgaben
*
*        Tabellendefinition
*
*        Datentypen:
*        - INTEGER (Wandelt Ausdr�cke in Int um)
*        - DOUBLE
*        - CURRENCY (Formatiert Zahlen nach W�hrungsnotation)
*        - VARCHAR (kein weiterer Format Check)
*        - DATE (Datumsformat, Timestamp Umwandlung)
*
*        Formtype:
*        - TEXT (normales Textfeld)
*        - PASSWORD (Feldinhalt wird nicht angezeigt)
*        - SELECT (Gibt Werte als option Feld aus)
*        - MULTIPLE (Select-Feld mit nehreren Werten)
*
*        VALUE:
*        - Wert oder Array
*
*        SEPARATOR
*        - Trennzeichen f�r multiple Felder
*
*        Hinweis:
*        Das ID-Feld ist nicht bei den Table Values einzuf�gen.
*/

class remoting_lib {
	
        /**
        * Definition of the database atble (array)
        * @var tableDef
        */
        private $tableDef;

        /**
        * Private
        * @var action
        */
        private $action;

        /**
        * Table name (String)
        * @var table_name
        */
        private $table_name;

        /**
        * Debug Variable
        * @var debug
        */
        private $debug = 0;

        /**
        * name of the primary field of the database table (string)
        * @var table_index
        */
        var $table_index;

        /**
        * contains the error messages
        * @var errorMessage
        */
        var $errorMessage = '';

        var $dateformat = "d.m.Y";
    	var $formDef = array();
        var $wordbook;
        var $module;
        var $primary_id;
		var $diffrec = array();
		
		var $sys_username;
		var $sys_userid;
		var $sys_default_group;
		var $sys_groups;

		
		//* Load the form definition from file.
    	function loadFormDef($file) {
			global $app,$conf;
            
			include_once($file);
				
			$this->formDef = $form;
			unset($this->formDef['tabs']);
                
			//* Copy all fields from all tabs into one form definition
			foreach($form['tabs'] as $tab) {
				foreach($tab['fields'] as $key => $value) {
					$this->formDef['fields'][$key] = $value;
				}
			}
			unset($form);
				
            return true;
        }
		
		//* Load the user profile
		function loadUserProfile($client_id = 0) {
			global $app,$conf;

			$client_id = intval($client_id);
            
			if($client_id == 0) {
				$this->sys_username         = 'admin';
				$this->sys_userid            = 1;
				$this->sys_default_group     = 1;
				$this->sys_groups            = 1;
				$_SESSION["s"]["user"]["typ"] = 'admin';
			} else {
				//* load system user - try with sysuser and before with userid (workarrond)
				/*
				$user = $app->db->queryOneRecord("SELECT * FROM sys_user WHERE sysuser_id = $client_id");
				if(empty($user["userid"])) {
						$user = $app->db->queryOneRecord("SELECT * FROM sys_user WHERE userid = $client_id");		
						if(empty($user["userid"])) {
								$this->errorMessage .= "No sysuser with the ID $client_id found.";
								return false;
						}
				}*/
				
				$user = $app->db->queryOneRecord("SELECT * FROM sys_user WHERE client_id = $client_id");
				$this->sys_username         = $user['username'];
				$this->sys_userid            = $user['userid'];
				$this->sys_default_group     = $user['default_group'];
				$this->sys_groups             = $user['groups'];
				// $_SESSION["s"]["user"]["typ"] = $user['typ'];
				// we have to force admin priveliges for the remoting API as some function calls might fail otherwise.
				$_SESSION["s"]["user"]["typ"] = 'admin';
			}

		return true;
	    }  


        /**
        * Converts data in human readable form
        *
        * @param record
        * @return record
        */
        function decode($record) {
                $new_record = '';
				if(is_array($record)) {
                        foreach($this->formDef['fields'] as $key => $field) {
                                switch ($field['datatype']) {
                                case 'VARCHAR':
                                        $new_record[$key] = stripslashes($record[$key]);
                                break;

                                case 'TEXT':
                                        $new_record[$key] = stripslashes($record[$key]);
                                break;

                                case 'DATETSTAMP':
                                        if($record[$key] > 0) {
                                                $new_record[$key] = date($this->dateformat,$record[$key]);
                                        }
                                break;
								
								case 'DATE':
                                        if($record[$key] != '' && $record[$key] != '0000-00-00') {
												$tmp = explode('-',$record[$key]);
                                                $new_record[$key] = date($this->dateformat,mktime(0, 0, 0, $tmp[1]  , $tmp[2], $tmp[0]));
                                        }
                                break;

                                case 'INTEGER':
										//* We use + 0 to force the string to be a number as 
										//* intval return value is too limited on 32bit systems
                                        if(intval($record[$key]) == 2147483647) {
											$new_record[$key] = $record[$key] + 0;
										} else {
											$new_record[$key] = intval($record[$key]);
										}
                                break;

                                case 'DOUBLE':
                                        $new_record[$key] = $record[$key];
                                break;

                                case 'CURRENCY':
                                        $new_record[$key] = number_format($record[$key], 2, ',', '');
                                break;

                                default:
                                        $new_record[$key] = stripslashes($record[$key]);
                                }
                        }

                }
				
        return $new_record;
        }

        /**
        * Get the key => value array of a form filled from a datasource definitiom
        *
        * @param field = array with field definition
        * @param record = Dataset as array
        * @return key => value array for the value field of a form
        */

        function getDatasourceData($field, $record) {
                global $app;

                $values = array();

                if($field["datasource"]["type"] == 'SQL') {

                        // Preparing SQL string. We will replace some
                        // common placeholders
                        $querystring = $field["datasource"]["querystring"];
                        $querystring = str_replace("{USERID}",$this->sys_userid,$querystring);
                        $querystring = str_replace("{GROUPID}",$this->sys_default_group,$querystring);
                        $querystring = str_replace("{GROUPS}",$this->sys_groups,$querystring);
                        $table_idx = $this->formDef['db_table_idx'];
						
						$tmp_recordid = (isset($record[$table_idx]))?$record[$table_idx]:0;
                        $querystring = str_replace("{RECORDID}",$tmp_recordid,$querystring);
						unset($tmp_recordid);
						
                        $querystring = str_replace("{AUTHSQL}",$this->getAuthSQL('r'),$querystring);

                        // Getting the records
                        $tmp_records = $app->db->queryAllRecords($querystring);
                        if($app->db->errorMessage != '') die($app->db->errorMessage);
                        if(is_array($tmp_records)) {
                                $key_field = $field["datasource"]["keyfield"];
                                $value_field = $field["datasource"]["valuefield"];
                                foreach($tmp_records as $tmp_rec) {
                                        $tmp_id = $tmp_rec[$key_field];
                                        $values[$tmp_id] = $tmp_rec[$value_field];
                                }
                        }
                }

                if($field["datasource"]["type"] == 'CUSTOM') {
                        // Calls a custom class to validate this record
                        if($field["datasource"]['class'] != '' and $field["datasource"]['function'] != '') {
                                $datasource_class = $field["datasource"]['class'];
                                $datasource_function = $field["datasource"]['function'];
                                $app->uses($datasource_class);
                                $values = $app->$datasource_class->$datasource_function($field, $record);
                        } else {
                                $this->errorMessage .= "Custom datasource class or function is empty<br>\r\n";
                        }
                }

                return $values;

        }

        /**
        * Converts the data in a format to store it in the database table
        *
        * @param record = Datensatz als Array
        * @return record
        */
        function encode($record) {

                if(is_array($record)) {
                        foreach($this->formDef['fields'] as $key => $field) {

                                if(isset($field['validators']) && is_array($field['validators'])) $this->validateField($key, (isset($record[$key]))?$record[$key]:'', $field['validators']);

                                switch ($field['datatype']) {
                                case 'VARCHAR':
                                        if(!@is_array($record[$key])) {
                                                $new_record[$key] = (isset($record[$key]))?mysql_real_escape_string($record[$key]):'';
                                        } else {
                                                $new_record[$key] = implode($field['separator'],$record[$key]);
                                        }
                                break;
                                case 'TEXT':
                                        if(!is_array($record[$key])) {
                                                $new_record[$key] = mysql_real_escape_string($record[$key]);
                                        } else {
                                                $new_record[$key] = implode($field['separator'],$record[$key]);
                                        }
                                break;
                                case 'DATETSTAMP':
                                        if($record[$key] > 0) {
                                                list($tag,$monat,$jahr) = explode('.',$record[$key]);
                                                $new_record[$key] = mktime(0,0,0,$monat,$tag,$jahr);
                                        } else {
											$new_record[$key] = 0;
										}
                                break;
								case 'DATE':
                                        if($record[$key] != '' && $record[$key] != '0000-00-00') {
												if(function_exists('date_parse_from_format')) {
													$date_parts = date_parse_from_format($this->dateformat,$record[$key]);
													//list($tag,$monat,$jahr) = explode('.',$record[$key]);
													$new_record[$key] = $date_parts['year'].'-'.$date_parts['month'].'-'.$date_parts['day'];
													//$tmp = strptime($record[$key],$this->dateformat);
													//$new_record[$key] = ($tmp['tm_year']+1900).'-'.($tmp['tm_mon']+1).'-'.$tmp['tm_mday'];
												} else {
													//$tmp = strptime($record[$key],$this->dateformat);
													//$new_record[$key] = ($tmp['tm_year']+1900).'-'.($tmp['tm_mon']+1).'-'.$tmp['tm_mday'];
													$tmp = strtotime($record[$key]);
													$new_record[$key] = date('Y-m-d',$tmp);
												}
                                        } else {
											$new_record[$key] = '0000-00-00';
										}
                                break;
                                case 'INTEGER':
                                        $new_record[$key] = (isset($record[$key]))?intval($record[$key]):0;
                                        //if($new_record[$key] != $record[$key]) $new_record[$key] = $field['default'];
                                        //if($key == 'refresh') die($record[$key]);
                                break;
                                case 'DOUBLE':
                                        $new_record[$key] = mysql_real_escape_string($record[$key]);
                                break;
                                case 'CURRENCY':
                                        $new_record[$key] = str_replace(",",".",$record[$key]);
                                break;
                                
                                case 'DATETIME':
                                		if (is_array($record[$key]))
                                		{
	                                		$filtered_values = array_map(create_function('$item','return (int)$item;'), $record[$key]);
                                			extract($filtered_values, EXTR_PREFIX_ALL, '_dt');
                                			
                                			if ($_dt_day != 0 && $_dt_month != 0 && $_dt_year != 0) {
	                                			$new_record[$key] = date( 'Y-m-d H:i:s', mktime($_dt_hour, $_dt_minute, $_dt_second, $_dt_month, $_dt_day, $_dt_year) );
	                                		}
                                		}
                                break;
                                }

                                // The use of the field value is deprecated, use validators instead
                                if(isset($field['regex']) && $field['regex'] != '') {
                                        // Enable that "." matches also newlines
                                        $field['regex'] .= 's';
                                        if(!preg_match($field['regex'], $record[$key])) {
                                                $errmsg = $field['errmsg'];
                                                $this->errorMessage .= $errmsg."\r\n";
                                        }
                                }


                        }
                }
                return $new_record;
        }

        /**
        * process the validators for a given field.
        *
        * @param field_name = Name of the field
        * @param field_value = value of the field
        * @param validatoors = Array of validators
        * @return record
        */

        function validateField($field_name, $field_value, $validators) {

                global $app;
				
				$escape = '`';
				
                // loop trough the validators
                foreach($validators as $validator) {

                        switch ($validator['type']) {
                                case 'REGEX':
                                        $validator['regex'] .= 's';
                                        if(!preg_match($validator['regex'], $field_value)) {
                                                $errmsg = $validator['errmsg'];
                                                if(isset($this->wordbook[$errmsg])) {
                                                	$this->errorMessage .= $this->wordbook[$errmsg]."<br />\r\n";
												} else {
													$this->errorMessage .= $errmsg."<br />\r\n";
												}
                                        }
                                break;
                                case 'UNIQUE':
                                        if($this->action == 'NEW') {
                                                $num_rec = $app->db->queryOneRecord("SELECT count(*) as number FROM ".$escape.$this->formDef['db_table'].$escape. " WHERE $field_name = '".$app->db->quote($field_value)."'");
                                                if($num_rec["number"] > 0) {
                                                        $errmsg = $validator['errmsg'];
														if(isset($this->wordbook[$errmsg])) {
                                                        	$this->errorMessage .= $this->wordbook[$errmsg]."<br />\r\n";
														} else {
															$this->errorMessage .= $errmsg."<br />\r\n";
														}
                                                }
                                        } else {
                                                $num_rec = $app->db->queryOneRecord("SELECT count(*) as number FROM ".$escape.$this->formDef['db_table'].$escape. " WHERE $field_name = '".$app->db->quote($field_value)."' AND ".$this->formDef['db_table_idx']." != ".$this->primary_id);
                                                if($num_rec["number"] > 0) {
                                                        $errmsg = $validator['errmsg'];
                                                        if(isset($this->wordbook[$errmsg])) {
                                                        	$this->errorMessage .= $this->wordbook[$errmsg]."<br />\r\n";
														} else {
															$this->errorMessage .= $errmsg."<br />\r\n";
														}
                                                }
                                        }
                                break;
                                case 'NOTEMPTY':
                                        if(empty($field_value)) {
                                                $errmsg = $validator['errmsg'];
                                                if(isset($this->wordbook[$errmsg])) {
                                                    $this->errorMessage .= $this->wordbook[$errmsg]."<br />\r\n";
												} else {
													$this->errorMessage .= $errmsg."<br />\r\n";
												}
                                        }
                                break;
                                case 'ISEMAIL':
                                        if(!preg_match("/^\w+[\w\.\-\+]*\w{0,}@\w+[\w.-]*\w+\.[a-zA-Z0-9\-]{2,30}$/i", $field_value)) {
                                                $errmsg = $validator['errmsg'];
                                                if(isset($this->wordbook[$errmsg])) {
                                                    $this->errorMessage .= $this->wordbook[$errmsg]."<br />\r\n";
												} else {
													$this->errorMessage .= $errmsg."<br />\r\n";
												}
                                        }
                                break;
                                case 'ISINT':
                                        $tmpval = intval($field_value);
                                        if($tmpval === 0 and !empty($field_value)) {
                                                $errmsg = $validator['errmsg'];
                                                if(isset($this->wordbook[$errmsg])) {
                                                    $this->errorMessage .= $this->wordbook[$errmsg]."<br />\r\n";
												} else {
													$this->errorMessage .= $errmsg."<br />\r\n";
												}
                                        }
                                break;
                                case 'ISPOSITIVE':
                                        if(!is_numeric($field_value) || $field_value <= 0){
                                          $errmsg = $validator['errmsg'];
                                          if(isset($this->wordbook[$errmsg])) {
                                             $this->errorMessage .= $this->wordbook[$errmsg]."<br />\r\n";
										  } else {
											 $this->errorMessage .= $errmsg."<br />\r\n";
										  }
                                        }
                                break;
								case 'ISIPV4':
								$vip=1;
								if(preg_match("/^[0-9]{1,3}(\.)[0-9]{1,3}(\.)[0-9]{1,3}(\.)[0-9]{1,3}$/", $field_value)){
								$groups=explode(".",$field_value);
								foreach($groups as $group){
									if($group<0 OR $group>255)
									$vip=0;
								}
								}else{$vip=0;}
                                        if($vip==0) {
										$errmsg = $validator['errmsg'];
                                          if(isset($this->wordbook[$errmsg])) {
                                             $this->errorMessage .= $this->wordbook[$errmsg]."<br />\r\n";
										  } else {
											 $this->errorMessage .= $errmsg."<br />\r\n";
										  }
										}
                                break;
								case 'ISIP':
								//* Check if its a IPv4 or IPv6 address
								if(function_exists('filter_var')) {
									if(!filter_var($field_value,FILTER_VALIDATE_IP)) {
										$errmsg = $validator['errmsg'];
										if(isset($this->wordbook[$errmsg])) {
											$this->errorMessage .= $this->wordbook[$errmsg]."<br />\r\n";
										} else {
											$this->errorMessage .= $errmsg."<br />\r\n";
										}
									}
								} else {
									//* Check content with regex, if we use php < 5.2
									$ip_ok = 0;
									if(preg_match("/^(\:\:([a-f0-9]{1,4}\:){0,6}?[a-f0-9]{0,4}|[a-f0-9]{1,4}(\:[a-f0-9]{1,4}){0,6}?\:\:|[a-f0-9]{1,4}(\:[a-f0-9]{1,4}){1,6}?\:\:([a-f0-9]{1,4}\:){1,6}?[a-f0-9]{1,4})(\/\d{1,3})?$/i", $field_value)){
										$ip_ok = 1;
									}
									if(preg_match("/^[0-9]{1,3}(\.)[0-9]{1,3}(\.)[0-9]{1,3}(\.)[0-9]{1,3}$/", $field_value)){
										$ip_ok = 1;
									}
									if($ip_ok == 0) {
										$errmsg = $validator['errmsg'];
										if(isset($this->wordbook[$errmsg])) {
											$this->errorMessage .= $this->wordbook[$errmsg]."<br />\r\n";
										} else {
											$this->errorMessage .= $errmsg."<br />\r\n";
										}
									}
								}
                                break;
                                case 'CUSTOM':
                                        // Calls a custom class to validate this record
                                        if($validator['class'] != '' and $validator['function'] != '') {
                                                $validator_class = $validator['class'];
                                                $validator_function = $validator['function'];
                                                $app->uses($validator_class);
                                                $this->errorMessage .= $app->$validator_class->$validator_function($field_name, $field_value, $validator);
                                        } else {
                                                $this->errorMessage .= "Custom validator class or function is empty<br />\r\n";
                                        }
                                break;
								default:
									$this->errorMessage .= "Unknown Validator: ".$validator['type'];
								break;
                        }


                }

                return true;
        }

        /**
        * Create SQL statement
        *
        * @param record = Datensatz als Array
        * @param action = INSERT oder UPDATE
        * @param primary_id
        * @return record
        */
        function getSQL($record, $action = 'INSERT', $primary_id = 0, $sql_ext_where = '') {

                global $app;

                $this->action = $action;
                $this->primary_id = $primary_id;

                $record = $this->encode($record,$tab);
                $sql_insert_key = '';
                $sql_insert_val = '';
                $sql_update = '';

                if(!is_array($this->formDef)) $app->error("No form definition found.");

                // gehe durch alle Felder des Tabs
                if(is_array($record)) {
                foreach($this->formDef['fields'] as $key => $field) {
                                // Wenn es kein leeres Passwortfeld ist
                                if (!($field['formtype'] == 'PASSWORD' and $record[$key] == '')) {
                                        // Erzeuge Insert oder Update Quelltext
                                        if($action == "INSERT") {
                                                if($field['formtype'] == 'PASSWORD') {
                                                        $sql_insert_key .= "`$key`, ";
                                                        if($field['encryption'] == 'CRYPT') {
																$record[$key] = $app->auth->crypt_password(stripslashes($record[$key]));
																$sql_insert_val .= "'".$app->db->quote($record[$key])."', ";
														} elseif ($field['encryption'] == 'MYSQL') {
																$sql_insert_val .= "PASSWORD('".$app->db->quote($record[$key])."'), ";
														} elseif ($field['encryption'] == 'CLEARTEXT') {
																$sql_insert_val .= "'".$app->db->quote($record[$key])."', ";
                                                        } else {
                                                                $record[$key] = md5(stripslashes($record[$key]));
																$sql_insert_val .= "'".$app->db->quote($record[$key])."', ";
                                                        }
                                                } elseif ($field['formtype'] == 'CHECKBOX') {
                                                        $sql_insert_key .= "`$key`, ";
														if($record[$key] == '') {
															// if a checkbox is not set, we set it to the unchecked value
															$sql_insert_val .= "'".$field['value'][0]."', ";
															$record[$key] = $field['value'][0];
														} else {
															$sql_insert_val .= "'".$record[$key]."', ";
														}
                                                } else {
                                                        $sql_insert_key .= "`$key`, ";
                                                        $sql_insert_val .= "'".$record[$key]."', ";
                                                }
                                        } else {
										
                                                if($field['formtype'] == 'PASSWORD') {
														if(isset($field['encryption']) && $field['encryption'] == 'CRYPT') {
                                                                $record[$key] = $app->auth->crypt_password(stripslashes($record[$key]));
																$sql_update .= "`$key` = '".$app->db->quote($record[$key])."', ";
														} elseif (isset($field['encryption']) && $field['encryption'] == 'MYSQL') {
																$sql_update .= "`$key` = PASSWORD('".$app->db->quote($record[$key])."'), ";
														} elseif (isset($field['encryption']) && $field['encryption'] == 'CLEARTEXT') {
																$sql_update .= "`$key` = '".$app->db->quote($record[$key])."', ";
                                                        } else {
                                                                $record[$key] = md5(stripslashes($record[$key]));
																$sql_update .= "`$key` = '".$app->db->quote($record[$key])."', ";
                                                        }
                                                } elseif ($field['formtype'] == 'CHECKBOX') {
														if($record[$key] == '') {
															// if a checkbox is not set, we set it to the unchecked value
															$sql_update .= "`$key` = '".$field['value'][0]."', ";
															$record[$key] = $field['value'][0];
														} else {
															$sql_update .= "`$key` = '".$record[$key]."', ";
														}
                                                } else {
                                                        $sql_update .= "`$key` = '".$record[$key]."', ";
                                                }
                                        }
                                } else {
									// we unset the password filed, if empty to tell the datalog function 
									// that the password has not been changed
								    unset($record[$key]);
								}
                        }
        }



                if(stristr($this->formDef['db_table'],'.')) {
                        $escape = '';
                } else {
                        $escape = '`';
                }


                if($action == "INSERT") {
                        if($this->formDef['auth'] == 'yes') {
                                // Setze User und Gruppe
                                $sql_insert_key .= "`sys_userid`, ";
                                $sql_insert_val .= ($this->formDef["auth_preset"]["userid"] > 0)?"'".$this->formDef["auth_preset"]["userid"]."', ":"'".$this->sys_userid."', ";
                                $sql_insert_key .= "`sys_groupid`, ";
                                $sql_insert_val .= ($this->formDef["auth_preset"]["groupid"] > 0)?"'".$this->formDef["auth_preset"]["groupid"]."', ":"'".$this->sys_default_group."', ";
                                $sql_insert_key .= "`sys_perm_user`, ";
                                $sql_insert_val .= "'".$this->formDef["auth_preset"]["perm_user"]."', ";
                                $sql_insert_key .= "`sys_perm_group`, ";
                                $sql_insert_val .= "'".$this->formDef["auth_preset"]["perm_group"]."', ";
                                $sql_insert_key .= "`sys_perm_other`, ";
                                $sql_insert_val .= "'".$this->formDef["auth_preset"]["perm_other"]."', ";
                        }
                        $sql_insert_key = substr($sql_insert_key,0,-2);
                        $sql_insert_val = substr($sql_insert_val,0,-2);
                        $sql = "INSERT INTO ".$escape.$this->formDef['db_table'].$escape." ($sql_insert_key) VALUES ($sql_insert_val)";
                } else {
                        if($primary_id != 0) {
                                $sql_update = substr($sql_update,0,-2);
                                $sql = "UPDATE ".$escape.$this->formDef['db_table'].$escape." SET ".$sql_update." WHERE ".$this->formDef['db_table_idx']." = ".$primary_id;
                                if($sql_ext_where != '') $sql .= " and ".$sql_ext_where;
                        } else {
                                $app->error("Primary ID fehlt!");
                        }
                }
                
                return $sql;
        }
		
		function getDeleteSQL($primary_id) {
			
			if(stristr($this->formDef['db_table'],'.')) {
				$escape = '';
			} else {
				$escape = '`';
			}
			
			$sql = "DELETE FROM ".$escape.$this->formDef['db_table'].$escape." WHERE ".$this->formDef['db_table_idx']." = ".$primary_id;
			return $sql;
		}


		function getDataRecord($primary_id) {
			global $app;
			$escape = '`';
			if(@is_numeric($primary_id)) {
				$sql = "SELECT * FROM ".$escape.$this->formDef['db_table'].$escape." WHERE ".$this->formDef['db_table_idx']." = ".$primary_id;
            	return $app->db->queryOneRecord($sql);
			} elseif (@is_array($primary_id)) {
				$sql_where = '';
				foreach($primary_id as $key => $val) {
					$key = $app->db->quote($key);
					$val = $app->db->quote($val);
					if(stristr($val,'%')) {
						$sql_where .= "$key like '$val' AND ";
					} else {
						$sql_where .= "$key = '$val' AND ";
					}
				}
				$sql_where = substr($sql_where,0,-5);
				$sql = "SELECT * FROM ".$escape.$this->formDef['db_table'].$escape." WHERE ".$sql_where;
				return $app->db->queryAllRecords($sql);
			} else {
				$this->errorMessage = 'The ID must be either an integer or an array.';
				return array();
			}
			
			
		}

		function ispconfig_sysuser_add($params,$insert_id){
			global $conf,$app,$sql1;
			$username = $app->db->quote($params["username"]);
			$password = $app->db->quote($params["password"]);
			if(!isset($params['modules'])) {
				$modules = $conf['interface_modules_enabled'];
			} else {
				$modules = $app->db->quote($params['modules']);
			}
			if(!isset($params['startmodule'])) {			
				$startmodule = 'dashboard';
			} else {						
				$startmodule = $app->db->quote($params["startmodule"]);
				if(!preg_match('/'.$startmodule.'/',$modules)) {
					$_modules = explode(',',$modules);
					$startmodule=$_modules[0];
				}
			}
			$usertheme = $app->db->quote($params["usertheme"]);
			$type = 'user';
			$active = 1;
			$insert_id = intval($insert_id);
			$language = $app->db->quote($params["language"]);
			$groupid = $app->db->datalogInsert('sys_group', "(name,description,client_id) VALUES ('$username','','$insert_id')", 'groupid');
			$groups = $groupid;
			$password = $app->auth->crypt_password(stripslashes($password));
			$sql1 = "INSERT INTO sys_user (username,passwort,modules,startmodule,app_theme,typ,active,language,groups,default_group,client_id)
			VALUES ('$username','$password','$modules','$startmodule','$usertheme','$type','$active','$language',$groups,$groupid,$insert_id)";
			$app->db->query($sql1);
		}
		
		function ispconfig_sysuser_update($params,$client_id){
			global $app;
			$username = $app->db->quote($params["username"]);
			$clear_password = $app->db->quote($params["password"]);
			$client_id = intval($client_id);
			$password = $app->auth->crypt_password(stripslashes($clear_password));
			if ($clear_password) $pwstring = ", passwort = '$password'"; else $pwstring ="" ;
			$sql = "UPDATE sys_user set username = '$username' $pwstring WHERE client_id = $client_id";
			$app->db->query($sql);
		}
		
		function ispconfig_sysuser_delete($client_id){
			global $app;
			$client_id = intval($client_id);
			$sql = "DELETE FROM sys_user WHERE client_id = $client_id";
			$app->db->query($sql);
			$sql = "DELETE FROM sys_group WHERE client_id = $client_id";
			$app->db->query($sql);
		}

        function datalogSave($action,$primary_id, $record_old, $record_new) {
                global $app,$conf;
				
				$app->db->datalogSave($this->formDef['db_table'], $action, $this->formDef['db_table_idx'], $primary_id, $record_old, $record_new);
				return true;
				/*

                if(stristr($this->formDef['db_table'],'.')) {
                        $escape = '';
                } else {
                        $escape = '`';
                }

                $diffrec = array();
				
                if(is_array($record_new) && count($record_new) > 0) {
                        foreach($record_new as $key => $val) {
                                if($record_old[$key] != $val) {
										// Record has changed
                                        $diffrec[$key] = array('old' => $record_old[$key],
                                                               'new' => $val);
                                }
                        }
                } elseif(is_array($record_old)) {
                        foreach($record_old as $key => $val) {
                                if($record_new[$key] != $val) {
										// Record has changed
                                        $diffrec[$key] = array('new' => $record_new[$key],
                                                               'old' => $val);
                                }
                        }
                }
				$this->diffrec = $diffrec;
				
				
				// Full diff records for ISPConfig, they have a different format then the simple diffrec
				$diffrec_full = array();

                if(is_array($record_old) && count($record_old) > 0) {
                        foreach($record_old as $key => $val) {
                                if(isset($record_new[$key]) && $record_new[$key] != $val) {
                                    // Record has changed
									$diffrec_full['old'][$key] = $val;
									$diffrec_full['new'][$key] = $record_new[$key];
                                } else {
									$diffrec_full['old'][$key] = $val;
									$diffrec_full['new'][$key] = $val;
								}
                        }
                } elseif(is_array($record_new)) {
                        foreach($record_new as $key => $val) {
                                if(isset($record_new[$key]) && $record_old[$key] != $val) {
                                    // Record has changed
									$diffrec_full['new'][$key] = $val;
									$diffrec_full['old'][$key] = $record_old[$key];
                                } else {
									$diffrec_full['new'][$key] = $val;
									$diffrec_full['old'][$key] = $val;
								}
                        }
                }
				
				
				// Insert the server_id, if the record has a server_id
				$server_id = (isset($record_old["server_id"]) && $record_old["server_id"] > 0)?$record_old["server_id"]:0;
				if(isset($record_new["server_id"])) $server_id = $record_new["server_id"];

                if(count($this->diffrec) > 0) {
						$diffstr = $app->db->quote(serialize($diffrec_full));
                        $username = $app->db->quote($this->sys_username);
                        $dbidx = $this->formDef['db_table_idx'].":".$primary_id;
                        // $action = ($action == 'INSERT')?'i':'u';
						
						if($action == 'INSERT') $action = 'i';
						if($action == 'UPDATE') $action = 'u';
						if($action == 'DELETE') $action = 'd';
                        $sql = "INSERT INTO sys_datalog (dbtable,dbidx,server_id,action,tstamp,user,data) VALUES ('".$this->formDef['db_table']."','$dbidx','$server_id','$action','".time()."','$username','$diffstr')";
						$app->db->query($sql);
                }

                return true;
				*/

        }

}

?>
