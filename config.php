<?php
/* You can set custom config files and access them via search query `cfg`
For example, add a config json called 'custom/config-myCustomConfig.json' and set some different config options there.
Them, access the same url but with query string of ?cfg=myCustomConfig
*/

global $CONFIG,$CUSTOM_CONFIG_FN,$CUSTOM_CONFIG_NAME,$CUSTOM_PATH;
$CUSTOM_PATH = "custom";
// load defaults
$CONFIG = json_decode(file_get_contents('config.json'), true);
$CUSTOM_CONFIG_NAME = 'default';
$CUSTOM_CONFIG_FN = "$CUSTOM_PATH/config-$CUSTOM_CONFIG_NAME.json";

// load custom default config,"if a"y
read_from_config_file($CUSTOM_CONFIG_FN);

// load custom config, if any
if (!empty($_GET['cfg']) && strpos($_GET['cfg'],'/')===FALSE) {
	$name = $_GET['cfg'];
	$fn = "$CUSTOM_PATH/config-$name.json";
	if (file_exists($fn)){
		$CUSTOM_CONFIG_FN = $fn;
		$CUSTOM_CONFIG_NAME = $name;
		read_from_config_file($CUSTOM_CONFIG_FN);
	}  else if (!empty($_REQUEST['addcfg'])){
		$CUSTOM_CONFIG_FN = $fn;
		$CUSTOM_CONFIG_NAME = $name;
		file_put_contents($CUSTOM_CONFIG_FN,'{}');
		file_put_contents("$CUSTOM_PATH/style-$CUSTOM_CONFIG_NAME.css","@import url('style-default.css');\n");
		file_put_contents("$CUSTOM_PATH/script-$CUSTOM_CONFIG_NAME.js","//function process_page_hook(page, page_element, page_content, title){}");
	}
}

// constants:
define('MANAGER_FLAG', 'manager_mode_activated');
define('CACHE_PATH', __DIR__ . '/cache');
define('MYLOG_PATH', __DIR__ . '/' . $CONFIG['log_filename']);
define('CACHETYPE_LIST', 'list');
define('CACHETYPE_FILE', 'googledoc_html');
define('CONFIG_OPTIONS_FN','config-options.json');

// google auth constants:
define('APPLICATION_NAME', 'Drive API PHP Quickstart');
define('CREDENTIALS_PATH', __DIR__ . '/google_credentials.json');
define('REFRESH_TOKEN_PATH', __DIR__ . '/google_credentials_refresh.json');
define('CLIENT_SECRET_PATH', __DIR__ . '/client_secret.json');

function read_from_config_file($fn){
	global $CONFIG;
	if (file_exists($fn)){
		$new_config = json_decode(file_get_contents($fn), true);
		$CONFIG = array_merge($CONFIG,$new_config);
	}
}

// for the JS config object
function config_for_js(){
	global $CONFIG;
	$copy = array();
	foreach($CONFIG["options_for_js"] as $name){
		$copy[$name]=$CONFIG[$name];
	}
	return $copy;
}

// our personal logger
function mylog($str) {
	global $CONFIG,$MANAGER_MODE;
	$str = date("Y-m-d H:i:s\t") . $str . "\n";
	file_put_contents(MYLOG_PATH, $str, FILE_APPEND | LOCK_EX);
	if (!empty($MANAGER_MODE)) {
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

function is_firefox(){
	return preg_match('/Firefox/i',$_SERVER['HTTP_USER_AGENT']);
}