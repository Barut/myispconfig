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

$form["title"] 			= "Reseller";
$form["description"] 	= "";
$form["name"] 			= "reseller";
$form["action"]			= "reseller_edit.php";
$form["db_table"]		= "client";
$form["db_table_idx"]	= "client_id";
$form["db_history"]		= "yes";
$form["tab_default"]	= "address";
$form["list_default"]	= "reseller_list.php";
$form["auth"]			= 'yes';

$form["auth_preset"]["userid"]  = 0; // 0 = id of the user, > 0 id must match with id of current user
$form["auth_preset"]["groupid"] = 0; // 0 = default groupid of the user, > 0 id must match with groupid of current user
$form["auth_preset"]["perm_user"] = 'riud'; //r = read, i = insert, u = update, d = delete
$form["auth_preset"]["perm_group"] = 'riud'; //r = read, i = insert, u = update, d = delete
$form["auth_preset"]["perm_other"] = ''; //r = read, i = insert, u = update, d = delete

//* Load themes
$themes_list = array();
$handle = @opendir(ISPC_THEMES_PATH);
while ($file = @readdir ($handle)) {
    if (substr($file, 0, 1) != '.') {
        if(@is_dir(ISPC_THEMES_PATH."/$file")) {
            $themes_list[$file] = $file;
        }
    }
}

//* Languages
$language_list = array();
$handle = @opendir(ISPC_ROOT_PATH.'/lib/lang');
while ($file = @readdir ($handle)) {
    if ($file != '.' && $file != '..') {
        if(@is_file(ISPC_ROOT_PATH.'/lib/lang/'.$file) and substr($file,-4,4) == '.lng') {
			$tmp = substr($file, 0, 2);
			$language_list[$tmp] = $tmp;
        }
	}
}

