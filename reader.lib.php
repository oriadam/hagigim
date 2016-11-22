<?php
require_once "config.php";
global $CONFIG, $GOOGLE;

// Google Drive API v3 - https://developers.google.com/drive/v3/web/quickstart/php
require_once __DIR__ . '/vendor/autoload.php';
// If modifying these scopes, delete your previously saved credentials
// at ~/.credentials/drive-php-quickstart.json
define('SCOPES', implode(' ', array(
		Google_Service_Drive::DRIVE, 
		Google_Service_Drive::DRIVE_METADATA_READONLY, 
		Google_Service_Drive::DRIVE_READONLY, 
		Google_Service_Drive::DRIVE_FILE
)));
function readGoogleToken($client) {
	global $CONFIG;
	// Request authorization from the user.
	$authUrl = $client->createAuthUrl();
	if (php_sapi_name() == 'cli') {
		// cli mode (command line mode)
		print "Google Auth";
		printf("Open the following link in your browser:\n%s\n", $authUrl);
		print 'Enter verification code: ';
		$authCode = trim(fgets(STDIN));
	} else {
		// web mode (html mode)
		if (empty($CONFIG[MANAGER_FLAG]))
			return false;
		$now1 = floor(time() / 60);
		$now2 = 1 + $now1;
		$prefix = 'verificationcode';
		
		if (empty($_POST["$prefix-$now1"]) && empty($_POST["$prefix-$now2"])) {
			echo "<h1>Google Auth</h1>";
			echo "Open the following link: <A target=_blank href='$authUrl'>$authUrl</a><br>";
			echo "<form method=POST><label>Enter verification code: <input name='$prefix-$now2'></label><input type=submit value=Go></form>";
			return false;
		} else {
			$authCode = @$_POST["$prefix-$now1"] ?: @$_POST["$prefix-$now2"];
		}
	}
	
	// Exchange authorization code for an access token.
	$accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
	if (! empty($accessToken['error'])) {
		log("Google Token Error: " . $accessToken['error']);
		return false;
	} else {
		return $accessToken;
	}
}

/**
 * Returns an authorized API client.
 *
 * @return Google_Client the authorized client object
 */
function getGoogleClient() {
	global $CONFIG;
	$client = new Google_Client();
	$client->setApplicationName(APPLICATION_NAME);
	$client->setScopes(SCOPES);
	$client->setAuthConfig(CLIENT_SECRET_PATH);
	$client->setAccessType('offline');
	$client->setApprovalPrompt('force');
	
	// Load previously authorized credentials from a file.
	$credentialsPath = expandHomeDirectory(CREDENTIALS_PATH);
	if (file_exists($credentialsPath)) {
		$accessToken = json_decode(file_get_contents($credentialsPath), true);
	} else {
		$accessToken = readGoogleToken($client);
		if ($accessToken) {
			// Store the credentials to disk.
			if (! file_exists(dirname($credentialsPath))) {
				mkdir(dirname($credentialsPath), 0777, true);
			}
			file_put_contents($credentialsPath, json_encode($accessToken));
			chmod($credentialsPath, 0777);
		}
	}
	if ($accessToken) {
		$client->setAccessToken($accessToken);
		// handle refresh token
		if (! file_exists(REFRESH_TOKEN_PATH)) {
			$refreshToken = $client->getRefreshToken();
			if ($refreshToken) {
				file_put_contents(REFRESH_TOKEN_PATH, json_encode($refreshToken));
				chmod(REFRESH_TOKEN_PATH, 0777);
			} else {
				mylog("Google Auth Error: client->getRefreshToken() returned empty result");
			}
		}
		
		// Refresh the token if it's expired.
		if ($client->isAccessTokenExpired()) {
			if (file_exists(REFRESH_TOKEN_PATH)) {
				$refreshToken = json_decode(file_get_contents(REFRESH_TOKEN_PATH));
				$client->fetchAccessTokenWithRefreshToken($refreshToken);
				$accessToken = $client->getAccessToken();
				if ($accessToken) {
					file_put_contents($credentialsPath, json_encode($accessToken));
					chmod($credentialsPath, 0777);
				} else {
					mylog("Google Auth Error: client->fetchAccessTokenWithRefreshToken() called, getAccessToken() returned empty result");
				}
			} else {
				if (file_exists($credentialsPath)) {
					unlink($credentialsPath);
					return getGoogleClient();
				} else {
					mylog("Google Auth Error: no refresh token");
					return false;
				}
			}
			$refreshToken = $client->getRefreshToken();
			if ($refreshToken) {
				$client->fetchAccessTokenWithRefreshToken($refreshToken);
			} else {
				mylog("Google Auth Error: client->getRefreshToken() returned empty result");
			}
		}
		return $client;
	} else {
		return false;
	}
}

/**
 * Expands the home directory alias '~' to the full path.
 *
 * @param string $path
 *        	the path to expand.
 * @return string the expanded path.
 */
function expandHomeDirectory($path) {
	$homeDirectory = getenv('HOME');
	if (empty($homeDirectory)) {
		$homeDirectory = getenv('HOMEDRIVE') . getenv('HOMEPATH');
	}
	return str_replace('~', realpath($homeDirectory), $path);
}

// Get the API client and construct the service object.
$GOOGLE = array();
$GOOGLE['client'] = getGoogleClient();
if (empty($GOOGLE['client'])) {
	exit();
}
$GOOGLE['service'] = new Google_Service_Drive($GOOGLE['client']);
function get_files($q = '') {
	global $CONFIG, $GOOGLE;
	// Get the names and IDs of all files
	$optParams = $CONFIG['list'];
	if (! empty($q)) {
		$optParams['q'] .= " AND $q";
	}
	
	try {
		$results = $GOOGLE['service']->files->listFiles($optParams);
		return $results->getFiles();
	} catch (Exception $e) {
		$obj = new stdClass();
		$obj->error = $e;
		return $obj;
	}
	
	/*
	 * // Usage Example:
	 * if (count($results->getFiles()) == 0) {
	 * print "No files found.\n";
	 * } else {
	 * print "Files:\n";
	 * foreach ($results->getFiles() as $file) {
	 * printf("%s (%s)\n", $file->getName(), $file->getId());
	 * }
	 */
}
function get_file_as($id, $mime = 'text/html') {
	global $CONFIG, $GOOGLE;
	// $id = $file->getId();
	$optParams = array(
			"fileId" => $id, 
			"mimeType" => $mime
	);
	$results = $GOOGLE['service']->files->export($id, $mime);
	return $results;
}
