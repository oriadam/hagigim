# hagigim
Display beautiful digital books based on Google Drive

**Setup:**

1. Setup Google v3 API for PHP, Web Server<br>
<https://developers.google.com/drive/v3/web/quickstart/php><br>
When done you will have the file `client_secret.json` and a folder `vendor` with all required libraries.

1. Change your manager password in `custom/config-default.json` at `manager_password_md5` (stored as MD5 hash).
2. Visit the `manager.php` page. Use the password you just set.<br>
Click Validate Google Auth. You will have to follow a link, approve API access, and enter the token back inside the box.<br>
Click Validate Google Auth again, and make sure you get the OK.<br>
Click clear cache (file, list)<br>
Click Test cache (file, list) and make sure you get the OK.<br> 
Click Edit Configuration.<br>

**Notes:**

* Requires PHP 5.5 or higher
* JSON files must be strict - meaning **no comments, no ending commas**.
* Doc files must have view permission to the Google user, and must be in Google-Doc format (not Word).
* Before first run please visit the `manager.php` page, validate Google API, test and reset cache folders.
* You can add additional config files in custom folder, for example `custom/config-xxx.json`; and access custom configs via search query `"?cfg=xxx"`.
The `custom/config-default.json` file is loaded first in any case.
Try adding "?cfg=myCustomConfig" for example (see the window title changes).

**Configuring the toolbar:**
You can choose which buttons will go to which one of the four availalbe toolbars.
The buttons names are keys in the global var `tb_items`.
See `toolbar.js`
Each item element can have the following optional properties.
  + `icon: 'fa-icon'` classname of the icon, expected to be a fontawesome class. see http://fontawesome.io/icons/
  + `init: function(item)` will trigger on page load
  + `f: function(item)` will trigger on button click
  + `toggle: true` if the button has two states. see `active` and `icon_active`
  + `active: function(item)` return weather the item is currently on active state
  + `icon_active: 'fa-icon-alt'` classname for when the button is active (optional)



**Adding AddThis sharing service:**

+ Go to AddThis.com, sign up or log in to your account
+ Go to Tools and add:
  + Share Button
  + Type: Inline
  + Configure them however you see fit

+ Make sure there are not other tools except one inline
+ Save and copy the code
+ On the management panel find "AddThis Inline Code" and paste the code
