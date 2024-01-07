<?php
  define("level","../");                              //define the structur to to root directory (e.g. "../", for files in root set "")
  require_once(level."inc/standard_includes.php");    //Load all necessary files (DB-Connection, User-Login, etc.)

  print "<style>a.link { margin:0 auto;width:90vw;font-size:48pt;color:black;display:block;border:1px solid gray;padding:2vw;border-radius:50px;background-color:#DDD; }</style>";
  print "<div style='text-align:center;width:100vw;'>";
  if(isset($_GET['action']) && $_GET['action']=='logout') { session_destroy(); header("Location: excercises.php"); }
  if(isset($_POST['pw']) && $_POST['pw']=='bczofingen')
  {
    $_SESSION['pw'] = 'bczofingen';
    header('Location: excercises.php');
  }

  if(isset($_SESSION['pw']) && $_SESSION['pw']=='bczofingen')
  {
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
      print "<hr style='clear:both;'/>><a class='link' href='excercises.php'>Zurück</a>";
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
        print "<div style='float:left;margin:3px;text-align:center;'><a href='excercises.php?user_id=".$my_user->id."'>".$my_user->get_picture(false,'','300px',true)."</a><br/><span style='font-size:36pt;'>".$my_user->firstname."</span></div>";
      }
      print "<hr style='clear:both;'/><a class='link' href='excercises.php?action=logout'>Logout</a>";
    }
  }
  else {
    print "<form id='login' method='POST' target='excercises.php'>
      <span style='font-size:48pt;'>Bitte Passwort eingeben:</span><br/>
      <input type='password' name='pw' style='width:90vw;height:100px;font-size:48pt;'/><br/>
      <input type='submit' style='font-size:48pt;'/>
      </form>";
  }
  print "</div>";

?>