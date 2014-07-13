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

$form["title"] 			= "Mailing List";
$form["description"] 	= "";
$form["name"] 			= "mail_mailinglist";
$form["action"]			= "mail_mailinglist_edit.php";
$form["db_table"]		= "mail_mailinglist";
$form["db_table_idx"]	= "mailinglist_id";
$form["db_history"]		= "yes";
$form["tab_default"]	= "mailinglist";
$form["list_default"]	= "mail_mailinglist_list.php";
$form["auth"]			= 'yes'; // yes / no

$form["auth_preset"]["userid"]  = 0; // 0 = id of the user, > 0 id must match with id of current user
$form["auth_preset"]["groupid"] = 0; // 0 = default groupid of the user, > 0 id must match with groupid of current user
$form["auth_preset"]["perm_user"] = 'riud'; //r = read, i = insert, u = update, d = delete
$form["auth_preset"]["perm_group"] = 'riud'; //r = read, i = insert, u = update, d = delete
$form["auth_preset"]["perm_other"] = ''; //r = read, i = insert, u = update, d = delete

$form["tabs"]['mailinglist'] = array (
	'title' 	=> "Mailing List",
	'width' 	=> 100,
	'template' 	=> "templates/mail_mailinglist_edit.htm",
	'fields' 	=> array (
	##################################
	# Begin Datatable fields
	##################################
		'server_id' => array (
			'datatype'	=> 'INTEGER',
			'formtype'	=> 'SELECT',
			'default'	=> '',
			'datasource'	=> array ( 	'type'	=> 'SQL',
										'querystring' => 'SELECT server_id,server_name FROM server WHERE mail_server = 1 AND mirror_server_id = 0 AND {AUTHSQL} ORDER BY server_name',
										'keyfield'=> 'server_id',
										'valuefield'=> 'server_name'
									 ),
			'value'	=> ''
		),
		'domain' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'validators'	=> array ( 	0 => array (	'type'	=> 'NOTEMPTY',
														'errmsg'=> 'domain_error_empty'),
										1 => array (	'type'	=> 'REGEX',
														'regex' => '/^[\w\.\-]{2,255}\.[a-zA-Z\-]{2,10}$/',
														'errmsg'=> 'domain_error_regex'),
									),
			'default'	=> '',
			'value'		=> '',
			'width'		=> '30',
			'maxlength'	=> '255'
		),
		'listname' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'validators'	=> array ( 	0 => array (	'type'	=> 'NOTEMPTY',
														'errmsg'=> 'listname_error_empty'),
									),
			'default'	=> '',
			'value'		=> '',
			'width'		=> '30',
			'maxlength'	=> '255'
		),
		'email' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'validators'	=> array ( 	0 => array (	'type'	=> 'ISEMAIL',
														'errmsg'=> 'email_error_isemail'),
									),
			'default'	=> '',
			'value'		=> '',
			'width'		=> '30',
			'maxlength'	=> '255'
		),
		'password' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'PASSWORD',
			'encryption'=> 'CLEARTEXT',
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
?>