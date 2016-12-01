# hagigim
Display books from Google Drive

Setup:

1. Setup Google v3 API for PHP<br>
<https://developers.google.com/drive/v3/web/quickstart/php><br>
When done you will have the file `client_secret.json` and a folder `vendor` with all required libraries.

1. Change your manager password in `config.json` at `manager_password_md5` (stored as MD5 hash).<br>
Note that all JSON file must be strict - **no comments, no ending commas**.

1. Visit the `manager.php` page. Use the password from step 1.<br>
Click Validate Google Auth. You will have to follow a link, approve API access, and enter the token back inside the box.<br>
Click Validate Google Auth again, and make sure you get the OK.<br>
Click clear cache (file, list)<br>
Click Test cache (file, list) and make sure you get the OK.<br> 
Click Edit Configuration.<br>

Notes:
* Requires PHP 5.5 or higher

* Before first run please visit the `manager.php` page, validate Google API and reset all cache folders.
