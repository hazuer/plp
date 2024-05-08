<?php
defined('_VALID_MOS') or die('Restricted access');

$userRoot = '';
$docRoot  = $_SERVER['SERVER_NAME'];
#define('BASE_URL','https://'.$docRoot."/".$userRoot);
define('BASE_URL','https://'.$docRoot);
#var_dump(BASE_URL);

define('PAGE_TITLE','Paquetería - Los Pinos');

//RESP

define('HOST','localhost');
define('USERNAME','root');
define('PASSWD','');
define('DBNAME','jt_local');
define('PORT','3306');
define('SOCKET','null');
define('NODE_PATH_FILE','D:/Programs/laragon/www/jt/nodejs/'); //ENZ



/*
//LOCAL-TLAQUI
define('HOST','srv1134.hstgr.io');
define('USERNAME','u611824705_admin');
define('PASSWD','FJ4t82*i');
define('DBNAME','u611824705_jt');
define('PORT','3306');
define('SOCKET','null');
//define('NODE_PATH_FILE','C:/laragon/www/jt/nodejs/'); //Local
define('NODE_PATH_FILE','D:/Programs/laragon/www/jt/nodejs/'); //ENZ
*/





//PROD-HOSTINGER
/*
define('HOST','127.0.0.1');
define('USERNAME','u611824705_admin');
define('PASSWD','FJ4t82*i');
define('DBNAME','u611824705_jt');
define('PORT','3306');
define('SOCKET','null');
define('NODE_PATH_FILE','files/public_html/jt/nodejs/'); // PROD

*/



?>