<?php
/*
Copyright (c) 2007-2012, Till Brehm, projektfarm Gmbh, ISPConfig UG
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

class db {
	private $dbHost = '';		   // hostname of the MySQL server
	private $dbName = '';		   // logical database name on that server
	private $dbUser = '';		   // database authorized user
	private $dbPass = '';		   // user's password
	private $dbCharset = '';	   // what charset comes and goes to mysql: utf8 / latin1
	private $dbNewLink = false;    // Return a new linkID when connect is called again
	private $dbClientFlags = 0;    // MySQL Client falgs
	private $linkId = 0;		   // last result of mysql_connect()
	private $queryId = 0;		   // last result of mysql_query()
	private $record	= array();	   // last record fetched
	private $autoCommit = 1;	    // Autocommit Transactions
	private $currentRow;		   // current row number
	private $errorNumber = 0;	   // last error number
	public $errorMessage = '';	   // last error message
	private $errorLocation = '';   // last error location
	public $show_error_messages = false;

	public function __construct()
    {
		global $conf;
		$this->dbHost = $conf['db_host'];
		$this->dbName = $conf['db_database'];
		$this->dbUser = $conf['db_user'];
		$this->dbPass = $conf['db_password'];
		$this->dbCharset = $conf['db_charset'];
		$this->dbNewLink = $conf['db_new_link'];
		$this->dbClientFlags = $conf['db_client_flags'];
		//$this->connect();
	}

	/**  Error handler */
	public function updateError($location)
    {
		$this->errorNumber = mysql_errno();
		$this->errorMessage = mysql_error();
		$this->errorLocation = $location;
		if($this->errorNumber && $this->show_error_messages){
			echo('<br /><b>'.$this->errorLocation.'</b><br />'.$this->errorMessage);
			flush();
		}
	}

	public function connect()
	{
		if($this->linkId == 0){
			$this->linkId = mysql_connect($this->dbHost, $this->dbUser, $this->dbPass, $this->dbNewLink, $this->dbClientFlags);
			if(!$this->linkId){
				$this->updateError('DB::connect()<br />mysql_connect');
				return false;
			}
    		$this->queryId = @mysql_query('SET NAMES '.$this->dbCharset, $this->linkId);
			$this->queryId = @mysql_query("SET character_set_results = '".$this->dbCharset."', character_set_client = '".$this->dbCharset."', character_set_connection = '".$this->dbCharset."', character_set_database = '".$this->dbCharset."', character_set_server = '".$this->dbCharset."'", $this->linkId);
		}
		return true;
	}

	public function query($queryString)
	{
		if(!$this->connect()){
			return false;
		}
		if(!mysql_select_db($this->dbName, $this->linkId)){
			$this->updateError('DB::connect()<br />mysql_select_db');
			return false;
		}
		$this->queryId = @mysql_query($queryString, $this->linkId);
		$this->updateError('DB::query('.$queryString.')<br />mysql_query');
		if(!$this->queryId){
			return false;
		}
		$this->currentRow = 0;
		return $this->queryId;
	}

	/** Returns all records as an array */
	public function queryAllRecords($queryString)
	{
		if(!$this->query($queryString)){
			return false;
		}
		$ret = array();
		while($line = $this->nextRecord()){
			$ret[] = $line;
		}
		return $ret;
	}

	/** Returns one row as an array */
	public function queryOneRecord($queryString)
	{
		if(!$this->query($queryString) || $this->numRows() == 0){
			return false;
		}
		return $this->nextRecord();
	}

	/** Returns the next record as an array */
	public function nextRecord()
	{
	$this->record = mysql_fetch_assoc($this->queryId);
		$this->updateError('DB::nextRecord()<br />mysql_fetch_array');
		if(!$this->record || !is_array($this->record)){
			return false;
		}
		$this->currentRow++;
		return $this->record;
	}

	/** Returns the number of rows returned by the last select query */
	public function numRows()
    {
		return mysql_num_rows($this->queryId);
	}

	public function affectedRows()
    {
		return mysql_affected_rows($this->linkId);
	}
		
	/** Returns the last mySQL insert_id() */
	public function insertID()
	{
		return mysql_insert_id($this->linkId);
	}
        
    /** Checks a variable - Depreciated, use quote() */
    public function check($formfield)
    {
        return $this->quote($formfield);
    }
		
	/** Escapes quotes in variable. mysql_real_escape_string() */
    public function quote($formfield)
    {	
		if(!$this->connect()){
			$this->updateError('WARNING: mysql_connect: Used addslashes instead of mysql_real_escape_string');
			return addslashes($formfield);
		}
        return mysql_real_escape_string($formfield, $this->linkId);
    }
		
	/** Unquotes a variable, strip_slashes() */
    public function unquote($formfield)
    {
        return stripslashes($formfield);
    }
		
	public function toLower($record)
    {
		if(is_array($record)){
			foreach($record as $key => $val) {
				$key = strtolower($key);
				$out[$key] = $val;
			}
		}
	    return $out;
	}
       
    // deprecated
	/*
    public function insert($tablename, $form, $debug = 0)
    {
        if(is_array($form)){
	        foreach($form as $key => $value){
                $sql_key .= "$key, ";
                $sql_value .= "'".$this->check($value)."', ";
            }
            $sql_key = substr($sql_key,0,strlen($sql_key) - 2);
            $sql_value = substr($sql_value,0,strlen($sql_value) - 2);
            $sql = "INSERT INTO $tablename (".$sql_key.') VALUES ('.$sql_value.')';
            //TODO: where has $debug come from !???
            if($debug == 1){ echo "SQL-Statement: $sql<br><br>"; }
            $this->query($sql);
            if($debug == 1){ echo 'mySQL Error Message: '.$this->errorMessage; }
        }
    }
    
	// Deprecated
    public function update($tablename, $form, $bedingung, $debug = 0)
    {
	    if(is_array($form)){
            foreach($form as $key => $value){
                $insql .= "$key = '".$this->check($value)."', ";
            }
            $insql = substr($insql, 0, strlen($insql) - 2);
            $sql = "UPDATE $tablename SET " . $insql . " WHERE $bedingung";
            if($debug == 1){ echo "SQL-Statement: $sql<br><br>"; }
            $this->query($sql);
            if($debug == 1){ echo 'mySQL Error Message: '.$this->errorMessage; }
        }
    }
	*/

	public function diffrec($record_old, $record_new) {
		$diffrec_full = array();
		$diff_num = 0;

		if(is_array($record_old) && count($record_old) > 0) {
			foreach($record_old as $key => $val) {
				// if(!isset($record_new[$key]) || $record_new[$key] != $val) {
				if($record_new[$key] != $val) {
					// Record has changed
					$diffrec_full['old'][$key] = $val;
					$diffrec_full['new'][$key] = $record_new[$key];
					$diff_num++;
				} else {
					$diffrec_full['old'][$key] = $val;
					$diffrec_full['new'][$key] = $val;
				}
			}
		} elseif(is_array($record_new)) {
			foreach($record_new as $key => $val) {
				if(isset($record_new[$key]) && @$record_old[$key] != $val) {
					// Record has changed
					$diffrec_full['new'][$key] = $val;
					$diffrec_full['old'][$key] = @$record_old[$key];
					$diff_num++;
				} else {
					$diffrec_full['new'][$key] = $val;
					$diffrec_full['old'][$key] = $val;
				}
			}
		}
		
		return array('diff_num' => $diff_num, 'diff_rec' => $diffrec_full);
		
	}
	
	//** Function to fill the datalog with a full differential record.
	public function datalogSave($db_table, $action, $primary_field, $primary_id, $record_old, $record_new, $force_update = false) {
		global $app,$conf;

		//* Insert backticks only for incomplete table names.
		if(stristr($db_table,'.')) {
			$escape = '';
		} else {
			$escape = '`';
		}
		
		if($force_update == true) {
			//* We force a update even if no record has changed
			$diffrec_full = array('new' => $record_new, 'old' => $record_old);
			$diff_num = count($record_new);
		} else {
			//* get the difference record between old and new record
			$tmp = $this->diffrec($record_old, $record_new);
			$diffrec_full = $tmp['diff_rec'];
			$diff_num = $tmp['diff_num'];
			unset($tmp);
		}
		
		//* Insert the server_id, if the record has a server_id
		$server_id = (isset($record_old['server_id']) && $record_old['server_id'] > 0)?$record_old['server_id']:0;
		if(isset($record_new['server_id'])) $server_id = $record_new['server_id'];
		

		if($diff_num > 0) {
			$diffstr = $app->db->quote(serialize($diffrec_full));
			$username = $app->db->quote($_SESSION['s']['user']['username']);
			$dbidx = $primary_field.':'.$primary_id;
						
			if($action == 'INSERT') $action = 'i';
			if($action == 'UPDATE') $action = 'u';
			if($action == 'DELETE') $action = 'd';
			$sql = "INSERT INTO sys_datalog (dbtable,dbidx,server_id,action,tstamp,user,data) VALUES ('".$db_table."','$dbidx','$server_id','$action','".time()."','$username','$diffstr')";
			$app->db->query($sql);
		}

		return true;
	}
	
	//** Inserts a record and saves the changes into the datalog
	public function datalogInsert($tablename, $insert_data, $index_field) {
		global $app;
		
		if(is_array($insert_data)) {
			$key_str = '';
			$val_str = '';
			foreach($insert_data as $key => $val) {
				$key_str .= "`".$key ."`,";
				$val_str .= "'".$this->quote($val)."',";
			}
			$key_str = substr($key_str,0,-1);
			$val_str = substr($val_str,0,-1);
			$insert_data_str = '('.$key_str.') VALUES ('.$val_str.')';
		} else {
			$insert_data_str = $insert_data;
		}
		
		$old_rec = array();
		$this->query("INSERT INTO $tablename $insert_data_str");
		$index_value = $this->insertID();
		$new_rec = $this->queryOneRecord("SELECT * FROM $tablename WHERE $index_field = '$index_value'");
		$this->datalogSave($tablename, 'INSERT', $index_field, $index_value, $old_rec, $new_rec);
		
		return $index_value;
	}
	
	//** Updates a record and saves the changes into the datalog
	public function datalogUpdate($tablename, $update_data, $index_field, $index_value, $force_update = false) {
		global $app;
		
		$old_rec = $this->queryOneRecord("SELECT * FROM $tablename WHERE $index_field = '$index_value'");
		
		if(is_array($update_data)) {
			$update_data_str = '';
			foreach($update_data as $key => $val) {
				$update_data_str .= "`".$key ."` = '".$this->quote($val)."',";
			}
			$update_data_str = substr($update_data_str,0,-1);
		} else {
			$update_data_str = $update_data;
		}
		
		$this->query("UPDATE $tablename SET $update_data_str WHERE $index_field = '$index_value'");
		$new_rec = $this->queryOneRecord("SELECT * FROM $tablename WHERE $index_field = '$index_value'");
		$this->datalogSave($tablename, 'UPDATE', $index_field, $index_value, $old_rec, $new_rec, $force_update);
		
		return true;
	}
	
	//** Deletes a record and saves the changes into the datalog
	public function datalogDelete($tablename, $index_field, $index_value) {
		global $app;
		
		$old_rec = $this->queryOneRecord("SELECT * FROM $tablename WHERE $index_field = '$index_value'");
		$this->query("DELETE FROM $tablename WHERE $index_field = '$index_value'");
		$new_rec = array();
		$this->datalogSave($tablename, 'DELETE', $index_field, $index_value, $old_rec, $new_rec);
		
		return true;
	}


       
    public function closeConn()
    {
    	if($this->linkId)
    	{
    		mysql_close($this->linkId);
    		return true;
    	} else { return false; }
    }
    
    public function freeResult($query) 
    {
    	if(mysql_free_result($query))
    	{
    		return true;
    	} else {
    		return false;
    	}
    }
    
	/*
    public function delete()
    {
    }
	*/
    
	/*
    public function Transaction($action)
    {
        //action = begin, commit oder rollback
    }
	*/
    
    /** Creates a database table with the following format for the $columns array   
    * <code>
    * $columns = array(action =>   add | alter | drop
    *                  name =>     Spaltenname
    *                  name_new => neuer Spaltenname, nur bei 'alter' belegt
    *                  type =>     42go-Meta-Type: int16, int32, int64, double, char, varchar, text, blob
    *                  typeValue => Wert z.B. bei Varchar
    *                  defaultValue =>  Default Wert
    *                  notNull =>   true | false
    *                  autoInc =>   true | false
    *                  option =>   unique | primary | index)
    * </code>   
    */
	
	
    public function createTable($table_name, $columns)
    {
        $index = '';
        $sql = "CREATE TABLE $table_name (";
        foreach($columns as $col){
            $sql .= $col['name'].' '.$this->mapType($col['type'], $col['typeValue']).' ';
            //* Set default value
            if(isset($col['defaultValue']) && $col['defaultValue'] != '') {
			    if($col['defaultValue'] == 'NULL' or $col['defaultValue'] == 'NOT NULL') {
				    $sql .= 'DEFAULT '.$col['defaultValue'].' ';
			    } else {
				    $sql .= "DEFAULT '".$col['defaultValue']."' ";
			    }
		    } elseif($col['defaultValue'] != false) {
			    $sql .= "DEFAULT '' ";
		    }
		    if(isset($col['defaultValue']) && $col['defaultValue'] != 'NULL' && $col['defaultValue'] != 'NOT NULL') {
                if($col['notNull'] == true) {
                    $sql .= 'NOT NULL ';
                } else {
                    $sql .= 'NULL ';
                }
		    }
            if(isset($col['autoInc']) && $col['autoInc'] == true){ $sql .= 'auto_increment '; }
            $sql.= ',';
            //* Index Definitions
            if(isset($col['option']) && $col['option'] == 'primary'){ $index .= 'PRIMARY KEY ('.$col['name'].'),'; }
            if(isset($col['option']) && $col['option'] == 'index'){   $index .= 'INDEX ('.$col['name'].'),'; }
            if(isset($col['option']) && $col['option'] == 'unique'){  $index .= 'UNIQUE ('.$col['name'].'),'; }
       }
       $sql .= $index;
       $sql = substr($sql,0,-1);
       $sql .= ')';
       $this->query($sql);
       return true;
    }
       
    /** Changes a table definition. The format for the $columns array is 
    * <code>
    * $columns = array(action =>   add | alter | drop
    *                  name =>     Spaltenname
    *                 name_new => neuer Spaltenname, nur bei 'alter' belegt
    *                 type =>     42go-Meta-Type: int16, int32, int64, double, char, varchar, text, blob
    *                 typeValue => Wert z.B. bei Varchar
    *                 defaultValue =>  Default Wert
    *                 notNull =>   true | false
    *                 autoInc =>   true | false
    *                 option =>   unique | primary | index)
    */
    public function alterTable($table_name,$columns)
    {
       $index = '';
       $sql = "ALTER TABLE $table_name ";
       foreach($columns as $col){
            if($col['action'] == 'add'){
                $sql .= 'ADD '.$col['name'].' '.$this->mapType($col['type'],$col['typeValue']).' ';
            }elseif($col['action'] == 'alter') {
                $sql .= 'CHANGE '.$col['name'].' '.$col['name_new'].' '.$this->mapType($col['type'],$col['typeValue']).' ';
            }elseif($col['action'] == 'drop') {
                $sql .= 'DROP '.$col['name'].' ';
            }
            if($col['action'] != 'drop') {  
            if($col['defaultValue'] != '') $sql .= "DEFAULT '".$col['defaultValue']."' ";
            if($col['notNull'] == true) {
                $sql .= 'NOT NULL ';
            } else {
                $sql .= 'NULL ';
            }
            if($col['autoInc'] == true) $sql .= 'auto_increment ';
            $sql.= ',';
            //* Index definitions
            if($col['option'] == 'primary') $index .= 'PRIMARY KEY ('.$col['name'].'),';
            if($col['option'] == 'index') $index .= 'INDEX ('.$col['name'].'),';
            if($col['option'] == 'unique') $index .= 'UNIQUE ('.$col['name'].'),';
            }
       }
       $sql .= $index;
       $sql = substr($sql,0,-1);
       //die($sql);
       $this->query($sql);
       return true;
    }
       
    public function dropTable($table_name) 
    {
        $this->check($table_name);
        $sql = "DROP TABLE '". $table_name."'";
        return $this->query($sql);
    }
       
    /** Return an array of table names */
    public function getTables($database_name = '')
    {
		if($database_name == ''){
            $database_name = $this->dbName;
        }
        $result = @mysql_list_tables($database_name);
        $tb_names = array();
        for ($i = 0; $i < @mysql_num_rows($result); $i++) {
            $tb_names[$i] = @mysql_tablename($result, $i);
        }
        return $tb_names;       
    }
       
       
    public function tableInfo($table_name) {
        //* Tabellenfelder einlesen ?
        if($rows = $this->queryAllRecords('SHOW FIELDS FROM '.$table_name)){
        foreach($rows as $row) {
            $name    = $row['Field'];
            $default = $row['Default'];
            $key     = $row['Key'];
            $extra   = $row['Extra'];
            $isnull  = $row['Null'];
            $type    = $row['Type'];
        
            $column = array('name' => $name, 'defaultValue' => $default);
            //$column["type"] = $type;
            if(stristr($key, 'PRI')){ $column['option'] = 'primary'; }
            $column['notNull'] = stristr($isnull,'YES') ? false : true;
            if($extra == 'auto_increment'){ $column['autoInc'] = true; }         
            
            //* Get the Data and Metatype
            if( stristr($type, 'int(') ){    $metaType = 'int32'; }
            if( stristr($type, 'bigint') ){  $metaType = 'int64'; }
            if( stristr($type, 'char') ) {
                $metaType = 'char';
                $tmp_typeValue = explode('(',$type);
                $column['typeValue'] = substr($tmp_typeValue[1], 0, -1);  
            }
            if( stristr($type, 'varchar') ){
                $metaType = 'varchar';
                $tmp_typeValue = explode('(',$type);
                $column['typeValue'] = substr($tmp_typeValue[1], 0, -1);  
            }
            if(stristr($type,'text'))   $metaType = 'text';
            if(stristr($type,'double')) $metaType = 'double';
            if(stristr($type,'blob'))   $metaType = 'blob';
            
            $column['type'] = $metaType;
            $columns[] = $column;
        }
            return $columns;
        } else {
            return false;
        }
    }
       
    public function mapType($metaType, $typeValue) {
        //TODO: ? this is not required ?? global $go_api;
        $metaType = strtolower($metaType);
        switch ($metaType) {
        case 'int16':
            return 'smallint';
        case 'int32':
            return 'int';
        case 'int64':
            return 'bigint';
        case 'double':
            return 'double';
        case 'char':
            return 'char';
        case 'varchar':
            if($typeValue < 1) die('Datenbank Fehler: F�r diesen Datentyp ist eine L�ngenangabe notwendig.');
            return 'varchar('.$typeValue.')';
        case 'text':
            return 'text';
        case 'blob':
            return 'blob';
        }
    }

}

?>
