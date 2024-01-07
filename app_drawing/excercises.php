<?php
  define("level","../");                              //define the structur to to root directory (e.g. "../", for files in root set "")
  require_once(level."inc/standard_includes.php");    //Load all necessary files (DB-Connection, User-Login, etc.)

  print "<div style='text-align:center;width:100vw;'>";

  if(isset($_GET['user_id']))
  {
    print "<h1 style='margin-top:0;'>Übungen</h1>";

    $db->sql_query("SELECT 
                    DISTINCT(excercise2user_excercise_id),excercise_id,excercise_pic_path 
                    FROM excercise2user
                    LEFT JOIN excercises ON excercise2user_excercise_id = excercise_id
                    WHERE excercise2user_user_id='".$_GET['user_id']."'");
    while($d = $db->get_next_res())
    {
      print "<a href='".str_replace('.png','_preview.png',$d->excercise_pic_path)."'><img style='width:90vw;border:1px solid gray;' src='".str_replace('.png','_preview.png',$d->excercise_pic_path)."' onclick='load_pic(\"".$d->excercise_pic_path."\",\"".$d->excercise_id."\")'/></a>";
    }
    print "<hr style='clear:both;'/>><a style='font-size:36pt;' href='excercises.php'>Zurück</a>";
  }
  else
  {
    $db->sql_query("SELECT MAX(excercise2user_user_id) as user_id 
    FROM excercise2user 
    LEFT JOIN users ON excercise2user.excercise2user_user_id = users.user_id 
    GROUP BY excercise2user_user_id 
    ORDER BY user_firstname");
    while($d = $db->get_next_res())
    {
      $my_user = new user($d->user_id);
      print "<div style='float:left;margin:3px;text-align:center;'><a href='excercises.php?user_id=".$my_user->id."'>".$my_user->get_picture(false,'','300px',true)."</a><br/><span style='font-size:9pt;'>".$my_user->firstname."</span></div>";
    }
  }
  print "</div>";

?>