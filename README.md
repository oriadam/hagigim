# hagigim
Display books from Google Drive

Requirements:

1. Copy the `config-default.json` file to `config.json` and change any settings you need.

1. Setup Google v3 API for PHP:<br>
https://developers.google.com/drive/v3/web/quickstart/php<br>
When done you should have the file `client_secret.json` and a folder `vendor` with all required libraries.

1. Run the following command in command line to approve API access and create `google_credentials.json`.<br>
You will need to enter the link and then enter the key in that link.<br>
    
    php reader.lib.php
    

