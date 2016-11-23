# hagigim
Display books from Google Drive

Setup:

1. Setup Google v3 API for PHP<br>
<https://developers.google.com/drive/v3/web/quickstart/php><br>
When done you will have the file `client_secret.json` and a folder `vendor` with all required libraries.

1. Edit `config.json` and `style.css` to fit your needs.<br>
Note the manager password (stored as MD5 hash).
Note that the JSON file must be strict - **no comments, no ending commas**.

1. Visit the `manager.php` page. Note the manager password you have set before on `config.json`.<br>
Click Validate Google Auth. You will have to follow a link, approve API access, and enter the token back inside the box.<br>
Click Validate Google Auth again, and make sure you get the OK.<br>
Click clear cache (file, list)<br>
Click Test cache (file, list) and make sure you get the OK.<br> 

Notes::
* Requires PHP 5.5 or higher

* Before first run please visit the manager page, validate Google API and reset all cache folders.<br>
<http://localhost/hagigim/manager.php><br>


* Content caching (of files) will refresh automatically when the file was modified on Google Drive*. <br>
List caching (of search results and content modify date) will refresh after 24h. Changable in `config.json:list_cache_expires`.<br>
*Note that the file modified time is saved in lists cache, so cached lists might cause a content refresh delay of up to 24h.
 
* Supports up to 1000 pages (=Drive Files) per search. Default is up to 300. Changable in `config.json:list/pageSize`. 


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
		"list_cache_expires": 86400, // how long to keep ajax list requests inside 'cache' folder
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
