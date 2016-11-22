<?php
global $CONFIG;
require_once "config.php";
session_start();
header("Cache-Control: no-store");

$header = '<html><head>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.4/jquery.min.js"></script>
<link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet">
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
<style>
.not-really-here {opacity: 0;position: absolute;z-index: -1;}
</style>
</head><body>';

$secret_cookie = md5($CONFIG['manager_password_md5'] . floor(time() / 222200) . MANAGER_FLAG); // secret cookie lasts ~60 hours
$CONFIG[MANAGER_FLAG] = @$_COOKIE[MANAGER_FLAG] == $secret_cookie;

$bad_password = false;
if (empty($CONFIG[MANAGER_FLAG])) {
	// login flow - honeypots logic
	if (! empty($_POST['user']) || ! empty($_POST['pass'])) {
		// honeypots logic
		$bad_password = true;
	} else if (! empty($_POST['po'])) {
		// login flow - read password
		if ($CONFIG['manager_password_md5'] == md5($_POST['po'])) {
			setcookie(MANAGER_FLAG, $secret_cookie);
			$CONFIG[MANAGER_FLAG] = true;
		} else {
			$bad_password = true;
		}
	}
}
if ($bad_password) {
	sleep(5 * rand());
}
if (empty($CONFIG[MANAGER_FLAG])) {
	// login flow - display
	echo $header;
	?>
<div class="container">
	<form method="POST" class="form-inline">
			<?php /* These are just honeypots: */ ?>
			<label class="not-really-here">User: <input name="user" /></label> <label
			class="not-really-here">Pass: <input name="pass" type="password" /></label>
			<?php /* ^ when these have values - ignore everything... */ ?>
			<div class="input-append">
			<div class="form-group<?=$bad_password?' has-error':''?>">
				<input placeholder="Enter your password" class="form-control"
					name="po" />
			</div>
			<input type="submit" value="Go" class="form-control">
		</div>
	</form>
</div>
<script>
		document.querySelector('[name="po"]').type="password";
	</script>
<?php
	// if manager
} else {
	echo $header;
	$action = empty($_GET['f']) ? '' : preg_replace('/[^a-z\-]/', '', strtolower($_GET['f']));
	$actions = array(
			'auth' => 'Validate Google Auth',
			'authclear' => 'Revoke Google Credentials',
			'testcache-list' => 'Test List Cache', 
			'testcache-file' => 'Test File Cache', 
			'clearcache-list' => 'Refresh List Cache',
			'clearcache-file' => 'Refresh File Cache',
				'log' => 'Display Logs'
	);
	$action_name = @$actions[$action];
	if ($action_name) {
		echo "<pre id='out'>Action: $action_name\n";
		if ($action == 'authclear'){
			if (file_exists(REFRESH_TOKEN_PATH))
				unlink(REFRESH_TOKEN_PATH);
			if (file_exists(CREDENTIALS_PATH))
				unlink(CREDENTIALS_PATH);
			$action = 'auth';
			echo "</pre><script>location.href='?f=$action&_=".rand()."';</script>";
		}
		if ($action == 'auth') {
			require ("reader.lib.php");
			$list = get_files('Testing file list access');
			if ($list===null){
				echo "\nError";
			} else {
				echo "OK";
			}
		}
		if ($action == 'log') {
			echo "Log path: ".MYLOG_PATH."\n";
			if (file_exists(MYLOG_PATH)){
				$log = file_get_contents(MYLOG_PATH);
				$entries = explode("\n", $log);
				for($i = count($entries); $i >= 0; $i--) {
					echo $entries[$i] . "\n";
				}
			} else {
				echo "No log file.";
			}
		}
		if ($action == 'clearcache-list') {
			$action = 'clearcache';
			$cachetype = CACHETYPE_LIST;
		}
		if ($action == 'clearcache-file') {
			$action = 'clearcache';
			$cachetype = CACHETYPE_FILE;
		}
		if ($action == 'testcache-list') {
			$action = 'testcache';
			$cachetype = CACHETYPE_LIST;
		}
		if ($action == 'testcache-file') {
			$action = 'testcache';
			$cachetype = CACHETYPE_FILE;
		}
		if ($action == 'clearcache') {
			$path = CACHE_PATH . "/$cachetype";
			echo "Removing $path\n";
			rrmdir($path);
			mkdir($path, 0777, true);
			chmod($path, 0777);
			echo "\nDone.";
		}
		if ($action == 'testcache') {
			$id = "_test_cache";
			$content = "This is a content for testing cache";
			echo "Cache file = " . cache_path($id, $cachetype) . "\n";
			cache_write($id, $cachetype, $content);
			$read = cache_read($id, $cachetype);
			if ($content == $read) {
				echo "OK";
			} else {
				echo "FAIL!\nContent from cache:\n" . $read;
			}
		}
		
		echo "</pre>";
	}
	
	echo "<h3>Select action:</h3>";
	foreach ( $actions as $act => $name ) {
		echo "<a href='?f=$act&_=".rand()."' class='act btn btn-default'>$name</a><br>";
	}
	echo "<style>.act { width:250px;text-align:center; }</style>"?>

	<?php
} // else manager
function rrmdir($dir) {
	if (is_dir($dir)) {
		$objects = scandir($dir);
		foreach ( $objects as $object ) {
			if ($object != "." && $object != "..") {
				if (filetype($dir . "/" . $object) == "dir")
					rrmdir($dir . "/" . $object);
				else
					unlink($dir . "/" . $object);
			}
		}
		reset($objects);
		rmdir($dir);
	}
}