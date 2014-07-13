<?php

/*
	Form Definition

	Tabledefinition

	Datatypes:
	- INTEGER (Forces the input to Int)
	- DOUBLE
	- CURRENCY (Formats the values to currency notation)
	- VARCHAR (no format check, maxlength: 255)
	- TEXT (no format check)
	- DATE (Dateformat, automatic conversion to timestamps)

	Formtype:
	- TEXT (Textfield)
	- TEXTAREA (Textarea)
	- PASSWORD (Password textfield, input is not shown when edited)
	- SELECT (Select option field)
	- RADIO
	- CHECKBOX
	- CHECKBOXARRAY
	- FILE

	VALUE:
	- Wert oder Array

	Hint:
	The ID field of the database table is not part of the datafield definition.
	The ID field must be always auto incement (int or bigint).


*/

$form["title"] 			= "Shell User";
$form["description"] 	= "";
$form["name"] 			= "shell_user";
$form["action"]			= "shell_user_edit.php";
$form["db_table"]		= "shell_user";
$form["db_table_idx"]	= "shell_user_id";
$form["db_history"]		= "yes";
$form["tab_default"]	= "shell";
$form["list_default"]	= "shell_user_list.php";
$form["auth"]			= 'yes'; // yes / no

$form["auth_preset"]["userid"]  = 0; // 0 = id of the user, > 0 id must match with id of current user
$form["auth_preset"]["groupid"] = 0; // 0 = default groupid of the user, > 0 id must match with groupid of current user
$form["auth_preset"]["perm_user"] = 'riud'; //r = read, i = insert, u = update, d = delete
$form["auth_preset"]["perm_group"] = 'riud'; //r = read, i = insert, u = update, d = delete
$form["auth_preset"]["perm_other"] = ''; //r = read, i = insert, u = update, d = delete

$form["tabs"]['shell'] = array (
	'title' 	=> "Shell User",
	'width' 	=> 100,
	'template' 	=> "templates/shell_user_edit.htm",
	'fields' 	=> array (
	##################################
	# Begin Datatable fields
	##################################
		'server_id' => array (
			'datatype'	=> 'INTEGER',
			'formtype'	=> 'SELECT',
			'default'	=> '',
			'datasource'	=> array ( 	'type'	=> 'SQL',
										'querystring' => 'SELECT server_id,server_name FROM server WHERE mirror_server_id = 0 AND {AUTHSQL} ORDER BY server_name',
										'keyfield'=> 'server_id',
										'valuefield'=> 'server_name'
									 ),
			'value'		=> ''
		),
		'parent_domain_id' => array (
			'datatype'	=> 'INTEGER',
			'formtype'	=> 'SELECT',
			'default'	=> '',
			'datasource'	=> array ( 	'type'	=> 'SQL',
										'querystring' => "SELECT domain_id,domain FROM web_domain WHERE type = 'vhost' AND {AUTHSQL} ORDER BY domain",
										'keyfield'=> 'domain_id',
										'valuefield'=> 'domain'
									 ),
			'value'		=> ''
		),
		'username' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'validators'	=> array ( 	0 => array (	'type'	=> 'UNIQUE',
														'errmsg'=> 'username_error_unique'),
										1 => array (	'type'	=> 'REGEX',
														'regex' => '/^[\w\.\-]{0,64}$/',
														'errmsg'=> 'username_error_regex'),
									),
			'default'	=> '',
			'value'		=> '',
			'width'		=> '30',
			'maxlength'	=> '255'
		),
		'password' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'PASSWORD',
			'encryption' => 'CRYPT',
			'default'	=> '',
			'value'		=> '',
			'width'		=> '30',
			'maxlength'	=> '255'
		),
		'chroot' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'SELECT',
			'default'	=> '',
			'valuelimit' => 'client:ssh_chroot',
			'value'		=> array('no' => 'None', 'jailkit' => 'Jailkit')
		),
		'quota_size' => array (
			'datatype'	=> 'INTEGER',
			'formtype'	=> 'TEXT',
			'validators'	=> array ( 	0 => array (	'type'	=> 'NOTEMPTY',
														'errmsg'=> 'quota_size_error_empty'),
									),
			'default'	=> '-1',
			'value'		=> '',
			'width'		=> '7',
			'maxlength'	=> '7'
		),
		'active' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'CHECKBOX',
			'default'	=> 'y',
			'value'		=> array(0 => 'n',1 => 'y')
		),
		'ssh_rsa' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'default'	=> '',
			'value'		=> '',
			'maxlength'	=> '600'
		),
	##################################
	# ENDE Datatable fields
	##################################
	)
);

if($_SESSION["s"]["user"]["typ"] == 'admin') {

$form["tabs"]['advanced'] = array (
	'title' 	=> "Options",
	'width' 	=> 100,
	'template' 	=> "templates/shell_user_advanced.htm",
	'fields' 	=> array (
	##################################
	# Begin Datatable fields
	##################################
		'puser' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'validators'	=> array ( 	0 => array (	'type'	=> 'NOTEMPTY',
														'errmsg'=> 'uid_error_empty'),
									),
			'default'	=> '0',
			'value'		=> '',
			'width'		=> '30',
			'maxlength'	=> '255'
		),
		'pgroup' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'validators'	=> array ( 	0 => array (	'type'	=> 'NOTEMPTY',
														'errmsg'=> 'uid_error_empty'),
									),
			'default'	=> '0',
			'value'		=> '',
			'width'		=> '30',
			'maxlength'	=> '255'
		),
		'shell' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'default'	=> '/bin/bash',
			'value'		=> '',
			'width'		=> '30',
			'maxlength'	=> '255'
		),
		'dir' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'validators'	=> array ( 	0 => array (	'type'	=> 'NOTEMPTY',
														'errmsg'=> 'directory_error_empty'),
									),
			'default'	=> '',
			'value'		=> '',
			'width'		=> '30',
			'maxlength'	=> '255'
		),
	##################################
	# ENDE Datatable fields
	##################################
	)
);

}


?>
