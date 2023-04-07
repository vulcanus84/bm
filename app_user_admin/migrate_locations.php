<?php

define("level","../");																//define the structur to to root directory (e.g. "../", for files in root set "")
require_once(level."inc/standard_includes.php");		//Load all necessary files (DB-Connection, User-Login, etc.)
if(!isset($_SESSION['login_user'])) { header("Location: ../index.php"); }
try
{
	$myPage = new page();
    $myPage->add_content("Migrationstool");
    $db2 = clone($db);
    $db->sql_query("SELECT * FROM users WHERE user_training_location > 0");
    while($d = $db->get_next_res())
    {
        print $d->user_id;
        $db2->insert(array('location2user_user_id'=>$d->user_id,
                            'location2user_location_id'=>$d->user_training_location)
                            ,'location2user');
    }
}
catch (Exception $e)
{
	$myPage = new page();
	$myPage->error_text = $e->getMessage();
	print $myPage->get_html_code();
}