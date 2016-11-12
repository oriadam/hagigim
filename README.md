# hagigim
Display books from Google Drive

Requirements:

1. Setup Google v3 API for PHP:<br>
https://developers.google.com/drive/v3/web/quickstart/php<br>
When done you should have the file `client_secret.json` and a folder `vendor` with all required libraries.

2. Run the following command in command line to approve the API access and create the file `google_credentials.json`.
`php reader.lib.php`<br>
You will get a temporary link and will have to enter a key.<br>

3. Copy the `config-default.json` file to `config.json` and change any settings you need.




