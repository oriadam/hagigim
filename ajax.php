<?php
global $CONFIG;
require_once "config.php";
require_once "reader.lib.php";

$func = $_GET['f'];

switch ($func) {
case 'content':
	/////////////////
	// READ A FILE //
	/////////////////
	if (empty($_GET['id'])){
		fatal('Missing required parameter: id');
	}
	$id = $_GET['id'];
	$file = get_file_as($id);
	if (empty($file) || !empty($file->error) || !$file->getId()){
		fatal('File not found'.($file->error?:''));
	}
	
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
	$q = preg_replace('@[\"\']@', '', $q);
	$list = get_files('fullText contains "'.$q.'"');
	if (!empty($list) && !empty($list->error)){
		fatal('Error with query '.($list ? $list->error?:'' : ''));
	}
	
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

// file name to pretty name parser
// change _ to spaces
function name($string) {
	return preg_replace('@[\s_]+|\.docx?@', ' ', $string);
}

// content parser
// htmlizer
function content($string) {
	return $string;
}

function fatal($string){
	$return = array();
	$return['error']=$string;
	echo json_encode($return);
	exit;
}
