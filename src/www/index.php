<?php
declare(strict_types=1);
require_once __DIR__."/../vendor/autoload.php";
require_once __DIR__."/../bootstrap.php";

use UCRM\Common\Plugin;

echo "Home Page<br/>";

//if(session_status() === PHP_SESSION_NONE)
//    session_start();

//if(isset($_SESSION))
//    var_dump($_SESSION);

//if(isset($_COOKIE))
//    var_dump($_COOKIE);

if(isset($_GET))
    var_dump($_GET);

//echo "<pre>";
//var_dump($_SERVER);
//echo "</pre>";

//Plugin::initialize(__DIR__."/../");
//echo \UCRM\Common\Plugin::environment();