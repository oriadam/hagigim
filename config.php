<?php 
global $CONFIG;
$CONFIG = json_decode(file_get_contents('config.json'),true);
