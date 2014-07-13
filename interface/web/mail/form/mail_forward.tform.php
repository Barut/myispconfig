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

$form["title"] 			= "Email Forward";
$form["description"] 	= "";
$form["name"] 			= "mail_forward";
$form["action"]			= "mail_forward_edit.php";
$form["db_table"]		= "mail_forwarding";
$form["db_table_idx"]	= "forwarding_id";
$form["db_history"]		= "yes";
$form["tab_default"]	= "forward";
$form["list_default"]	= "mail_forward_list.php";
$form["auth"]			= 'yes'; // yes / no

$form["auth_preset"]["userid"]  = 0; // 0 = id of the user, > 0 id must match with id of current user
$form["auth_preset"]["groupid"] = 0; // 0 = default groupid of the user, > 0 id must match with groupid of current user
$form["auth_preset"]["perm_user"] = 'riud'; //r = read, i = insert, u = update, d = delete
$form["auth_preset"]["perm_group"] = 'riud'; //r = read, i = insert, u = update, d = delete
$form["auth_preset"]["perm_other"] = ''; //r = read, i = insert, u = update, d = delete

$form["tabs"]['forward'] = array (
	'title' 	=> "Email Forward",
	'width' 	=> 100,
	'template' 	=> "templates/mail_forward_edit.htm",
	'fields' 	=> array (
	##################################
	# Begin Datatable fields
	##################################
		'server_id' => array (
			'datatype'	=> 'INTEGER',
			'formtype'	=> 'TEXT',
			'default'	=> '',
			'value'		=> '',
			'width'		=> '30',
			'maxlength'	=> '255'
		),
		'source' => array (
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
		'destination' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'default'	=> '',
			'value'		=> '',
			'width'		=> '30',
			'maxlength'	=> '255'
		),
		'type' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'SELECT',
			'default'	=> '',
			'value'		=> array('forward'=>'Forward','alias' => 'Alias')
		),
		'active' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'CHECKBOX',
			'default'	=> 'y',
			'value'		=> array(0 => 'n',1 => 'y')
		),
	##################################
	# ENDE Datatable fields
	##################################
	)
);



?>