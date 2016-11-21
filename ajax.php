<?php
global $CONFIG;
require_once "config.php";
require_once "reader.lib.php";

define ( 'CACHE_PATH', __DIR__ . '/cache' );

$func = @$_GET ['f'];

switch ($func) {
	case 'content' :
		if (empty ( $_GET ['id'] ))
			exit ();
		
		$id = $_GET ['id'];
		
		// /////////////
		// FOR DEBUG //
		// ////////////
		if (0) {
			$return = array (
					'id' => $id,
					'content' => '<p>הנני כאן מתחת לגדר הנני שם מעל הסיטדל</p><h1>' . rand () . '</h1>' 
			);
			echo json_encode ( $return );
			exit ();
		}
		
		// ///////////////
		// READ A FILE //
		// ///////////////
		if (empty ( $_GET ['id'] )) {
			fatal ( 'Missing required parameter: id' );
		}
		$mime = 'text/html';
		$cached = cache_read ( $id, $mime );
		if ($cached) {
			echo $cached;
			exit ();
		}
		$file = get_file_as ( $id, $mime );
		if (empty ( $file ) || ! empty ( $file->error ) || empty ( $file->getId ) || ! $file->getId ()) {
			fatal ( $file && @$file->error ? $file->error : 'Error fetching file' );
		}
		
		$return = array (
				'filename' => $file->getName (),
				'name' => name ( $file->getName () ),
				'id' => $file->getId (),
				'content' => content ( $file->getContent () ) 
		);
		$cached = json_encode ( $return );
		cache_write ( $id, $mime, $cached );
		echo $cached;
		exit ();
		break;
	
	case 'list' :
		// ////////////////
		// SEARCH PAGES //
		// ////////////////
		$q = empty ( $_GET ['q'] ) ? '' : $_GET ['q'];
		$q = preg_replace ( '@[\"\']@', '', $q );
		$id = urlencode ( $q );
		$mime = 'list';
		$cached = cache_read ( $id, $mime );
		if ($cached) {
			echo $cached;
			exit ();
		}
		
		$list = get_files ( 'fullText contains "' . $q . '"' );
		if (! empty ( $list ) && ! empty ( $list->error )) {
			fatal ( 'Error with query ' . ($list ? $list->error ?: '' : '') );
		}
		
		$return = array ();
		foreach ( $list as $file ) {
			$return [] = array (
					'name' => name ( $file->getName () ),
					'id' => $file->getId () 
			);
		}
		$cached = json_encode ( $return );
		cache_write ( $id, $mime, $cached );
		echo $cached;
		exit ();
		break;
	
	default :
		break;
}

// file name to pretty name parser
// change _ to spaces
function name($string) {
	return preg_replace ( '@[\s_]+|\.docx?@', ' ', $string );
}

// content parser
// htmlizer
function content($string) {
	return $string;
}
function fatal($string) {
	$return = array ();
	$return ['error'] = $string;
	echo json_encode ( $return );
	exit ();
}
function cache_path($id, $mime) {
	$mime = $mime ?: 'other';
	return CACHE_PATH . '/' . preg_replace ( '@[^a-zA-Z0-9_]@', '_', $mime ) . '/' . preg_replace ( '@[^a-zA-Z0-9_]@', '_', $id );
}
function cache_read($id, $mime) {
	global $CONFIG;
	$path = cache_path ( $id, $mime );
	$expires = time () - $CONFIG ['cache_expires'];
	if (! empty ( $path ))
		if (file_exists ( $path ))
			if (filemtime ( $path ) > $expires)
				return file_get_contents ( $path );
	
	return false;
}
function cache_write($id, $mime, $content) {
	$path = cache_path ( $id, $mime );
	$dir = dirname ( $path );
	if (! is_dir ( $dir )) {
		mkdir ( $dir, 0777, true );
		chmod ( $dir, 0777 );
	}
	
	file_put_contents ( $path, $content );
	chmod ( $path, 0777 );
}
