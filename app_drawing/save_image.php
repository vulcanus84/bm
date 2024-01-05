<?php
  define("level","../");                               //define the structur to to root directory (e.g. "../", for files in root set "")
  require_once(level."inc/standard_includes.php");     //Load all necessary files (DB-Connection, User-Login, etc.)
  $img = $_POST['dataURL'];
  $img = str_replace('data:image/png;base64,', '', $img);
  $img = str_replace(' ', '+', $img);
  $fileData = base64_decode($img);

  $img_preview = $_POST['dataURL_preview'];
  $img_preview = str_replace('data:image/png;base64,', '', $img_preview);
  $img_preview = str_replace(' ', '+', $img_preview);
  $fileData_preview = base64_decode($img_preview);
  //saving

  $curr_microtime = microtime();
  $fileName = 'saved_excercises/'.$curr_microtime.'.png';
  $fileName_preview = 'saved_excercises/'.$curr_microtime.'_preview.png';

  if($_POST['drawing_id']>0)
  {
    $d = $db->sql_query_with_fetch("SELECT * FROM excercises WHERE excercise_id = '".$_POST['drawing_id']."'");
    if(file_exists($d->excercise_pic_path)) { unlink($d->excercise_pic_path); }
    $preview_path = str_replace('.png','_preview.png',$d->excercise_pic_path);
    if(file_exists($preview_path)) { unlink($preview_path); }
    $db->update(array('excercise_pic_path'=>$fileName,'excercise_bg_image'=>$_POST['bg_image']),'excercises','excercise_id',$_POST['drawing_id']);
    $arr_json_data = array('drawing_id' => $d->excercise_id,'path'=>$fileName);
    print(json_encode($arr_json_data));
}
  else
  {
    $db->insert(array('excercise_name'=>$_SESSION['login_user']->login.'_'.date('Y-m-d H:i:s'),'excercise_pic_path'=>$fileName,'excercise_bg_image'=>$_POST['bg_image']),'excercises');
    $arr_json_data = array('drawing_id' => $db->last_inserted_id,'path'=>$fileName);
    print(json_encode($arr_json_data));
    $_POST['drawing_id'] = $db->last_inserted_id;
  }
  
  file_put_contents($fileName, $fileData);
  file_put_contents($fileName_preview, $fileData_preview);
  
  $arr = json_decode($_POST['players']);
  $db->sql_query("DELETE FROM excercise2user WHERE excercise2user_excercise_id='$_POST[drawing_id]'");
  foreach($arr as $player)
  {
    $id = $player->id;
    $posX = str_replace('px','',$player->posX);
    $posY = str_replace('px','',$player->posY);
    $db->insert(array('excercise2user_user_id'=>$id,'excercise2user_excercise_id'=>$_POST['drawing_id'],'excercise2user_posx'=>$posX,'excercise2user_posy'=>$posY),'excercise2user');
  }

  $arr = json_decode($_POST['textfields']);
  $db->sql_query("DELETE FROM excercise2draggables WHERE excercise2draggable_excercise_id='$_POST[drawing_id]'");
  foreach($arr as $textfield)
  {
    $posX = str_replace('px','',$textfield->posX);
    $posY = str_replace('px','',$textfield->posY);
    $width = str_replace('px','',$textfield->width);
    $height = str_replace('px','',$textfield->height);

    $db->insert(array('excercise2draggable_excercise_id'=>$_POST['drawing_id'],
                      'excercise2draggable_typ'=>'textfield',
                      'excercise2draggable_posx'=>$posX,
                      'excercise2draggable_posy'=>$posY,
                      'excercise2draggable_width'=>$width,
                      'excercise2draggable_height'=>$height,
                      'excercise2draggable_text'=>$textfield->mytext),'excercise2draggables');
  }
?>