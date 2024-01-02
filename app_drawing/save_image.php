<?php
  define("level","../");                               //define the structur to to root directory (e.g. "../", for files in root set "")
  require_once(level."inc/standard_includes.php");     //Load all necessary files (DB-Connection, User-Login, etc.)
  $img = $_POST['dataURL'];
  $img = str_replace('data:image/png;base64,', '', $img);
  $img = str_replace(' ', '+', $img);
  $fileData = base64_decode($img);
  //saving

  if($_POST['drawing_id']>0)
  {
    $d = $db->sql_query_with_fetch("SELECT * FROM excercises WHERE excercise_id = '".$_POST['drawing_id']."'");
    if(file_exists($d->excercise_pic_path)) { unlink($d->excercise_pic_path); }
    $fileName = 'saved_excercises/'.microtime().'.png';
    $db->update(array('excercise_pic_path'=>$fileName,'excercise_bg_image'=>$_POST['bg_image']),'excercises','excercise_id',$_POST['drawing_id']);
    print $d->excercise_id;
  }
  else
  {
    $fileName = 'saved_excercises/'.microtime().'.png';
    $db->insert(array('excercise_name'=>$_SESSION['login_user']->login.'_'.date('Y-m-d H:i:s'),'excercise_pic_path'=>$fileName,'excercise_bg_image'=>$_POST['bg_image']),'excercises');
    print $db->last_inserted_id;
    $_POST['drawing_id'] = $db->last_inserted_id;
  }
  file_put_contents($fileName, $fileData);
  $arr = json_decode($_POST['players']);
  $db->sql_query("DELETE FROM excercise2user WHERE excercise2user_excercise_id='$_POST[drawing_id]'");
  foreach($arr as $player)
  {
    $id = $player->id;
    $posX = str_replace('px','',$player->posX);
    $posY = str_replace('px','',$player->posY);
    $txt = $id."->".$posX."->".$posY."->\n";
    $db->insert(array('excercise2user_user_id'=>$id,'excercise2user_excercise_id'=>$_POST['drawing_id'],'excercise2user_posx'=>$posX,'excercise2user_posy'=>$posY),'excercise2user');
  }

?>