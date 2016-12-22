<?php
global $CONFIG;
require_once "config.php";
require_once "reader.lib.php";

$func = @$_GET['f'];

switch ($func) {
	case 'content':
		if (empty($_GET['id'])) {
			exit();
		}
		
		$id = $_GET['id'];
		$cache_id = $id;
		$modifiedTime = 1 * @$_GET['modifiedTime'];
		
		// ///////////////
		// READ A FILE //
		// ///////////////
		if (empty($_GET['id'])) {
			ajax_fatal('Missing required parameter: id');
		}
		$cached = cache_read($cache_id, CACHETYPE_FILE, $modifiedTime);
		if ($cached) {
			echo $cached;
			exit();
		}
		try {
			$response = get_file_as($id, 'text/html');
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
		cache_write($cache_id, CACHETYPE_FILE, $cached);
		echo $cached;
		exit();
		break;
	
	case 'list':
		// ////////////////
		// SEARCH PAGES //
		// ////////////////
		$cfg = @$_GET["cfg"] ?: '';
		$q = @$_GET["q"] ?: '';
		$q = preg_replace('@[\"\']@', '', $q);
		$cache_id = $cfg . '__' . urlencode($q) ?: '_empty_';
		$modifiedTime = time() - $CONFIG["list_cache_expires"];
		$cached = cache_read($cache_id, CACHETYPE_LIST, $modifiedTime);
		if ($cached) {
			echo $cached;
			exit();
		}
		
		$list_query = $CONFIG["google_drive_query"];
		if (!empty($q)){
			if (!empty($list_query)){
				$list_query .= " AND ";
			}
			$list_query .= "fullText contains \" $q \"";
		}
		$list = get_files($list_query);
		if (!empty($list) && !empty($list->error)) {
			ajax_fatal('Error with query ' . ($list ? $list->error ?: '' : ''));
			exit();
		}
		
		$return = array();
		foreach ( $list as $file ) {
			// For Debug:
			//$methods = get_class_methods($file);
			//var_export($methods);
			$row = array(
					'name' => name($file->getName()),
					'id' => $file->getId(),
					'modifiedTime' => strtotime($file->getModifiedTime())
			);
			if ($val=$file->getCreatedTime()){
				$row['createdTime'] = strtotime($val);
			}
			get_if_not_null($row,$file,'getContentHints');
			get_if_not_null($row,$file,'getDescription');
			get_if_not_null($row,$file,'getParents');
			get_if_not_null($row,$file,'getQuotaBytesUsed');
			get_if_not_null($row,$file,'getSize');
			get_if_not_null($row,$file,'getStarred');
			get_if_not_null($row,$file,'getTrashed');
			get_if_not_null($row,$file,'getVersion');
			get_if_not_null($row,$file,'getWebContentLink');
			get_if_not_null($row,$file,'getWebViewLink');
			get_if_not_null($row,$file,'getThumbnail');
			$return[] = $row;
		}
		$cached = json_encode($return);
		cache_write($cache_id, CACHETYPE_LIST, $cached);
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

// add method result to array if not null
function get_if_not_null(&$row,$class,$method){
	if (method_exists($class,$method)){
		$val = $class->$method();
		if ($val !== null){
			$property =	strtolower(str_replace('get','',$method));
			$row[$property] = $val;
		}
	}
}