$form["tabs"]['address'] = array (
	'title' 	=> "Address",
	'width' 	=> 100,
	'template' 	=> "templates/reseller_edit_address.htm",
	'fields' 	=> array (
	##################################
	# Begin Datatable fields
	##################################
		'company_name' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'default'	=> '',
			'value'		=> '',
			'separator'	=> '',
			'width'		=> '30',
			'maxlength'	=> '255',
			'rows'		=> '',
			'cols'		=> ''
		),
		'contact_name' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'validators'	=> array ( 	0 => array (	'type'	=> 'NOTEMPTY',
														'errmsg'=> 'contact_error_empty'),
										),
			'default'	=> '',
			'value'		=> '',
			'separator'	=> '',
			'width'		=> '30',
			'maxlength'	=> '255',
			'rows'		=> '',
			'cols'		=> ''
		),
		'customer_no' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'default'	=> '',
			'value'		=> '',
			'separator'	=> '',
			'width'		=> '30',
			'maxlength'	=> '255',
			'rows'		=> '',
			'cols'		=> ''
		),
		'username' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'validators'	=> array ( 	0 => array (	'type'	=> 'NOTEMPTY',
														'errmsg'=> 'username_error_empty'),
										1 => array (	'type'	=> 'CUSTOM',
														'class' => 'validate_client',
														'function' => 'username_unique',
														'errmsg'=> 'username_error_unique'),
										2 => array (	'type'	=> 'CUSTOM',
														'class' => 'validate_client',
														'function' => 'username_collision',
														'errmsg'=> 'username_error_collision'),
										3 => array (	'type'	=> 'REGEX',
														'regex' => '/^[\w\.\-\_]{0,64}$/',
														'errmsg'=> 'username_error_regex'),
										),
			'default'	=> '',
			'value'		=> '',
			'separator'	=> '',
			'width'		=> '30',
			'maxlength'	=> '255',
			'rows'		=> '',
			'cols'		=> ''
		),
		'password' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'PASSWORD',
			'encryption'=> 'CRYPT',
			'default'	=> '',
			'value'		=> '',
			'separator'	=> '',
			'width'		=> '30',
			'maxlength'	=> '255',
			'rows'		=> '',
			'cols'		=> ''
		),
		'language' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'SELECT',
			'default'	=> $conf["language"],
			'value'		=> $language_list,
			'separator'	=> '',
			'width'		=> '30',
			'maxlength'	=> '255',
			'rows'		=> '',
			'cols'		=> ''
		),
		'usertheme' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'SELECT',
			'default'	=> $conf["theme"],
			'value'		=> $themes_list,
			'separator'	=> '',
			'width'		=> '30',
			'maxlength'	=> '255',
			'rows'		=> '',
			'cols'		=> ''
		),
		'street' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'default'	=> '',
			'value'		=> '',
			'separator'	=> '',
			'width'		=> '30',
			'maxlength'	=> '255',
			'rows'		=> '',
			'cols'		=> ''
		),
		'zip' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'default'	=> '',
			'value'		=> '',
			'separator'	=> '',
			'width'		=> '10',
			'maxlength'	=> '255',
			'rows'		=> '',
			'cols'		=> ''
		),
		'city' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'default'	=> '',
			'value'		=> '',
			'separator'	=> '',
			'width'		=> '30',
			'maxlength'	=> '255',
			'rows'		=> '',
			'cols'		=> ''
		),
		'state' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'default'	=> '',
			'value'		=> '',
			'separator'	=> '',
			'width'		=> '30',
			'maxlength'	=> '255',
			'rows'		=> '',
			'cols'		=> ''
		),
		'country' => array (
			'datatype'	=> 'VARCHAR',

			'formtype'	=> 'SELECT',
			'default'	=> $conf["country"],
			'datasource'	=> array ( 	'type'	=> 'SQL',
										'querystring' => 'SELECT iso,printable_name FROM country ORDER BY printable_name',
										'keyfield'=> 'iso',
										'valuefield'=> 'printable_name'
									 ),
			'value'		=> ''
		),
		'telephone' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'default'	=> '',
			'value'		=> '',
			'separator'	=> '',
			'width'		=> '30',
			'maxlength'	=> '255',
			'rows'		=> '',
			'cols'		=> ''
		),
		'mobile' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'default'	=> '',
			'value'		=> '',
			'separator'	=> '',
			'width'		=> '30',
			'maxlength'	=> '255',
			'rows'		=> '',
			'cols'		=> ''
		),
		'fax' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'default'	=> '',
			'value'		=> '',
			'separator'	=> '',
			'width'		=> '30',
			'maxlength'	=> '255',
			'rows'		=> '',
			'cols'		=> ''
		),
		'email' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'default'	=> '',
			'value'		=> '',
			'separator'	=> '',
			'width'		=> '30',
			'maxlength'	=> '255',
			'rows'		=> '',
			'cols'		=> ''
		),
		'internet' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'default'	=> 'http://',
			'value'		=> '',
			'separator'	=> '',
			'width'		=> '30',
			'maxlength'	=> '255',
			'rows'		=> '',
			'cols'		=> ''
		),
		'icq' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'default'	=> '',
			'value'		=> '',
			'separator'	=> '',
			'width'		=> '30',
			'maxlength'	=> '255',
			'rows'		=> '',
			'cols'		=> ''
		),
		'vat_id' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'default'	=> '',
			'value'		=> '',
			'separator'	=> '',
			'width'		=> '30',
			'maxlength'	=> '255',
			'rows'		=> '',
			'cols'		=> ''
		),
		'notes' => array (
			'datatype'	=> 'TEXT',
			'formtype'	=> 'TEXTAREA',
			'default'	=> '',
			'value'		=> '',
			'separator'	=> '',
			'width'		=> '',
			'maxlength'	=> '',
			'rows'		=> '10',
			'cols'		=> '30'
		),
	##################################
	# END Datatable fields
	##################################
	)
);

