<?php
/*
Copyright (c) 2007 - 2012, Till Brehm, projektfarm Gmbh
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

//** Web-only
if( !empty($_SERVER['DOCUMENT_ROOT']) ) {

	Header("Pragma: no-cache");
	Header("Cache-Control: no-store, no-cache, max-age=0, must-revalidate");
	Header("Content-Type: text/html; charset=utf-8");
	
	//** Set a few php.ini values
	ini_set('register_globals',0);
	ini_set('magic_quotes_gpc', 0);
	
	if (isset($_REQUEST['GLOBALS']) || isset($_FILES['GLOBALS']) || isset($_REQUEST['s']) || isset($_REQUEST['s_old']) || isset($_REQUEST['conf'])) {
		die('Internal Error: var override attempt detected');
		exit;
	}
}

//** Set a few php.ini values
if(get_magic_quotes_runtime()) set_magic_quotes_runtime(false);
if(isset($app)) unset($app);
if(isset($conf)) unset($conf);


//** SVN Revision
$svn_revision = '$Revision: 3210 $';
$revision = str_replace(array('Revision:','$',' '), '', $svn_revision);

//** Application
define('ISPC_APP_TITLE', 'ISPConfig');
define('ISPC_APP_VERSION', '3.0.4.6');
define('DEVSYSTEM', 0);


//** Database
$conf['db_type'] = 'mysql';
$conf['db_host'] = 'localhost';
$conf['db_database'] = 'ispconfig_db';
$conf['db_user'] = 'ispconfig';
$conf['db_password'] = 'a7868ea1930a30226d2a54422ff0a3c3';
$conf['db_charset'] = 'utf8'; // same charset as html-charset - (HTML --> MYSQL: "utf-8" --> "utf8", "iso-8859-1" --> "latin1")
$conf['db_new_link'] = false;
$conf['db_client_flags'] = 0;

define('DB_TYPE',$conf['db_type']);
define('DB_HOST',$conf['db_host']);
define('DB_DATABASE',$conf['db_database']);
define('DB_USER',$conf['db_user']);
define('DB_PASSWORD',$conf['db_password']);
define('DB_CHARSET',$conf['db_charset']);


//** Database settings for the master DB. This setting is only used in multiserver setups
$conf['dbmaster_type']			= 'mysql';
$conf['dbmaster_host']			= '';
$conf['dbmaster_database']		= 'dbispconfig';
$conf['dbmaster_user']			= '';
$conf['dbmaster_password']		= '82ca4d16bbed67c93aad94c3b548137d';
$conf['dbmaster_new_link'] 		= false;
$conf['dbmaster_client_flags'] 	= 0;


//** Paths
$conf['ispconfig_log_dir'] = '/var/log/ispconfig';
define('ISPC_ROOT_PATH', realpath(dirname(__FILE__).'/../')); // Main ROOT is the parent directory to this file, ie interface/ or server/. NO trailing slashes.
define('ISPC_LIB_PATH', ISPC_ROOT_PATH.'/lib');
define('ISPC_CLASS_PATH', ISPC_ROOT_PATH.'/lib/classes');
define('ISPC_WEB_PATH', ISPC_ROOT_PATH.'/web');
define('ISPC_THEMES_PATH', ISPC_ROOT_PATH.'/web/themes');
define('ISPC_WEB_TEMP_PATH', ISPC_WEB_PATH.'/temp'); // Path for downloads, accessible via browser
define('ISPC_CACHE_PATH', ISPC_ROOT_PATH.'/cache');
define('ISPC_LOG_PATH', $conf['ispconfig_log_dir']);

//** Paths (Do not change!)
$conf['rootpath'] = substr(dirname(__FILE__),0,-4);
$conf['fs_div'] = '/'; // File system separator, "\\" on Windows and "/" on Linux and UNIX
$conf['classpath'] = $conf['rootpath'].$conf['fs_div'].'lib'.$conf['fs_div'].'classes';
$conf['temppath'] = $conf['rootpath'].$conf['fs_div'].'temp';
$conf['logpath'] = ISPC_LOG_PATH; // Actually just a copy of $conf['ispconfig_log_dir'], might be removed in a later version

define('FS_DIV',$conf['fs_div']);
define('SERVER_ROOT',$conf['rootpath']);
define('INCLUDE_ROOT',SERVER_ROOT.FS_DIV.'lib');
define('CLASSES_ROOT',INCLUDE_ROOT.FS_DIV.'classes');


//** Server
$conf['app_title'] = ISPC_APP_TITLE;
$conf['app_version'] = ISPC_APP_VERSION;
$conf['app_link'] = 'http://www.ispconfig.org/';
$conf['modules_available'] = 'dashboard,admin,mail,sites,monitor,client,dns,help';
$conf['server_id'] = '1';
$conf['init_scripts'] = '/etc/init.d';


//** Interface
$conf['interface_modules_enabled'] = 'dashboard,mail,sites,dns,tools,help';

//** Demo mode
/* The demo mode is an option to restrict certain actions in the interface like 
*  changing the password of users with sys_userid < 3 etc. to be 
*  able to run the ISPConfig interface as online demo. It does not
*  affect the server part. The demo mode should be always set to false
*  on every normal installation
*/
$conf['demo_mode'] = false;


//** Logging
$conf['log_file'] = $conf['ispconfig_log_dir'].$conf['fs_div'].'ispconfig.log';
$conf['log_priority'] = 2; // 0 = Debug, 1 = Warning, 2 = Error


//** Allow software package installations
$conf['software_updates_enabled'] = false;


//** Themes
$conf['theme'] = 'default';
$conf['html_content_encoding'] = 'utf-8'; // example: utf-8, iso-8859-1, ...
$conf['logo'] = 'themes/default/images/header_logo.png';

//** Default Language and Timezone
$conf['language'] = 'en';
$conf['debug_language'] = false;

//* Complete timezone list: http://php.net/manual/en/timezones.php
$conf['timezone'] = 'UTC';


//** Misc.
$conf['interface_logout_url'] = ''; // example: http://www.domain.tld/


//** Auto Load Modules
$conf['start_db'] = true;
$conf['start_session'] = true;

//** Constants
define('LOGLEVEL_DEBUG',0);
define('LOGLEVEL_WARN',1);
define('LOGLEVEL_ERROR',2);

//** include a local config-file if there is one
//** IMPORTANT!!!
//** DO NOT MAKE A COPY OF THIS FILE AS LOCAL CONFIG-FILE
//** USE A EMPTY FILE AND ONLY COPY THE SETTINGS IN IT, YOU NEED TO OVERRIDE
if (is_file(dirname(__FILE__) . '/config.inc.local.php')) include_once 'config.inc.local.php';
?>