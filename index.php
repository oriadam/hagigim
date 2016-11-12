<?php
require_once "config.php";
require_once "reader.lib.php";
global $CONFIG;

function template_fn($filename, $vars) {
	$string = file_get_contents($filename);
	return template($string, $vars);
}

function template($string, $vars) {
	foreach ($vars as $k => $v) {
		$string = str_replace('$' . $k, $v, $string);
	}
	return $string;
}

// file name to pretty name parser
// change _ to spaces
function name($string) {
	return preg_replace('@[\s_]+@', ' ', $string);
}

// content parser
// htmlizer
function content($string) {
	return $string;
}

if (empty($_GET['f'])) {
	///////////////////
	// LIST OF FILES //
	///////////////////
	$list = get_files();
	$CONTENT = '';
	foreach ($list as $file) {
		$CONTENT .= template_fn('list_row.templ.html', array('NAME' => name($file->getName()), 'ID' => $file->getId()));
	}
	$CONTENT = template_fn('list.templ.html', array('CONTENT' => $CONTENT));
	echo template_fn('index.templ.html', array(
		'TITLE' => $config['list_title'],
		'CONTENT' => $CONTENT));
	exit;

} else {
	/////////////////
	// READ A FILE //
	/////////////////
	$id = $_GET['f'];
	$file = get_file($id);
	$vars = array(
		'FILENAME' => $file->getName(),
		'NAME' => name($file->getName()),
		'ID' => $file->getId(),
		'CONTENT' => content($file->getContent()),
	);
	$CONTENT = template_fn('file.templ.html', $vars);
	echo template_fn('index.templ.html', array(
		'TITLE' => template($config['file_title'], $vars),
		'CONTENT' => $CONTENT));
	exit;
}