$form["tabs"]['limits'] = array (
	'title' 	=> "Limits",
	'width' 	=> 80,
	'template' 	=> "templates/reseller_edit_limits.htm",
	'fields' 	=> array (
	##################################
	# Begin Datatable fields
	##################################
		'template_master' => array (
			'datatype'	=> 'INTEGER',
			'formtype'	=> 'SELECT',
			'default'	=> '1',
			'datasource'	=> array ( 	'type'	=> 'CUSTOM',
										'class'=> 'custom_datasource',
										'function'=> 'master_templates'
									 ),
			'value'		=> ''
		),
		'template_additional' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
		),
		'default_mailserver' => array (
			'datatype'	=> 'INTEGER',
			'formtype'	=> 'SELECT',
			'default'	=> '1',
			'datasource'	=> array ( 	'type'	=> 'SQL',
										'querystring' => 'SELECT server_id,server_name FROM server WHERE mail_server = 1 AND {AUTHSQL} ORDER BY server_name',
										'keyfield'=> 'server_id',
										'valuefield'=> 'server_name'
									 ),
			'value'		=> ''
		),
		'limit_maildomain' => array (
			'datatype'	=> 'INTEGER',
			'formtype'	=> 'TEXT',
			'validators'	=> array ( 	0 => array (	'type'	=> 'ISINT',
														'errmsg'=> 'limit_maildomain_error_notint'),
									),
			'default'	=> '-1',
			'value'		=> '',
			'separator'	=> '',
			'width'		=> '10',
			'maxlength'	=> '10',
			'rows'		=> '',
			'cols'		=> ''
		),
		'limit_mailmailinglist' => array (
			'datatype'	=> 'INTEGER',
			'formtype'	=> 'TEXT',
			'validators'	=> array ( 	0 => array (	'type'	=> 'ISINT',
														'errmsg'=> 'limit_mailmailinglist_error_notint'),
									),
			'default'	=> '-1',
			'value'		=> '',
			'separator'	=> '',
			'width'		=> '10',
			'maxlength'	=> '10',
			'rows'		=> '',
			'cols'		=> ''
		),
		'limit_mailbox' => array (
			'datatype'	=> 'INTEGER',
			'formtype'	=> 'TEXT',
			'validators'	=> array ( 	0 => array (	'type'	=> 'ISINT',
														'errmsg'=> 'limit_mailbox_error_notint'),
									),
			'default'	=> '-1',
			'value'		=> '',
			'separator'	=> '',
			'width'		=> '10',
			'maxlength'	=> '10',
			'rows'		=> '',
			'cols'		=> ''
		),
		'limit_mailalias' => array (
			'datatype'	=> 'INTEGER',
			'formtype'	=> 'TEXT',
			'validators'	=> array ( 	0 => array (	'type'	=> 'ISINT',
														'errmsg'=> 'limit_mailalias_error_notint'),
									),
			'default'	=> '-1',
			'value'		=> '',
			'separator'	=> '',
			'width'		=> '10',
			'maxlength'	=> '10',
			'rows'		=> '',
			'cols'		=> ''
		),
		'limit_mailaliasdomain' => array (
			'datatype'	=> 'INTEGER',
			'formtype'	=> 'TEXT',
			'validators'	=> array ( 	0 => array (	'type'	=> 'ISINT',
														'errmsg'=> 'limit_mailaliasdomain_error_notint'),
									),
			'default'	=> '-1',
			'value'		=> '',
			'separator'	=> '',
			'width'		=> '10',
			'maxlength'	=> '10',
			'rows'		=> '',
			'cols'		=> ''
		),
		'limit_mailforward' => array (
			'datatype'	=> 'INTEGER',
			'formtype'	=> 'TEXT',
			'validators'	=> array ( 	0 => array (	'type'	=> 'ISINT',
														'errmsg'=> 'limit_mailforward_error_notint'),
									),
			'default'	=> '-1',
			'value'		=> '',
			'separator'	=> '',
			'width'		=> '10',
			'maxlength'	=> '10',
			'rows'		=> '',
			'cols'		=> ''
		),
		'limit_mailcatchall' => array (
			'datatype'	=> 'INTEGER',
			'formtype'	=> 'TEXT',
			'validators'	=> array ( 	0 => array (	'type'	=> 'ISINT',
														'errmsg'=> 'limit_mailcatchall_error_notint'),
									),
			'default'	=> '-1',
			'value'		=> '',
			'separator'	=> '',
			'width'		=> '10',
			'maxlength'	=> '10',
			'rows'		=> '',
			'cols'		=> ''
		),
		'limit_mailrouting' => array (
			'datatype'	=> 'INTEGER',
			'formtype'	=> 'TEXT',
			'validators'	=> array ( 	0 => array (	'type'	=> 'ISINT',
														'errmsg'=> 'limit_mailrouting_error_notint'),
									),
			'default'	=> '0',
			'value'		=> '',
			'separator'	=> '',
			'width'		=> '10',
			'maxlength'	=> '10',
			'rows'		=> '',
			'cols'		=> ''
		),
		'limit_mailfilter' => array (
			'datatype'	=> 'INTEGER',
			'formtype'	=> 'TEXT',
			'validators'	=> array ( 	0 => array (	'type'	=> 'ISINT',
														'errmsg'=> 'limit_mailfilter_error_notint'),
									),
			'default'	=> '-1',
			'value'		=> '',
			'separator'	=> '',
			'width'		=> '10',
			'maxlength'	=> '10',
			'rows'		=> '',
			'cols'		=> ''
		),
		'limit_fetchmail' => array (
			'datatype'	=> 'INTEGER',
			'formtype'	=> 'TEXT',
			'validators'	=> array ( 	0 => array (	'type'	=> 'ISINT',
														'errmsg'=> 'limit_mailfetchmail_error_notint'),
									),
			'default'	=> '-1',
			'value'		=> '',
			'separator'	=> '',
			'width'		=> '10',
			'maxlength'	=> '10',
			'rows'		=> '',
			'cols'		=> ''
		),
		'limit_mailquota' => array (
			'datatype'	=> 'INTEGER',
			'formtype'	=> 'TEXT',
			'validators'	=> array ( 	0 => array (	'type'	=> 'ISINT',
														'errmsg'=> 'limit_mailquota_error_notint'),
									),
			'default'	=> '-1',
			'value'		=> '',
			'separator'	=> '',
			'width'		=> '10',
			'maxlength'	=> '10',
			'rows'		=> '',
			'cols'		=> ''
		),
		'limit_spamfilter_wblist' => array (
			'datatype'	=> 'INTEGER',
			'formtype'	=> 'TEXT',
			'validators'	=> array ( 	0 => array (	'type'	=> 'ISINT',
														'errmsg'=> 'limit_spamfilter_wblist_error_notint'),
									),
			'default'	=> '-1',
			'value'		=> '',
			'separator'	=> '',
			'width'		=> '10',
			'maxlength'	=> '10',
			'rows'		=> '',
			'cols'		=> ''
		),
		'limit_spamfilter_user' => array (
			'datatype'	=> 'INTEGER',
			'formtype'	=> 'TEXT',
			'validators'	=> array ( 	0 => array (	'type'	=> 'ISINT',
														'errmsg'=> 'limit_spamfilter_user_error_notint'),
									),
			'default'	=> '-1',
			'value'		=> '',
			'separator'	=> '',
			'width'		=> '10',
			'maxlength'	=> '10',
			'rows'		=> '',
			'cols'		=> ''
		),
		'limit_spamfilter_policy' => array (
			'datatype'	=> 'INTEGER',
			'formtype'	=> 'TEXT',
			'validators'	=> array ( 	0 => array (	'type'	=> 'ISINT',
														'errmsg'=> 'limit_spamfilter_policy_error_notint'),
									),
			'default'	=> '-1',
			'value'		=> '',
			'separator'	=> '',
			'width'		=> '10',
			'maxlength'	=> '10',
			'rows'		=> '',
			'cols'		=> ''
		),
		'default_webserver' => array (
			'datatype'	=> 'INTEGER',
			'formtype'	=> 'SELECT',
			'default'	=> '1',
			'datasource'	=> array ( 	'type'	=> 'SQL',
										'querystring' => 'SELECT server_id,server_name FROM server WHERE web_server = 1 AND {AUTHSQL} ORDER BY server_name',
										'keyfield'=> 'server_id',
										'valuefield'=> 'server_name'
									 ),
			'value'		=> ''
		),
		'limit_web_domain' => array (
			'datatype'	=> 'INTEGER',
			'formtype'	=> 'TEXT',
			'validators'	=> array ( 	0 => array (	'type'	=> 'ISINT',
														'errmsg'=> 'limit_web_domain_error_notint'),
									),
			'default'	=> '-1',
			'value'		=> '',
			'separator'	=> '',
			'width'		=> '10',
			'maxlength'	=> '10',
			'rows'		=> '',
			'cols'		=> ''
		),
		'limit_web_quota' => array (
			'datatype'	=> 'INTEGER',
			'formtype'	=> 'TEXT',
			'validators'	=> array ( 	0 => array (	'type'	=> 'ISINT',
														'errmsg'=> 'limit_web_quota_error_notint'),
									),
			'default'	=> '-1',
			'value'		=> '',
			'separator'	=> '',
			'width'		=> '10',
			'maxlength'	=> '10',
			'rows'		=> '',
			'cols'		=> ''
		),
		'web_php_options' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'CHECKBOXARRAY',
			'validators'	=> array ( 	0 => array (	'type'	=> 'NOTEMPTY',
														'errmsg'=> 'web_php_options_notempty'),
									),
			'default'	=> '',
			'separator' => ',',
			'value'		=> array('no' => 'Disabled', 'fast-cgi' => 'Fast-CGI', 'cgi' => 'CGI', 'mod' => 'Mod-PHP', 'suphp' => 'SuPHP')
		),
		'limit_web_aliasdomain' => array (
			'datatype'	=> 'INTEGER',
			'formtype'	=> 'TEXT',
			'validators'	=> array ( 	0 => array (	'type'	=> 'ISINT',
														'errmsg'=> 'limit_web_aliasdomain_error_notint'),
									),
			'default'	=> '-1',
			'value'		=> '',
			'separator'	=> '',
			'width'		=> '10',
			'maxlength'	=> '10',
			'rows'		=> '',
			'cols'		=> ''
		),
		'limit_web_subdomain' => array (
			'datatype'	=> 'INTEGER',
			'formtype'	=> 'TEXT',
			'validators'	=> array ( 	0 => array (	'type'	=> 'ISINT',
														'errmsg'=> 'limit_web_subdomain_error_notint'),
									),
			'default'	=> '-1',
			'value'		=> '',
			'separator'	=> '',
			'width'		=> '10',
			'maxlength'	=> '10',
			'rows'		=> '',
			'cols'		=> ''
		),
		'limit_ftp_user' => array (
			'datatype'	=> 'INTEGER',
			'formtype'	=> 'TEXT',
			'validators'	=> array ( 	0 => array (	'type'	=> 'ISINT',
														'errmsg'=> 'limit_ftp_user_error_notint'),
									),
			'default'	=> '-1',
			'value'		=> '',
			'separator'	=> '',
			'width'		=> '10',
			'maxlength'	=> '10',
			'rows'		=> '',
			'cols'		=> ''
		),
		'limit_shell_user' => array (
			'datatype'	=> 'INTEGER',
			'formtype'	=> 'TEXT',
			'validators'	=> array ( 	0 => array (	'type'	=> 'ISINT',
														'errmsg'=> 'limit_shell_user_error_notint'),
									),
			'default'	=> '-1',
			'value'		=> '',
			'separator'	=> '',
			'width'		=> '10',
			'maxlength'	=> '10',
			'rows'		=> '',
			'cols'		=> ''
		),
		'ssh_chroot' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'CHECKBOXARRAY',
			'validators'	=> array ( 	0 => array (	'type'	=> 'NOTEMPTY',
														'errmsg'=> 'ssh_chroot_notempty'),
									),
			'default'	=> '',
			'separator' => ',',
			'value'		=> array('no' => 'None', 'jailkit' => 'Jailkit')
		),
		'limit_webdav_user' => array (
			'datatype'	=> 'INTEGER',
			'formtype'	=> 'TEXT',
			'validators'	=> array ( 	0 => array (	'type'	=> 'ISINT',
														'errmsg'=> 'limit_webdav_user_error_notint'),
									),
			'default'	=> '-1',
			'value'		=> '',
			'separator'	=> '',
			'width'		=> '10',
			'maxlength'	=> '10',
			'rows'		=> '',
			'cols'		=> ''
		),
		'default_dnsserver' => array (
			'datatype'	=> 'INTEGER',
			'formtype'	=> 'SELECT',
			'default'	=> '1',
			'datasource'	=> array ( 	'type'	=> 'SQL',
										'querystring' => 'SELECT server_id,server_name FROM server WHERE dns_server = 1 AND {AUTHSQL} ORDER BY server_name',
										'keyfield'=> 'server_id',
										'valuefield'=> 'server_name'
									 ),
			'value'		=> ''
		),
		'limit_dns_zone' => array (
			'datatype'	=> 'INTEGER',
			'formtype'	=> 'TEXT',
			'validators'	=> array ( 	0 => array (	'type'	=> 'ISINT',
														'errmsg'=> 'limit_dns_zone_error_notint'),
									),
			'default'	=> '-1',
			'value'		=> '',
			'separator'	=> '',
			'width'		=> '10',
			'maxlength'	=> '10',
			'rows'		=> '',
			'cols'		=> ''
		),
                'limit_dns_slave_zone' => array (
                        'datatype'      => 'INTEGER',
                        'formtype'      => 'TEXT',
                        'validators'    => array (      0 => array (    'type'  => 'ISINT',
                                                                                                                'errmsg'=> 'limit_dns_slave_zone_error_notint'),
                                                                        ),
                        'default'       => '-1',
                        'value'         => '',
                        'separator'     => '',
                        'width'         => '10',
                        'maxlength'     => '10',
                        'rows'          => '',
                        'cols'          => ''
                ),
		'limit_dns_record' => array (
			'datatype'	=> 'INTEGER',
			'formtype'	=> 'TEXT',
			'validators'	=> array ( 	0 => array (	'type'	=> 'ISINT',
														'errmsg'=> 'limit_dns_record_error_notint'),
									),
			'default'	=> '-1',
			'value'		=> '',
			'separator'	=> '',
			'width'		=> '10',
			'maxlength'	=> '10',
			'rows'		=> '',
			'cols'		=> ''
		),
		'limit_client' => array (
			'datatype'	=> 'INTEGER',
			'formtype'	=> 'TEXT',
			'validators'	=> array ( 	0 => array (	'type'	=> 'ISINT',
														'errmsg'=> 'limit_client_error_notint'),
										1 => array (	'type'	=> 'CUSTOM',
														'class' => 'validate_reseller',
														'function' => 'limit_client'),
									),
			'default'	=> '1',
			'value'		=> '',
			'separator'	=> '',
			'width'		=> '10',
			'maxlength'	=> '10',
			'rows'		=> '',
			'cols'		=> ''
		),
		'default_dbserver' => array (
			'datatype'	=> 'INTEGER',
			'formtype'	=> 'SELECT',
			'default'	=> '1',
			'datasource'	=> array ( 	'type'	=> 'SQL',
										'querystring' => 'SELECT server_id,server_name FROM server WHERE db_server = 1 AND {AUTHSQL} ORDER BY server_name',
										'keyfield'=> 'server_id',
										'valuefield'=> 'server_name'
									 ),
			'value'		=> ''
		),
		'limit_database' => array (
			'datatype'	=> 'INTEGER',
			'formtype'	=> 'TEXT',
			'validators'	=> array ( 	0 => array (	'type'	=> 'ISINT',
														'errmsg'=> 'limit_database_error_notint'),
									),
			'default'	=> '-1',
			'value'		=> '',
			'separator'	=> '',
			'width'		=> '10',
			'maxlength'	=> '10',
			'rows'		=> '',
			'cols'		=> ''
		),
        'limit_cron' => array (
            'datatype'  => 'INTEGER',
            'formtype'  => 'TEXT',
            'validators'    => array (  0 => array (    'type'  => 'ISINT',
                                                        'errmsg'=> 'limit_cron_error_notint'),
                                    ),
            'default'   => '0',
            'value'     => '',
            'separator' => '',
            'width'     => '10',
            'maxlength' => '10',
            'rows'      => '',
            'cols'      => ''
        ),
        'limit_cron_type' => array (
            'datatype'  => 'VARCHAR',
            'formtype'  => 'SELECT',
            'default'   => '',
            'value'     => array('full' => 'Full Cron','chrooted' => 'Chrooted Cron','url' => 'URL Cron')
        ),
        'limit_cron_frequency' => array (
            'datatype'  => 'INTEGER',
            'formtype'  => 'TEXT',
            'validators'    => array (  0 => array (    'type'  => 'ISINT',
                                                        'errmsg'=> 'limit_cron_error_frequency'),
                                    ),
            'default'   => '-1',
            'value'     => '',
            'separator' => '',
            'width'     => '10',
            'maxlength' => '10',
            'rows'      => '',
            'cols'      => ''
        ),
		'limit_traffic_quota' => array (
			'datatype'	=> 'INTEGER',
			'formtype'	=> 'TEXT',
			'validators'	=> array ( 	0 => array (	'type'	=> 'ISINT',
														'errmsg'=> 'limit_traffic_quota_error_notint'),
									),
			'default'	=> '-1',
			'value'		=> '',
			'separator'	=> '',
			'width'		=> '10',
			'maxlength'	=> '10',
			'rows'		=> '',
			'cols'		=> ''
		),
		'limit_openvz_vm' => array (
			'datatype'	=> 'INTEGER',
			'formtype'	=> 'TEXT',
			'validators'	=> array ( 	0 => array (	'type'	=> 'ISINT',
														'errmsg'=> 'limit_openvz_vm_error_notint'),
									),
			'default'	=> '0',
			'value'		=> '',
			'separator'	=> '',
			'width'		=> '10',
			'maxlength'	=> '10',
			'rows'		=> '',
			'cols'		=> ''
		),
		'limit_openvz_vm_template_id' => array (
			'datatype'	=> 'INTEGER',
			'formtype'	=> 'SELECT',
			'default'	=> '',
			'datasource'	=> array ( 	'type'	=> 'SQL',
										'querystring' => 'SELECT template_id,template_name FROM openvz_template WHERE 1 ORDER BY template_name',
										'keyfield'=> 'template_id',
										'valuefield'=> 'template_name'
									 ),
			'value'		=> array(0 => ' ')
		),
	##################################
	# END Datatable fields
	##################################
	)
);

/*
$form["tabs"]['ipaddress'] = array (
	'title' 	=> "IP Addresses",
	'width' 	=> 100,
	'template' 	=> "templates/client_edit_ipaddress.htm",
	'fields' 	=> array (
	##################################
	# Beginn Datatable fields
	##################################
		'ip_address' => array (
			'datatype'	=> 'TEXT',
			'formtype'	=> 'CHECKBOXARRAY',
			'default'	=> '',
			'value'		=> array('192.168.0.1' => '192.168.0.1', '192.168.0.2' => '192.168.0.2'),
			'separator'	=> ';'
		),
	##################################
	# ENDE Datatable fields
	##################################
	)
);
*/


?>
