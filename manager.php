<?php
global $CONFIG,$MANAGER_MODE;
require_once "config.php";
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate");

$header = '<html><head>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.4/jquery.min.js"></script>
<link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet">
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
<link href="manager.css" rel="stylesheet">
</head><body>';

$secret_cookie = md5($CONFIG['manager_password_md5'] . floor(time() / 222200) . MANAGER_FLAG); // secret cookie lasts ~60 hours
$secret_cookie2 = md5($CONFIG['manager_password_md5'] . ceil(time() / 222200) . MANAGER_FLAG); // secret cookie lasts ~60 hours
$MANAGER_MODE = @$_COOKIE[MANAGER_FLAG] == $secret_cookie || @$_COOKIE[MANAGER_FLAG] == $secret_cookie2;

$bad_password = false;
if (!$MANAGER_MODE) {
	// login flow - honeypots logic
	if (! empty($_POST['user']) || ! empty($_POST['pass'])) {
		// honeypots logic
		$bad_password = true;
	} else if (! empty($_POST['po'])) {
		// login flow - read password
		if ($CONFIG['manager_password_md5'] == md5($_POST['po'])) {
			setcookie(MANAGER_FLAG, $secret_cookie2);
			header("Location: ?logged-in");
			exit();
		} else {
			$bad_password = true;
		}
	}
}
if ($bad_password) {
	sleep(5 * rand());
}
if (!$MANAGER_MODE) {
	// login flow - display
	echo $header;
	?>
<div class="container">
	<form method="POST" class="form-inline" action="?logging">
		<?php /* START HONEYPOTS */?>
		<h1>Manager Login</h1>
		<label class="not-really-here">User: <input name="user" /></label> <label
		class="not-really-here">Pass: <input name="pass" type="password" /></label>
		<?php /* ^ when these have values - ignore everything... */?>
		<?php /* END HONEYPOTS */?>
		<div class="input-append form-group <?=$bad_password ? 'has-error' : ''?>">
			<input placeholder="Enter your password" class="form-control <?=$bad_password ? 'has-error' : ''?>" name="po"  />
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
	$getaction = @$_GET['f'];
	$action = explode(';', $getaction);
	$param = @$action[1];
	$action = $action[0];
	$actions = array(
			'config' => 'Edit Configuration',
			'edit;custom/style.css' => 'Edit style.css',
			'auth' => 'Validate Google Auth',
			'clearauth' => 'Revoke Google Credentials',
			'testcache;list' => 'Test List Cache',
			'testcache;file' => 'Test File Cache',
			'clearcache;list' => 'Clear List Cache',
			'clearcache;file' => 'Clear File Cache',
			'log;mylog' => 'Read Inner Log',
			'log;phplog' => 'Read PHP Log',
			'clearlog;mylog' => 'Clear Inner Log',
			'clearlog;phplog' => 'Clear PHP Log',
			'logout' => 'Log out',
	);
	if (array_key_exists($getaction,$actions)) {
		$action_name = $actions[$getaction];
		if ($action == 'config') {
			include("manager-config.include.php");
			exit();
		}
		if ($action == 'edit') {
			global $FILENAME;
			$FILENAME = $param;
			include("manager-edit.include.php");
			exit();
		}

		if ($action == 'logout') {
			setcookie(MANAGER_FLAG, '');
			header("Location: ?");
			exit();			
		}

		// /////////////////////
		// Handle parameters //
		// /////////////////////
		if ($param == 'mylog') {
			$logpath = MYLOG_PATH;
		}
		
		if ($param == 'phplog') {
			$logpath = __DIR__ . '/error_log';
		}
		if ($param == 'list') {
			$cachetype = CACHETYPE_LIST;
		}
		if ($param == 'file') {
			$cachetype = CACHETYPE_FILE;
		}
		
		// /////////////////////////////////
		// Handle actions that redirects //
		// /////////////////////////////////
		
		if ($action == 'clearauth') {
			if (file_exists(REFRESH_TOKEN_PATH)) {
				unlink(REFRESH_TOKEN_PATH);
			}
			
			if (file_exists(CREDENTIALS_PATH)) {
				unlink(CREDENTIALS_PATH);
			}
			
			redirect('auth');
		}
		
		echo "<pre id='out'>Action: $action_name\n";
		if ($action == 'auth') {
			require "reader.lib.php";
			$list = get_files('Testing file list access');
			if ($list === null) {
				echo "\nError";
			} else {
				echo "OK";
			}
		}
		if ($action == 'log') {
			echo "Log file path: $logpath\n";
			if (file_exists($logpath)) {
				readfile($logpath);
			} else {
				echo "No such file.";
			}
		}
		if ($action == 'clearlog') {
			if (file_exists($logpath)) {
				unlink($logpath);
			}
			echo "\nDone.";
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
			echo "Testing basic: ";
			cache_write($id, $cachetype, $content);
			$read = cache_read($id, $cachetype);
			if ($content != $read) {
				echo "FAIL!\nContent from cache:\n" . $read;
			} else {
				echo "OK\n";
				echo "Testing modified time up to date: ";
				$read = cache_read($id, $cachetype, time() - 1000);
				if ($read != $content) {
					echo "FAIL";
				} else {
					echo "OK\n";
					echo "Testing modified time expired: ";
					$read = cache_read($id, $cachetype, time() + 1000);
					if ($read !== false) {
						echo "FAIL";
					} else {
						echo "OK\n";
					}
				}
			}
		}
		
		echo "</pre>";
	}
	
	echo "<h3>Select action:</h3>";
	foreach ( $actions as $act => $name ) {
		echo "<a href='?f=$act' class='act btn btn-default'>$name</a><br>";
	}

} // else manager
function rrmdir($dir) {
	if (is_dir($dir)) {
		$objects = scandir($dir);
		foreach ( $objects as $object ) {
			if ($object != "." && $object != "..") {
				if (filetype($dir . "/" . $object) == "dir") {
					rrmdir($dir . "/" . $object);
				} else {
					unlink($dir . "/" . $object);
				}
			}
		}
		reset($objects);
		rmdir($dir);
	}
}
function redirect($action) {
	echo "<script>location.href='?f=$action&_=" . rand() . "';</script>";
	exit();
}