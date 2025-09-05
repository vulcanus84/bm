<?php
  define("level","../");                              //define the structur to to root directory (e.g. "../", for files in root set "")
  require_once(level."inc/standard_includes.php");    //Load all necessary files (DB-Connection, User-Login, etc.)

  try
  {
    $myPage = new page();
		$page->change_parameter('x','1');
		$_SERVER['link'] = $page->get_link();

    if(!IS_AJAX)
    {
      //Display page
      $myPage->set_title("Badminton Academy");
      $myPage->set_subtitle("Übungen zeichnen");

      $myPage->add_css("
                      .active { border-bottom:5px solid #AAAAFF;border-top:5px solid #AAAAFF; }
                      .inactive { border:5px solid white; }
                      ");

      $myPage->add_content("<!-- The Modal -->");
      $myPage->add_content("<div id='myModal' class='modal' style='z-index:9999;'>");
      $myPage->add_content("  <!-- Modal content -->");
      $myPage->add_content("  <div class='modal-content'>");
      $myPage->add_content("    <span onclick=\"hide_modal();\" class='close'>&times;</span>");
      $myPage->add_content("    <p id='myModalText'>Some text in the Modal..</p>");
      $myPage->add_content("  </div>");
      $myPage->add_content("</div>");
                  
      $myPage->add_content("<div id='containment-wrapper' style=\"position:relative;border:1px solid blue;width:1000px;height:500px;border:1px solid gray;background-image:url('');background-size: contain;background-repeat: no-repeat;\">");
      $myPage->add_content("<canvas id='canvas' width='1000' height='500'></canvas>");
      $myPage->add_content("</div>");
      $myPage->add_content("<canvas style='display:none;border:5px solid green;' id='canvas2' width='1000' height='500'></canvas>");
      $myHTML = new html();

      $myPage->add_content("<div>");
      $myPage->add_content("<table cellspacing='0'><tr>");
      $myPage->add_content("<input style='display:none;' type='file' id='cameraInput'></input>");
      $myPage->add_content("<td><img src='imgs/icon_player.png' style='height:30px;' id='player' onclick='set_edit_mode(\"player\");'/></td>");
      $myPage->add_content("<td><img src='imgs/icon_draw.png' style='height:30px;' id='freehand' onclick='set_edit_mode(\"freehand\");'/></td>");
      $myPage->add_content("<td><img src='imgs/icon_erase.png' style='height:30px;' id='erase' onclick='set_edit_mode(\"erase\");'/></td>");
      $myPage->add_content("<td><img src='imgs/icon_arrow.png' style='height:30px;' id='arrow' onclick='set_edit_mode(\"arrow\");'/></td>");
      $myPage->add_content("<td><img src='imgs/icon_text.png' style='height:30px;' id='text' onclick='set_edit_mode(\"text\");'/></td>");
      $myPage->add_content("<td style='border-right:3px solid black;'>&nbsp;</td>");
      $myPage->add_content("<td><img src='imgs/icon_image.png' style='height:30px;' id='text' onclick='get_image_library();'/></td>");
      $myPage->add_content("<td><img src='imgs/icon_camera.png' style='height:30px;' onclick=\"$('#cameraInput').trigger('click');\"/></td>");
      $myPage->add_content("<td style='border-right:3px solid black;'>&nbsp;</td>");
      $myPage->add_content("<td id='color_picker' style='border-right:3px solid black;'><table><tr>");
      $myPage->add_content("<td id='arrow_no_picker'>".$myHTML->get_selection_with_array('1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20','arrow_no','onchange=change_arrow_no()')."</td>");
      $array_color = array('black','green','red','blue','purple','orange','yellow');
      foreach($array_color as $color)
      {
        $myPage->add_content("<td><span id='color_".$color."'  style='height: 25px;width: 25px;background-color:".$color.";border-radius: 50%;display: inline-block;' onclick='change_color(\"".$color."\");'></span></td>");
      }
      $myPage->add_content("</tr></table></td>");

      //Check permissions
      $db->sql_query("SELECT * FROM location_permissions
                            LEFT JOIN locations ON loc_permission_loc_id = location_id
                            WHERE loc_permission_user_id='".$_SESSION['login_user']->id."'");
      $w_str = " AND (";
      $i=0;
      while($d = $db->get_next_res())
      {
        if($i==0) { $w_str.= "location2user_location_id='$d->location_id'"; } else { $w_str.= " OR location2user_location_id='$d->location_id'"; }
        $i++;
      }
      $w_str.= ")";

      $db->sql_query("SELECT DISTINCT CONCAT(user_firstname,' ', user_lastname) as user_fullname, user_id FROM users 
                      LEFT JOIN location2user ON location2user_user_id = user_id 
                      LEFT JOIN locations ON location2user_location_id = location_id
                      WHERE user_firstname != '' AND user_hide!='1' AND user_id>1 $w_str
                      ORDER BY user_fullname");
      $myPage->add_content("<td id='player_picker' style='border-right:3px solid black;'>".$myHTML->get_selection($db,'user1','user_id','user_fullname','')."<button id='add_player' onclick='add_player();'>Einfügen</button></td>");

      $myPage->add_content("<td><select id='select_bg' onchange='change_background();'></select></td>");
      $myPage->add_content("<td style='border-right:3px solid black;'>&nbsp;</td>");
      $myPage->add_content("<td><button id='erase_pic' style='background-color:red;' onclick='close_pic();'>Zeichnung löschen</button></td>");
      $myPage->add_content("<td><button id='load_pic' style='background-color:blue;' onclick='show_pics();'>Laden</button></td>");
      $myPage->add_content("<td><button id='del_pic' style='background-color:red;' onclick='show_del_warning();'>Löschen</button></td>");
      $myPage->add_content("<td><button id='save_pic' style='background-color:orange'  onclick='save_pic();'>Speichern</button></td>");
      $myPage->add_content("<td><button id='publish_pic' style='background-color:green;' onclick='publish_pic();'>Publizieren</button></td>");
      $myPage->add_content("<td><button id='save_copy' style='background-color:purple'  onclick='save_copy();'>Kopie</button></td>");
      $myPage->add_content("<td id='preview_link_container' style='border-left:3px solid black;padding-left:5px;padding-right:5px;font-size:20pt;'><a id='preview_link' href='' target='_blank'/><img style='height:30px;' src='imgs/icon_preview_pic.png' alt='Preview'/></a></td>");
      $myPage->add_content("</tr></table>");
      $myPage->add_content("</div>");

      if(isset($_SESSION['login_user'])) { $myPage->add_js_link(level."inc/js/jquery.ui.touch.js"); }

      $myPage->add_css_link(level.'inc/css/jquery-ui.min.css');
      $myPage->add_js_link('js/main.js');  
      $myPage->add_js_link('js/modal_functions.js');  
      $myPage->add_js_link('js/arrow_handling.js');
      $myPage->add_js_link('js/save_load_handling.js');
      $myPage->add_js_link('js/draw_handling.js');  
      $myPage->add_js_link('js/text_handling.js');  
      $myPage->add_js_link('js/image_handling.js');  

      print $myPage->get_html_code();
    }
    else
    {
      //Return the requested data
      if($_GET['ajax']=='load_pictures') 
      { 
        print "<div style='height:50vh;'>";
        print "<div style='width:20vw;overflow:auto;height:50vh;float:left;border-right:3px solid gray;margin:5px;'>";
        print "<h1 style='margin-top:0;'>Spieler</h1>";
        $db->sql_query("SELECT MAX(excercise2user_user_id) as user_id 
                        FROM excercise2user 
                        LEFT JOIN users ON excercise2user.excercise2user_user_id = users.user_id 
                        GROUP BY excercise2user_user_id 
                        ORDER BY user_firstname");
        print "<div style='float:left;margin:3px;text-align:center;'><div onclick='filter_user(0);' style='width:80px;height:80px;border:1px solid gray;border-radius:40px;background-color:#EEE;cursor:pointer;font-size:20pt;display: table-cell; vertical-align: middle;'>Alle</div><span style='font-size:9pt;'>Alle</span></div>";
        while($d = $db->get_next_res())
        {
          $my_user = new user($d->user_id);
          print "<div style='float:left;margin:3px;text-align:center;'>".$my_user->get_picture(false,'filter_user','80px',true)."<br/><span style='font-size:9pt;'>".$my_user->firstname."</span></div>";
        }
        print "</div>";
        print "<div id='excersises' style='width:50vw;float:left;overflow:auto;height:50vh;margin:5px;'>";
        print get_excercises($db,0);
        print "</div>";
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
    }
  }
  catch (Exception $e)
  {
    $myPage = new page();
    $myPage->error_text = $e->getMessage();
    print $myPage->get_html_code();
  }

function get_excercises($db,$user_id)
{
  $txt = "<h1 style='margin-top:0;'>Übungen</h1>";
  if($user_id>0)
  {
    $db->sql_query("SELECT 
    DISTINCT(excercise2user_excercise_id),excercise_id,excercise_pic_path 
    FROM excercise2user
    LEFT JOIN excercises ON excercise2user_excercise_id = excercise_id
    WHERE excercise2user_user_id='".$user_id."'");
  }
  else
  {
    $db->sql_query("SELECT * FROM excercises");
  }
  while($d = $db->get_next_res())
  {
    $txt.= "<img style='float:left;width:15vw;border:1px solid gray;margin:0px 5px 5px 0px;' src='".str_replace('.png','_preview.png',$d->excercise_pic_path)."' onclick='load_pic(\"".$d->excercise_pic_path."\",\"".$d->excercise_id."\")'/>";
  }
  return $txt;
}

?>