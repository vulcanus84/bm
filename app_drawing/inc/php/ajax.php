<?php
if($_GET['ajax']=='load_pictures') 
{ 
  print "<div id='players'>";
  print "<h1 style='margin-top:0;'>Spieler</h1>";
  $db->sql_query("SELECT MAX(excercise2user_user_id) as user_id 
                  FROM excercise2user 
                  LEFT JOIN users ON excercise2user.excercise2user_user_id = users.user_id 
                  GROUP BY excercise2user_user_id 
                  ORDER BY user_firstname");
  print "<div class='player'><div onclick='filter_user(0);' style='width:80px;height:80px;border:1px solid gray;border-radius:40px;background-color:#EEE;cursor:pointer;font-size:20pt;display: table-cell; vertical-align: middle;'>Alle</div><span style='font-size:9pt;'>Alle</span></div>";
  while($d = $db->get_next_res())
  {
    $my_user = new user($d->user_id);
    print "<div class='player'><img id='{$d->user_id}' class='player_div' src='{$my_user->get_pic_path(true)}'/><br/>{$my_user->firstname}</div>";
  }
  print "</div>";
  print "<div id='excersises'>";
  print get_excercises($db,0);
  print "</div>";
}

if($_GET['ajax']=='get_excercises') 
{ 
  print get_excercises($db,$_GET['user_id']);
}

if($_GET['ajax']=='del_warning') 
{ 
  print "Wollen sie diese Zeichnung wirklich löschen?<p/>
          <button style='background-color:red;' onclick='del_from_db()'>Ja</button>
          <button onclick='$(\"#myModal\").hide();'>Nein</button>
          <button style='background-color:purple;' onclick='close_pic()'>Nur Arbeitsfläche löschen</button>
          ";
}

if($_GET['ajax']=='del_changes_warning') 
{ 
  print "Es gibt ungespeicherte Änderungen. Wollen Sie die Zeichnung wirklich schliessen?<p/><button style='background-color:red;' onclick='close_pic()'>Ja</button><button onclick='$(\"#myModal\").hide();'>Nein</button>";
}

if($_GET['ajax']=='get_pic_path') 
{ 
  $my_user = new user($_GET['user_id']);
  if(isset($_GET['x']))
  {
    print json_encode(array($my_user->get_pic_path(true),$_GET['x'],$_GET['y'],$_GET['user_id']));
  }
  else
  {
    print $my_user->get_pic_path(true);
  }
}

if($_GET['ajax']=='del_from_db') 
{
  $d = $db->sql_query_with_fetch("SELECT * FROM excercises WHERE excercise_id='".$_POST['id']."'");
  if(file_exists($d->excercise_pic_path)) { unlink($d->excercise_pic_path); }
  $preview_path = str_replace('.png','_preview.png',$d->excercise_pic_path);
  if(file_exists($preview_path)) { unlink($preview_path); }
  $db->delete('excercises','excercise_id',$_POST['id']); 
}

if($_GET['ajax']=='publish_pic')
{
  $db->sql_query("SELECT * FROM excercises WHERE excercise_id='".$_POST['id']."'");
  if($db->count()=='1')
  {
    $db->update(array('excercise_status'=>$_POST['pub_status']),'excercises','excercise_id',$_POST['id']);
  }
}

if($_GET['ajax']=='get_players') 
{
  $arr_json_data = array();
  $db->sql_query("SELECT * FROM excercise2user WHERE excercise2user_excercise_id='".$_GET['excercise_id']."'");
  while($d=$db->get_next_res())
  {
    $arr_json_data[] = array('user_id' => $d->excercise2user_user_id, 'posx' => $d->excercise2user_posx,'posy' => $d->excercise2user_posy);
  }
  print(json_encode($arr_json_data));
}

if($_GET['ajax']=='get_draggables') 
{
  $arr_json_data = array();
  $db->sql_query("SELECT * FROM excercise2draggables WHERE excercise2draggable_excercise_id='".$_GET['excercise_id']."'");
  while($d=$db->get_next_res())
  {
    $arr_json_data[] = array('typ'=>$d->excercise2draggable_typ,'text' => $d->excercise2draggable_text,'pic_path' => $d->excercise2draggable_pic_path, 'posx' => $d->excercise2draggable_posx,'posy' => $d->excercise2draggable_posy, 'width' => $d->excercise2draggable_width,'height' => $d->excercise2draggable_height);
  }
  print(json_encode($arr_json_data));
}

if($_GET['ajax']=='get_excercise_details') 
{
  $d = $db->sql_query_with_fetch("SELECT * FROM excercises WHERE excercise_id='".$_GET['excercise_id']."'");
  $arr_json_data = array('bg_image' => $d->excercise_bg_image,'publish_status'=>$d->excercise_status);
  print(json_encode($arr_json_data));
}

if($_GET['ajax']=='get_image_library') 
{
  if ($handle = opendir('img_library')) 
  {
    while (false !== ($entry = readdir($handle))) 
    {
        if ($entry != "." && $entry != "..") 
        {
          print "<img onclick=\"add_from_img_library('img_library/".$entry."')\" src='img_library/".$entry."' style='float:left;height:100px;margin:5px;' />";
        }
    }
    print "<hr style='clear:both'/>";
    closedir($handle);
  }
}
