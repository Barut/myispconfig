<?php

/*
	Datatypes:
	- INTEGER
	- DOUBLE
	- CURRENCY
	- VARCHAR
	- TEXT
	- DATE
*/



// Name of the list
if($_SESSION['s']['user']['typ'] == 'admin') {
	$liste["name"] 				= "web_domain_admin";
} else {
	$liste["name"] 				= "web_domain";
}

// Database table
$liste["table"] 			= "web_domain";

// Index index field of the database table
$liste["table_idx"]			= "domain_id";

// Search Field Prefix
$liste["search_prefix"] 	= "search_";

// Records per page
$liste["records_per_page"] 	= "15";

// Script File of the list
$liste["file"]				= "web_domain_list.php";

// Script file of the edit form
$liste["edit_file"]			= "web_domain_edit.php";

// Script File of the delete script
$liste["delete_file"]		= "web_domain_del.php";

// Paging Template
$liste["paging_tpl"]		= "templates/paging.tpl.htm";

// Enable auth
$liste["auth"]				= "yes";


/*****************************************************
* Suchfelder
*****************************************************/

$liste["item"][] = array(	'field'		=> "domain_id",
							'datatype'	=> "INTEGER",
							'formtype'	=> "TEXT",
							'op'		=> "=",
							'prefix'	=> "",
							'suffix'	=> "",
							'width'		=> "",
							'value'		=> "");


$liste["item"][] = array(	'field'		=> "active",
							'datatype'	=> "VARCHAR",
							'formtype'	=> "SELECT",
							'op'		=> "=",
							'prefix'	=> "",
							'suffix'	=> "",
							'width'		=> "",
							'value'		=> array('y' => "<div id=\"ir-Yes\" class=\"swap\"><span>Yes</span></div>",'n' => "<div class=\"swap\" id=\"ir-No\"><span>No</span></div>"));

if($_SESSION['s']['user']['typ'] == 'admin') {
$liste["item"][] = array(	'field'		=> "sys_groupid",
							'datatype'	=> "INTEGER",
							'formtype'	=> "SELECT",
							'op'		=> "=",
							'prefix'	=> "",
							'suffix'	=> "",
							'datasource'	=> array ( 	'type'	=> 'SQL',
														'querystring' => 'SELECT groupid, name FROM sys_group WHERE groupid != 1 ORDER BY name',
														'keyfield'=> 'groupid',
														'valuefield'=> 'name'
									 				  ),
							'width'		=> "",
							'value'		=> "");
}
	
$liste["item"][] = array(	'field'		=> "server_id",
							'datatype'	=> "INTEGER",
							'formtype'	=> "SELECT",
							'op'		=> "=",
							'prefix'	=> "",
							'suffix'	=> "",
							'datasource'	=> array ( 	'type'	=> 'SQL',
														'querystring' => 'SELECT a.server_id, a.server_name FROM server a, web_domain b WHERE (a.server_id = b.server_id) AND ({AUTHSQL-B}) ORDER BY a.server_name',
														'keyfield'=> 'server_id',
														'valuefield'=> 'server_name'
									 				  ),
							'width'		=> "",
							'value'		=> "");

$liste["item"][] = array(	'field'		=> "domain",
							'datatype'	=> "VARCHAR",
							'formtype'	=> "TEXT",
							'op'		=> "like",
							'prefix'	=> "%",
							'suffix'	=> "%",
							'width'		=> "",
							'value'		=> "");


?>