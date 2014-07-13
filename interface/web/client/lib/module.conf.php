<?php

$module["name"] 		= "client";
$module["title"] 		= "top_menu_client";
$module["template"] 	= "module.tpl.htm";
$module["startpage"] 	= "client/client_list.php";
$module["tab_width"]    = '';

/*
	Email accounts menu
*/




$items[] = array( 'title' 	=> "Edit Client",
				  'target' 	=> 'content',
				  'link'	=> 'client/client_list.php',
				  'html_id' => 'client_list');

$items[] = array( 'title' 	=> "Add Client",
				  'target' 	=> 'content',
				  'link'	=> 'client/client_edit.php',
				  'html_id' => 'client_add');
				  
				  if($_SESSION["s"]["user"]["typ"] == 'admin'){
	$items[] = array( 'title' 	=> "Edit Client-Templates",
					  'target' 	=> 'content',
					  'link'	=> 'client/client_template_list.php',
					  'html_id' => 'client_template_list');
}

$module["nav"][] = array(	'title'	=> 'Clients',
							'open' 	=> 1,
							'items'	=> $items);

unset($items);


if($_SESSION["s"]["user"]["typ"] == 'admin'){

$items[] = array( 'title' 	=> "Edit Reseller",
				  'target' 	=> 'content',
				  'link'	=> 'client/reseller_list.php',
				  'html_id' => 'reseller_list');

$items[] = array( 'title' 	=> "Add Reseller",
				  'target' 	=> 'content',
				  'link'	=> 'client/reseller_edit.php',
				  'html_id' => 'reseller_add');

$module["nav"][] = array(	'title'	=> 'Resellers',
							'open' 	=> 1,
							'items'	=> $items);

unset($items);
}

$items[] = array( 'title' 	=> "Send email",
				  'target' 	=> 'content',
				  'link'	=> 'client/client_message.php',
				  'html_id' => 'reseller_add');

$module["nav"][] = array(	'title'	=> 'Messaging',
							'open' 	=> 1,
							'items'	=> $items);

unset($items);

?>