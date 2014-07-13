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

$form["title"] 			= "DNS Zone";
$form["description"] 	= "";
$form["name"] 			= "dns_soa";
$form["action"]			= "dns_soa_edit.php";
$form["db_table"]		= "dns_soa";
$form["db_table_idx"]	= "id";
$form["db_history"]		= "yes";
$form["tab_default"]	= "dns_soa";
$form["list_default"]	= "dns_soa_list.php";
$form["auth"]			= 'yes'; // yes / no

$form["auth_preset"]["userid"]  = 0; // 0 = id of the user, > 0 id must match with id of current user
$form["auth_preset"]["groupid"] = 0; // 0 = default groupid of the user, > 0 id must match with groupid of current user
$form["auth_preset"]["perm_user"] = 'riud'; //r = read, i = insert, u = update, d = delete
$form["auth_preset"]["perm_group"] = 'riud'; //r = read, i = insert, u = update, d = delete
$form["auth_preset"]["perm_other"] = ''; //r = read, i = insert, u = update, d = delete

$form["tabs"]['dns_soa'] = array (
	'title' 	=> "DNS Zone",
	'width' 	=> 100,
	'template' 	=> "templates/dns_soa_edit.htm",
	'fields' 	=> array (
	##################################
	# Begin Datatable fields
	##################################
		'server_id' => array (
			'datatype'	=> 'INTEGER',
			'formtype'	=> 'SELECT',
			'datasource'	=> array ( 	'type'	=> 'SQL',
										'querystring' => 'SELECT server_id,server_name FROM server WHERE mirror_server_id = 0 AND dns_server = 1 AND {AUTHSQL} ORDER BY server_name',
										'keyfield'=> 'server_id',
										'valuefield'=> 'server_name'
									 ),
			'validators'	=> array ( 	0 => array (	'type'	=> 'NOTEMPTY',
														'errmsg'=> 'server_id_error_empty'),
									),
			'default'	=> '',
			'value'		=> '',
			'width'		=> '30',
			'maxlength'	=> '255'
		),
		'origin' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'validators'	=> array ( 	0 => array (	'type'	=> 'NOTEMPTY',
														'errmsg'=> 'origin_error_empty'),
										1 => array (	'type'	=> 'UNIQUE',
														'errmsg'=> 'origin_error_unique'),
										2 => array (	'type'	=> 'REGEX',
														'regex' => '/^[\w\.\-\/]{2,255}\.[a-zA-Z0-9\-]{2,30}[\.]{0,1}$/',
														'errmsg'=> 'origin_error_regex'),
									),
			'default'	=> '',
			'value'		=> '',
			'width'		=> '30',
			'maxlength'	=> '255'
		),
		'ns' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'validators'	=> array ( 	0 => array (	'type'	=> 'REGEX',
														'regex' => '/^[\w\.\-]{1,255}$/',
														'errmsg'=> 'ns_error_regex'),
									),
			'default'	=> '',
			'value'		=> '',
			'width'		=> '30',
			'maxlength'	=> '255'
		),
		'mbox' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'validators'	=> array ( 	0 => array (	'type'	=> 'NOTEMPTY',
														'errmsg'=> 'mbox_error_empty'),
										1 => array (	'type'	=> 'REGEX',
														'regex' => '/^[[a-zA-Z0-9\.\-\_]{0,255}\.$/',
														'errmsg'=> 'mbox_error_regex'),
									),
			'default'	=> '',
			'value'		=> '',
			'width'		=> '30',
			'maxlength'	=> '255'
		),
		'serial' => array (
			'datatype'	=> 'INTEGER',
			'formtype'	=> 'TEXT',
			'default'	=> '',
			'value'		=> '',
			'width'		=> '10',
			'maxlength'	=> '10'
		),
		'refresh' => array (
			'datatype'	=> 'INTEGER',
			'formtype'	=> 'TEXT',
			'default'	=> '7200',
			'value'		=> '',
			'width'		=> '10',
			'maxlength'	=> '10'
		),
		'retry' => array (
			'datatype'	=> 'INTEGER',
			'formtype'	=> 'TEXT',
			'default'	=> '540',
			'value'		=> '',
			'width'		=> '10',
			'maxlength'	=> '10'
		),
		'expire' => array (
			'datatype'	=> 'INTEGER',
			'formtype'	=> 'TEXT',
			'default'	=> '604800',
			'value'		=> '',
			'width'		=> '10',
			'maxlength'	=> '10'
		),
		'minimum' => array (
			'datatype'	=> 'INTEGER',
			'formtype'	=> 'TEXT',
			'default'	=> '86400',
			'value'		=> '',
			'width'		=> '10',
			'maxlength'	=> '10'
		),
		'ttl' => array (
			'datatype'	=> 'INTEGER',
			'formtype'	=> 'TEXT',
			'default'	=> '3600',
			'value'		=> '',
			'width'		=> '10',
			'maxlength'	=> '10'
		),
		'xfer' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'default'	=> '',
			'value'		=> '',
			'width'		=> '30',
			'maxlength'	=> '255'
		),
		'also_notify' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
//			'validators'    => array (  0 => array (    'type'  => 'ISIPV4',
//														'errmsg'=> 'also_notify_error_regex'
//													),
//									),
			'default'	=> '',
			'value'		=> '',
			'width'		=> '30',
			'maxlength'	=> '255'
		),
		'update_acl' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'default'	=> '',
			'value'		=> '',
			'width'		=> '30',
			'maxlength'	=> '255'
		),
		'active' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'CHECKBOX',
			'default'	=> 'Y',
			'value'		=> array(0 => 'N',1 => 'Y')
		),
	##################################
	# ENDE Datatable fields
	##################################
	)
);

$form["tabs"]['dns_records'] = array (
	'title' 	=> "Records",
	'width' 	=> 100,
	'template' 	=> "templates/dns_records_edit.htm",
	'fields' 	=> array (
	##################################
	# Begin Datatable fields
	##################################
		
	##################################
	# ENDE Datatable fields
	##################################
	),
	'plugins' => array (
     	'dns_records' => array (
         	'class'   => 'plugin_listview',
     		'options' => array(
				'listdef' => 'list/dns_a.list.php',
				'sqlextwhere' => "zone = ".@intval(@$_REQUEST['id']),
				'sql_order_by' => "ORDER BY type, name"
			)
        )
	)
);



?>