# hagigim
Display books from Google Drive

Setup:

1. Edit `config.json` and `style.css` to fit your needs.
Note that the JSON file is a strict JSON format - **no comments, no ending commas**.
(because `json_decode()` does not allow comments or ending commas. Guhhh)

1. Setup Google v3 API for PHP<br>
https://developers.google.com/drive/v3/web/quickstart/php<br>
When done you will have the file `client_secret.json` and a folder `vendor` with all required libraries.

1. On first run (or when google credentials have expired) you'll have to follow a link and approve API access. This will create the file `google_credentials.json`
