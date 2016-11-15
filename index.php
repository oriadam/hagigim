<?php
echo 1000;
require_once "config.php";
require_once "reader.lib.php";
require_once "utils.php";
global $CONFIG;
echo 1111;
print_r($CONFIG);
echo 22222;
print_r($CONFIG["template_vars"]);
echo 333333;

//echo template_fn('index.templ.html', $CONFIG["template_vars"]);
echo 444444;
