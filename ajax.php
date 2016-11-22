<?php
global $CONFIG;
require_once "config.php";
require_once "reader.lib.php";

$func = @$_GET['f'];

switch ($func) {
	case 'content':
		if (empty($_GET['id']))
			exit();
		
		$id = $_GET['id'];
		
		// /////////////
		// FOR DEBUG //
		// ////////////
		if (0) {
			$return = array(
					'id' => $id, 
					'content' => '<p>הנני כאן מתחת לגדר הנני שם מעל הסיטדל</p><h1>' . rand() . '</h1>'
			);
			echo json_encode($return);
			exit();
		}
		
		// ///////////////
		// READ A FILE //
		// ///////////////
		if (empty($_GET['id'])) {
			ajax_fatal('Missing required parameter: id');
		}
		$cached = cache_read($id, CACHETYPE_FILE);
		if ($cached) {
			echo $cached;
			exit();
		}
		try {
			$response = get_file_as($id,'text/html');
		} catch (RequestException $e) {
			if ($e->hasResponse()) {
				ajax_fatal(Psr7\str($e->getResponse()));
			}
		}
		
		if ($response && $response->getStatusCode() == 200) {
			$content = (string) $response->getBody();
		} else {
			ajax_fatal('Error fetching file ' . ($response && $repsonse->getStatusCode ? $response->getReasonPhrase() . '(' . $response->getStatusCode() . ')' : ''));
		}
		
		$return = array(
				'id' => $id,
				'content' => content($content)
		);
		$cached = json_encode($return);
		cache_write($id, CACHETYPE_FILE, $cached);
		echo $cached;
		exit();
		break;
	
	case 'list':
		// ////////////////
		// SEARCH PAGES //
		// ////////////////
		$q = empty($_GET['q']) ? '' : $_GET['q'];
		$q = preg_replace('@[\"\']@', '', $q);
		$id = urlencode($q) ?: '_empty_';
		$cached = cache_read($id, CACHETYPE_LIST);
		if ($cached) {
			echo $cached;
			exit();
		}
		
		$list = get_files('fullText contains "' . $q . '"');
		if (! empty($list) && ! empty($list->error)) {
			ajax_fatal('Error with query ' . ($list ? $list->error ?: '' : ''));
		}
		
		$return = array();
		foreach ( $list as $file ) {
			$return[] = array(
					'name' => name($file->getName()), 
					'id' => $file->getId()
			);
		}
		$cached = json_encode($return);
		cache_write($id, CACHETYPE_LIST, $cached);
		echo $cached;
		exit();
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
	$string = preg_replace('/<\\/?html[^>]*>|<head>.*<\\/head>|<style>.*<\\/style>|style="[^"]*"/', '', $string);
	$string = preg_replace('/<body[^>]*>/', '<div>', $string);
	$string = preg_replace('/<\\/body[^>]*>/', '<div>', $string);
	return $string;
}
