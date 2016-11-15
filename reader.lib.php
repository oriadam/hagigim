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

/**
 * Returns an authorized API client.
 * @return Google_Client the authorized client object
 */
function getClient() {
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
		if (php_sapi_name() != 'cli') {
			throw new Exception('This application must be run on the command line.');
		}

		// Request authorization from the user.
		$authUrl = $client->createAuthUrl();
		printf("Open the following link in your browser:\n%s\n", $authUrl);
		print 'Enter verification code: ';
		$authCode = trim(fgets(STDIN));

		// Exchange authorization code for an access token.
		$accessToken = $client->fetchAccessTokenWithAuthCode($authCode);

		// Store the credentials to disk.
		if (!file_exists(dirname($credentialsPath))) {
			mkdir(dirname($credentialsPath), 0700, true);
		}
		file_put_contents($credentialsPath, json_encode($accessToken));
		//printf("Credentials saved to %s\n", $credentialsPath);
	}
	$client->setAccessToken($accessToken);

	// Refresh the token if it's expired.
	try {
		if ($client->isAccessTokenExpired()) {
			$client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
			file_put_contents($credentialsPath, json_encode($client->getAccessToken()));
		}
	} catch (Exception $e) {
		print_r($e);
		unlink($credentialsPath);
	}
	return $client;
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
$GOOGLE['client'] = getClient();
$GOOGLE['service'] = new Google_Service_Drive($GOOGLE['client']);

function get_files($q = '') {
	global $CONFIG, $GOOGLE;
	// Get the names and IDs of all files
	$optParams = $CONFIG['list'];
	if (!empty($q)) {
		$optParams['q'] .= " AND $q";
	}

	$results = $GOOGLE['service']->files->listFiles($optParams);
	return $results->getFiles();

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

function get_file($file, $mime = 'text/html') {
	global $CONFIG, $GOOGLE;
	$id = $file->getId();
	$optParams = array(
		"fileId" => $id,
		"mimeType" => $mime,
	);
	$results = $GOOGLE['service']->files->export($optParams);
	print_r($results);
	return $results;
}
