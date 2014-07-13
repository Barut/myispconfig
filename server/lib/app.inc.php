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

//* Set timezone
if(isset($conf['timezone']) && $conf['timezone'] != '') date_default_timezone_set($conf['timezone']);

class app {
		
	var $loaded_modules = array();
	var $loaded_plugins = array();
        
	function app() {

                global $conf;

                if($conf['start_db'] == true) {
                	$this->load('db_'.$conf['db_type']);
                	$this->db = new db;
					if($this->db->linkId) $this->db->closeConn();
					$this->db->dbHost = $conf['db_host'];
					$this->db->dbName = $conf['db_database'];
					$this->db->dbUser = $conf['db_user'];
					$this->db->dbPass = $conf['db_password'];
					
					/*
					Initialize the connection to the master DB, 
					if we are in a multiserver setup
					*/
					
					if($conf['dbmaster_host'] != '' && ($conf['dbmaster_host'] != $conf['db_host'] || ($conf['dbmaster_host'] == $conf['db_host'] && $conf['dbmaster_database'] != $conf['db_database']))) {
						$this->dbmaster = new db;
						if($this->dbmaster->linkId) $this->dbmaster->closeConn();
						$this->dbmaster->dbHost = $conf['dbmaster_host'];
						$this->dbmaster->dbName = $conf['dbmaster_database'];
						$this->dbmaster->dbUser = $conf['dbmaster_user'];
						$this->dbmaster->dbPass = $conf['dbmaster_password'];
					} else {
						$this->dbmaster = $this->db;
					}
					
					
                }

        }

        function uses($classes) {

		global $conf;

		$cl = explode(',',$classes);
		if(is_array($cl)) {
			foreach($cl as $classname) {
				if(!@is_object($this->$classname)) {
					if(is_file($conf['classpath'].'/'.$classname.'.inc.php') && (DEVSYSTEM ||  !is_link($conf['classpath'].'/'.$classname.'.inc.php'))) {
						include_once($conf['classpath'].'/'.$classname.'.inc.php');
						$this->$classname = new $classname;
					}
				}
			}
		}
        }

        function load($classes) {

		global $conf;

		$cl = explode(',',$classes);
		if(is_array($cl)) {
			foreach($cl as $classname) {
				if(is_file($conf['classpath'].'/'.$classname.'.inc.php') && (DEVSYSTEM || !is_link($conf['classpath'].'/'.$classname.'.inc.php'))) {
					include_once($conf['classpath'].'/'.$classname.'.inc.php');
				} else {
					die('Unable to load: '.$conf['classpath'].'/'.$classname.'.inc.php');
				}
			}
		}
        }

        /*
         0 = DEBUG
         1 = WARNING
         2 = ERROR
        */

        function log($msg, $priority = 0) {
				
		global $conf;

		if($priority >= $conf['log_priority']) {
                        //if (is_writable($conf["log_file"])) {
                            if (!$fp = fopen ($conf['log_file'], 'a')) {
                                die('Unable to open logfile.');
                            }
						switch ($priority) {
							case 0:
								$priority_txt = 'DEBUG';
								break;
							case 1:
								$priority_txt = 'WARNING';
								break;
							case 2:
								$priority_txt = 'ERROR';
								break;
						}
							
                            if (!fwrite($fp, @date('d.m.Y-H:i').' - '.$priority_txt.' - '. $msg.PHP_EOL)) {
                                die('Unable to write to logfile.');
                            }
				echo @date('d.m.Y-H:i').' - '.$priority_txt.' - '. $msg.PHP_EOL;
				fclose($fp);

					// Log to database
					if(isset($this->dbmaster)) {
						$server_id = $conf['server_id'];
						$loglevel = $priority;
						$tstamp = time();
						$message = $this->dbmaster->quote($msg);
						$datalog_id = (isset($this->modules->current_datalog_id) && $this->modules->current_datalog_id > 0)?$this->modules->current_datalog_id:0;
						if($datalog_id > 0) {
							$tmp_rec = $this->dbmaster->queryOneRecord("SELECT count(syslog_id) as number FROM sys_log WHERE datalog_id = $datalog_id AND loglevel = ".LOGLEVEL_ERROR);
							//* Do not insert duplicate errors into the web log.
							if($tmp_rec['number'] == 0) {
								$sql = "INSERT INTO sys_log (server_id,datalog_id,loglevel,tstamp,message) VALUES ('$server_id',$datalog_id,'$loglevel','$tstamp','$message')";
								$this->dbmaster->query($sql);
							}
						} else {
							$sql = "INSERT INTO sys_log (server_id,datalog_id,loglevel,tstamp,message) VALUES ('$server_id',0,'$loglevel','$tstamp','$message')";
							$this->dbmaster->query($sql);
						}
					}

                        //} else {
                        //    die("Unable to write to logfile.");
                        //}
                } // if
        } // func

        /*
         0 = DEBUG
         1 = WARNING
         2 = ERROR
        */

        function error($msg) {
        	$this->log($msg,3);
		die($msg);
        }

        function log_array($data){
        	if (is_array($data)){
        		$this->log('Array data log');
        		foreach ($data as $key=>$value){
        			$this->log('---'.$key.'='.$value, 0);
        		}
        	}
        }
}

/*
 Initialize application (app) object
*/

$app = new app;

?>
