# hagigim
Display books from Google Drive

Setup:

1. Edit `config.json` and `style.css` to fit your needs. Note the manager password is stored as MD5 hash. 
Note that the JSON file is a strict JSON format - **no comments, no ending commas**.
(because `json_decode()` does not allow comments or ending commas. Guhhh)

1. Setup Google v3 API for PHP<br>
<https://developers.google.com/drive/v3/web/quickstart/php><br>
When done you will have the file `client_secret.json` and a folder `vendor` with all required libraries.

1. Before first run please visit the manager page, validate Google API and reset all cache folders.<br>
<http://localhost/hagigim/manager.php><br>

1. Requires PHP 5.5 or higher
 

__Settings__


	config.json
	{
		"page_title": "הגיגים", // main page title
		"cover_front": "<h1>הגיגים</h1><p>ספר שירים</p>", // the html on blue front cover 
		"cover_back": "<h1>תודה</h1>", // the html on blue back cover
		"loading_page" : "<h1 class='page-title'></h1><h2>העמוד בטעינה...</h2>", // html while page is loading via ajax
		"text_search" : "חיפוש...", // text on search box
		"text_go" : "המשך", // text on search button
		"text_searching": "רגע...", // text while search results are populated to book via ajax
		"text_found" : "נמצאו %s הגיגים", // text below search box
		"text_not_found" : "אין תוצאות", // text below search box - when nothing was found
		"cache_expires": 86400, // how long to keep ajax requests inside 'cache' folder
		"start_with_closed_book": false, // after search results populated, should i we display a closed book?
		"log_filename": "err.log", // file name for the error log. You can read the log on manager.php
		"manager_password_md5": "0ff49c3123244ee5187f9130902857a0", // MD5 hash of the manager password. To calculate visit http://demo.adopt-media.com/static/md5.html
		
	
		// the following is passed on to google drive api
		// the 'XXX' in parents is the id of the songs folder
		"list": {
			"q": "mimeType='application/vnd.google-apps.document' and '0B7cabAnVcz8xTHNUd1JieUFjQm8' in parents",
			"pageSize": 1000,
			"fields": "files(createdTime,description,id,modifiedTime,name,properties,starred),kind,nextPageToken"
		},
	
		// options pass on to turnjs
		// see http://www.turnjs.com/#api
		"turn_options" : {
			"autoCenter": true,
			"direction": "rtl"
		}
	}

