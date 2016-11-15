<?php
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
	return preg_replace('@[\s_]+|\.docx?@', ' ', $string);
}

// content parser
// htmlizer
function content($string) {
	return $string;
}
