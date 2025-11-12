<?php
  define("level","../");                              //define the structur to to root directory (e.g. "../", for files in root set "")
  require_once(level."inc/standard_includes.php");    //Load all necessary files (DB-Connection, User-Login, etc.)

  try
  {
    $myPage = new page();
    $myPage->set_title("Badminton Academy");
    $myPage->set_subtitle("Übungen zeichnen");
  	if(!$myPage->is_logged_in()) { print $myPage->get_html_code(); exit; }

    $page->change_parameter('x','1');
		$_SERVER['link'] = $page->get_link();

    if(!IS_AJAX)
    {
      //Display page
      $myPage->add_css_link('inc/css/index.css');

      $myPage->add_content("<!-- The Modal -->"); 
      $myPage->add_content("<div id='myModal' class='modal' style='z-index:9999;'>"); 
      $myPage->add_content(" <!-- Modal content -->"); 
      $myPage->add_content(" <div class='modal-content'>"); 
      $myPage->add_content(" <span onclick=\"hide_modal();\" class='close'>&times;</span>"); 
      $myPage->add_content(" <p id='myModalText'>Some text in the Modal..</p>"); 
      $myPage->add_content(" </div>"); 
      $myPage->add_content("</div>");

                  
      $myPage->add_content("<div id='containment-wrapper' style=\"position:relative;border:1px solid blue;width:1000px;height:500px;border:1px solid gray;background-image:url('');background-size: contain;background-repeat: no-repeat;\">");
      $myPage->add_content("<canvas id='canvas' width='1000' height='500'></canvas>");
      $myPage->add_content("</div>");
      $myPage->add_content("<canvas style='display:none;border:5px solid green;' id='canvas2' width='1000' height='500'></canvas>");
      $myHTML = new html();

      $myPage->add_content("<div>");
      $myPage->add_content("<table cellspacing='0'><tr>");
      $myPage->add_content("<input style='display:none;' type='file' id='cameraInput'></input>");
      $myPage->add_content("<td><img src='inc/imgs/icon_player.png' style='height:30px;' id='player' onclick='set_edit_mode(\"player\");'/></td>");
      $myPage->add_content("<td><img src='inc/imgs/icon_draw.png' style='height:30px;' id='freehand' onclick='set_edit_mode(\"freehand\");'/></td>");
      $myPage->add_content("<td><img src='inc/imgs/icon_erase.png' style='height:30px;' id='erase' onclick='set_edit_mode(\"erase\");'/></td>");
      $myPage->add_content("<td><img src='inc/imgs/icon_arrow.png' style='height:30px;' id='arrow' onclick='set_edit_mode(\"arrow\");'/></td>");
      $myPage->add_content("<td><img src='inc/imgs/icon_text.png' style='height:30px;' id='text' onclick='set_edit_mode(\"text\");'/></td>");
      $myPage->add_content("<td style='border-right:3px solid black;'>&nbsp;</td>");
      $myPage->add_content("<td><img src='inc/imgs/icon_image.png' style='height:30px;' id='text' onclick='get_image_library();'/></td>");
      $myPage->add_content("<td><img src='inc/imgs/icon_camera.png' style='height:30px;' onclick=\"$('#cameraInput').trigger('click');\"/></td>");
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
      $myPage->add_content("<td id='preview_link_container' style='border-left:3px solid black;padding-left:5px;padding-right:5px;font-size:20pt;'><a id='preview_link' href='' target='_blank'/><img style='height:30px;' src='inc/imgs/icon_preview_pic.png' alt='Preview'/></a></td>");
      $myPage->add_content("</tr></table>");
      $myPage->add_content("</div>");

      if(isset($_SESSION['login_user'])) { $myPage->add_js_link(level."inc/js/jquery.ui.touch.js"); }

      $myPage->add_css_link(level.'inc/css/jquery-ui.min.css');
      $myPage->add_js_link('inc/js/main.js');  
      $myPage->add_js_link('inc/js/modal_functions.js');  
      $myPage->add_js_link('inc/js/arrow_handling.js');
      $myPage->add_js_link('inc/js/save_load_handling.js');
      $myPage->add_js_link('inc/js/draw_handling.js');  
      $myPage->add_js_link('inc/js/text_handling.js');  
      $myPage->add_js_link('inc/js/image_handling.js');  

      print $myPage->get_html_code();
    }
    else
    {
      include('inc/php/ajax.php');
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