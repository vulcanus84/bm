<?php
define('level','./');
session_start();
session_destroy();
include("inc/php/class_user.php");

$myUser = new user();

print_r($myUser);
$_SESSION['login_user'] = $myUser;