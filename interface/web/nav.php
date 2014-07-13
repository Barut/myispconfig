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

require_once('../lib/config.inc.php');
require_once('../lib/app.inc.php');

$app->uses('tpl');

//** Top Naviation
if(isset($_GET['nav']) && $_GET['nav'] == 'top') {

	$app->tpl->newTemplate('topnav.tpl.htm');

	//* Check User Login and current module
	if(isset($_SESSION["s"]["user"]) && $_SESSION["s"]["user"]['active'] == 1 && is_array($_SESSION['s']['module'])) {
		//* Loading modules of the user and building top navigation
		$modules = explode(',', $_SESSION['s']['user']['modules']);
		/*
		 * If the dashboard is in the list of modules it always has to be the first!
		 */
		if (in_array('dashboard', $modules)) {
			$key = array_search('dashboard', $modules);
			unset($modules[$key]);
			$modules = array_merge(array('dashboard'), $modules);
		}
		if(is_array($modules)) {
			foreach($modules as $mt) {
				if(is_file($mt.'/lib/module.conf.php')) {
					if(!preg_match("/^[a-z]{2,20}$/i", $mt)) die('module name contains unallowed chars.');
					include_once($mt.'/lib/module.conf.php');
					$active = ($module['name'] == $_SESSION['s']['module']['name']) ? 1 : 0;
					$topnav[] = array(	'title' 	=> $app->lng($module['title']),
							'active' 	=> $active,
							'module'	=> $module['name']);
				}
			}
		}
	} else {
		//*  Loading Login Module
		include_once('login/lib/module.conf.php');
		$_SESSION['s']['module'] = $module;
		$topnav[] = array(	'title' 	=> 'Login',
				'active' 	=> 1);
		$module = null;
		unset($module);
	}

	//* Topnavigation
	$app->tpl->setLoop('nav_top',$topnav);

}

//** Side Naviation
if(isset($_GET['nav']) && $_GET['nav'] == 'side') {

	if(isset($_SESSION['s']['module']['name']) && is_file($_SESSION['s']['module']['name'].'/lib/custom_menu.inc.php')) {
		include_once($_SESSION['s']['module']['name'].'/lib/custom_menu.inc.php');
	} else {
	
		$app->tpl->newTemplate('sidenav.tpl.htm');

		//* translating module navigation
		$nav_translated = array();
		if(isset($_SESSION['s']['module']['nav']) && is_array($_SESSION['s']['module']['nav'])) {
			foreach($_SESSION['s']['module']['nav'] as $nav) {
				$tmp_items = array();
				foreach($nav['items'] as $item) {
					$item['title'] = $app->lng($item['title']);
					$tmp_items[] = $item;
				}
				$nav['title'] = $app->lng($nav['title']);
				$nav['startpage'] = $nav['items'][0]['link'];
				$nav['items'] = $tmp_items;
				$nav_translated[] = $nav;
			}
		} else {
			$nav_translated = null;
		}
		$app->tpl->setLoop('nav_left',$nav_translated);
	
	}

}

$app->tpl_defaults();
$app->tpl->pparse();

?>