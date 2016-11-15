<?php
require_once "utils.php";
require_once "config.php";
require_once "reader.lib.php";
global $CONFIG;

$func = $_GET['f'];
$id = $_GET['id'];

switch ($func) {
case 'content':
	/////////////////
	// READ A FILE //
	/////////////////
	$file = get_file($id);
	$return = array(
		'filename' => $file->getName(),
		'name' => name($file->getName()),
		'id' => $file->getId(),
		'content' => content($file->getContent()),
	);
	echo json_encode($return);
	exit;
	break;

case 'list':
	//////////////////
	// SEARCH PAGES //
	//////////////////
	$q = empty($_GET['q']) ? '' : $_GET['q'];
	$list = get_files($q);
	$return = array();
	foreach ($list as $file) {
		$return[] = array('name' => name($file->getName()), 'id' => $file->getId());
	}
	echo json_encode($return);
	exit;
	break;

default:
	break;
}
