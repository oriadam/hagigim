<?php
global $CONFIG;
$CONFIG = json_decode(file_get_contents('config.json'), true);

// constants:
define('MANAGER_FLAG', 'manager_mode_activated');
define('CACHE_PATH', __DIR__ . '/cache');
define('MYLOG_PATH', __DIR__ . '/' . $CONFIG['log_filename']);
define('CACHETYPE_LIST', 'list');
define('CACHETYPE_FILE', 'googledoc_html');

// google auth constants:
define('APPLICATION_NAME', 'Drive API PHP Quickstart');
define('CREDENTIALS_PATH', __DIR__ . '/google_credentials.json');
define('REFRESH_TOKEN_PATH', __DIR__ . '/google_credentials_refresh.json');
define('CLIENT_SECRET_PATH', __DIR__ . '/client_secret.json');

// our personal logger
function mylog($str) {
	global $CONFIG;
	$str = date("Y-m-d H:i:s\t") . $str;
	file_put_contents(MYLOG_PATH, $str, FILE_APPEND | LOCK_EX);
	if (! empty($CONFIG[MANAGER_FLAG])) {
		echo $str;
	}
}
// return ajax error and exit
function ajax_fatal($string) {
	$return = array();
	$return['error'] = $string;
	echo json_encode($return);
	exit();
}
// cache - get full file path for an $id-$cachetype pair
function cache_path($id, $cachetype) {
	$cachetype = $cachetype ?: 'other';
	return CACHE_PATH . '/' . preg_replace('@[^a-zA-Z0-9_]@', '_', $cachetype) . '/' . preg_replace('@[^a-zA-Z0-9_]@', '_', $id);
}
// cache - return content for $id-$cachetype
// if content is missing or expired, returns false
function cache_read($id, $cachetype, $modifiedTime = null) {
	global $CONFIG;
	$path = cache_path($id, $cachetype);
	if (! empty($path)) {
		if (file_exists($path)) {
			if ($modifiedTime) {
				$mtime = filemtime($path);
				if ($mtime < $modifiedTime) {
					// cache file older than modified time - remove file
					unlink($path);
					return false;
				}
			}
			return file_get_contents($path);
		}
	}
	
	return false;
}
// cache - save content for $id-$cachetype
function cache_write($id, $cachetype, $content) {
	$path = cache_path($id, $cachetype);
	$dir = dirname($path);
	if (! is_dir($dir)) {
		mkdir($dir, 0777, true);
		chmod($dir, 0777);
	}
	
	try {
		file_put_contents($path, $content);
		chmod($path, 0777);
	} catch (Excpetion $e) {
		mylog('cache_write cant write to "' . $path . '" Exception: ' . $e);
	}
}
