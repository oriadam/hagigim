<?php
require_once "config.php";
global $CONFIG, $GOOGLE;

// Google Drive API v3 - https://developers.google.com/drive/v3/web/quickstart/php
require_once __DIR__ . '/vendor/autoload.php';
define('APPLICATION_NAME', 'Drive API PHP Quickstart');
define('CREDENTIALS_PATH', __DIR__ . '/google_credentials.json');
define('CLIENT_SECRET_PATH', __DIR__ . '/client_secret.json');
// If modifying these scopes, delete your previously saved credentials
// at ~/.credentials/drive-php-quickstart.json
define('SCOPES', implode(' ', array(
	Google_Service_Drive::DRIVE_METADATA_READONLY)
));

function readGoogleToken($client) {
	// Request authorization from the user.
	$authUrl = $client->createAuthUrl();
	if (php_sapi_name() == 'cli') {
		// cli mode (command line mode)
		printf("Open the following link in your browser:\n%s\n", $authUrl);
		print 'Enter verification code: ';
		$authCode = trim(fgets(STDIN));
	} else {
		// web mode (html mode)
		$now1 = floor(time()/60);
		$now2 = 1+$now1;
		$prefix = 'verificationcode';
		
		if (empty($_POST["$prefix-$now1"])&&empty($_POST["$prefix-$now2"])){
			echo "Open the following link: <A target=_blank href='$authUrl'>$authUrl</a><br>";
			echo "<form method=POST><label>Enter verification code: <input name='$prefix-$now2'></label><input type=submit value=Go></form>";
			return false;
		} else {
			$authCode = @$_POST["$prefix-$now1"] ?: @$_POST["$prefix-$now2"];
		}
	}
	
	// Exchange authorization code for an access token.
	$accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
	if (!empty($accessToken['error'])){
		echo "Google Token Error: " . $accessToken['error'];
		return false;
	} else {
		return $accessToken;
	}
}

/**
 * Returns an authorized API client.
 * @return Google_Client the authorized client object
 */
function getGoogleClient() {
	
	$client = new Google_Client();
	$client->setApplicationName(APPLICATION_NAME);
	$client->setScopes(SCOPES);
	$client->setAuthConfig(CLIENT_SECRET_PATH);
	$client->setAccessType('offline');

	// Load previously authorized credentials from a file.
	$credentialsPath = expandHomeDirectory(CREDENTIALS_PATH);
	if (file_exists($credentialsPath)) {
		$accessToken = json_decode(file_get_contents($credentialsPath), true);
	} else {
		$accessToken = readGoogleToken($client);
		if ($accessToken){
			// Store the credentials to disk.
			if (!file_exists(dirname($credentialsPath))) {
				mkdir(dirname($credentialsPath), 0777, true);
			}
			file_put_contents($credentialsPath, json_encode($accessToken));
			chmod($credentialsPath,0777);
		}
		return false;
	}
	if ($accessToken) {
		$client->setAccessToken($accessToken);
	
		// Refresh the token if it's expired.
		try {
			if ($client->isAccessTokenExpired()) {
				$client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
				file_put_contents($credentialsPath, json_encode($client->getAccessToken()));
				chmod($credentialsPath,0777);
			}
		} catch (Exception $e) {
			print_r($e);
			unlink($credentialsPath);
			return false;
		}
		return $client;
	} else {
		return false;
	}
}

/**
 * Expands the home directory alias '~' to the full path.
 * @param string $path the path to expand.
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
if (empty($GOOGLE['client'])){
	exit;
}
$GOOGLE['service'] = new Google_Service_Drive($GOOGLE['client']);

function get_files($q = '') {
	global $CONFIG, $GOOGLE;
	// Get the names and IDs of all files
	$optParams = $CONFIG['list'];
	if (!empty($q)) {
		$optParams['q'] .= " AND $q";
	}

	try {
		$results = $GOOGLE['service']->files->listFiles($optParams);
		return $results->getFiles();
	}catch(Exception $e){
		$obj = new stdClass();
		$obj->error = $e;
		return $obj;
	}

	/*
		// Usage Example:
		if (count($results->getFiles()) == 0) {
			print "No files found.\n";
		} else {
			print "Files:\n";
			foreach ($results->getFiles() as $file) {
				printf("%s (%s)\n", $file->getName(), $file->getId());
			}
	*/
}

function get_file_as($id, $mime = 'text/html') {
	global $CONFIG, $GOOGLE;
	//$id = $file->getId();
	$optParams = array(
		"fileId" => $id,
		"mimeType" => $mime,
	);
	$results = $GOOGLE['service']->files->export($optParams);
	print_r($results);
	return $results;
}
