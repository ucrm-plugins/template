<?php
declare(strict_types=1);
require_once __DIR__ . "/../vendor/autoload.php";

use UCRM\Sessions\SessionUser;

/** @noinspection PhpUnusedLocalVariableInspection */
/** @var SessionUser $user */


echo "Home Page<br/>";

//var_dump($user);

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

