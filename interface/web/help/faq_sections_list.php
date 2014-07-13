<?php

require_once('../../lib/config.inc.php');
require_once('../../lib/app.inc.php');

// Path to the list definition file
$list_def_file = 'list/faq_sections_list.php';

// Check the module permissions
if(!stristr($_SESSION['s']['user']['modules'],'help')) {
	header('Location: ../index.php');
	die();
}

// Loading the class
$app->uses('listform_actions');

// Start the form rendering and action ahndling
$app->listform_actions->onLoad();

?>
