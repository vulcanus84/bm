<?php
  define("level","../");                               //define the structur to to root directory (e.g. "../", for files in root set "")
  require_once(level."inc/standard_includes.php");     //Load all necessary files (DB-Connection, User-Login, etc.)
  require_once(level."inc/php/class_query.php");       //Load class query for the grid (includes class column)

  try
  {
    //Display page
    $myPage = new page();
    $_SERVER['link'] = $page->get_link();

    $myPage->add_js("

      function check_for_buttons()
      {
        var check = true;
        var source_user_id = $('#user2 option:selected').attr('value');
        var target_user_id = $('#user1 option:selected').attr('value');
        if(source_user_id < 1 || target_user_id < 1) { check = false; }
        if(source_user_id == target_user_id) { check = false; }
        if(check) { $('#button_div').css('visibility', 'visible'); } else { $('#button_div').css('visibility', 'hidden'); }
        
      }

      function show_infos(field)
      {
        if(field=='from')
        {
          var user_id = $('#user1 option:selected').attr('value');
          $('#user_1').load('$_SERVER[link]?ajax=get_user&user_id='+user_id);
        }
        if(field=='to')
        {
          var user_id = $('#user2 option:selected').attr('value');
          $('#user_2').load('$_SERVER[link]?ajax=get_user&user_id='+user_id);
        }
        check_for_buttons();
      }

      function assign_pic(source_field)
      {
        if(source_field=='from')
        {
          var source_user_id = $('#user2 option:selected').attr('value');
          var target_user_id = $('#user1 option:selected').attr('value');
        }
        if(source_field=='to')
        {
          var source_user_id = $('#user1 option:selected').attr('value');
          var target_user_id = $('#user2 option:selected').attr('value');
        }
        
        var my_url = '$_SERVER[link]?ajax=assign_pic&source_user_id=' + source_user_id + '&target_user_id=' + target_user_id;
        $.ajax({ url: my_url }).done(
        function(data)
        {
          show_infos('from'); show_infos('to');
        });
      }

      function assign_tournaments(source_field)
      {
        if(source_field=='from')
        {
          var source_user_id = $('#user2 option:selected').attr('value');
          var target_user_id = $('#user1 option:selected').attr('value');
        }
        if(source_field=='to')
        {
          var source_user_id = $('#user1 option:selected').attr('value');
          var target_user_id = $('#user2 option:selected').attr('value');
        }
        
        var my_url = '$_SERVER[link]?ajax=assign_tournaments&source_user_id=' + source_user_id + '&target_user_id=' + target_user_id;
        $.ajax({ url: my_url }).done(
        function(data)
        {
          show_infos('from'); show_infos('to');
        });
      }

      function assign_stars(source_field)
      {
        if(source_field=='from')
        {
          var source_user_id = $('#user2 option:selected').attr('value');
          var target_user_id = $('#user1 option:selected').attr('value');
        }
        if(source_field=='to')
        {
          var source_user_id = $('#user1 option:selected').attr('value');
          var target_user_id = $('#user2 option:selected').attr('value');
        }
        
        var my_url = '$_SERVER[link]?ajax=assign_stars&source_user_id=' + source_user_id + '&target_user_id=' + target_user_id;
        $.ajax({ url: my_url }).done(
        function(data)
        {
          show_infos('from'); show_infos('to');
        });
      }

  	");

  if(!IS_AJAX)
    {
      $myPage->set_title("Administration");
      $myPage->set_subtitle("Passwort setzen");
      include('menu.php');
      $myPage->menu = $myMenu->create_menu("tabsJ");
      $myHTML = new html($db);
      $myPage->add_content("<h1>Benutzer zusammenführen</h1>");
      $myPage->add_content("<div style=''>");

      $myPage->add_content("<div style='width:33%;float:left;border-right:3px solid gray;'>");
      $db->sql_query($myPage->get_setting('sql_user_selection'));
      $myPage->add_content("<div style='text-align:center;'>".$myHTML->get_selection($db,'user1','user_id','user_fullname',"onchange='show_infos(\"from\")'")."</div>");
      $myPage->add_content("<div id='user_1' style='text-align:center;'></div>");
      $myPage->add_content("</div>");

      $myPage->add_content("<div id='button_div' style='width:33%;left:33.3%;position:absolute;visibility:hidden;'>");
      $db->sql_query($myPage->get_setting('sql_user_selection'));
      $myPage->add_content("<div style='text-align:center;'>
                              <div style='background-color:#8888FF;border-radius:5vw;width:80%;margin:0 auto;padding:1vw;'>
                                Bild übertragen<br/>
                                <button onclick='assign_pic(\"from\");' style='background-color:gray;'><<</button>&nbsp;<button onclick='assign_pic(\"to\");' style='background-color:gray;'>>></button><br/>
                              </div>
                              <div style='background-color:#88FF88;border-radius:5vw;width:80%;margin:0 auto;padding:1vw;'>
                                Turniere übertragen<br/>
                                <button onclick='assign_tournaments(\"from\");' style='background-color:gray;'><<</button>&nbsp;<button onclick='assign_tournaments(\"to\");' style='background-color:gray;'>>></button><br/>
                              </div>
                              <div style='background-color:#FF8888;border-radius:5vw;width:80%;margin:0 auto;padding:1vw;'>
                                Sternchen übertragen<br/>
                                <button onclick='assign_stars(\"from\");' style='background-color:gray;'><<</button>&nbsp;<button onclick='assign_stars(\"to\");' style='background-color:gray;'>>></button><br/>
                              </div>
                            </div>");
      $myPage->add_content("</div>");

      $myPage->add_content("<div style='width:33%;float:right;border-left:3px solid gray;'>");
      $db->sql_query($myPage->get_setting('sql_user_selection'));
      $myPage->add_content("<div style='text-align:center;'>".$myHTML->get_selection($db,'user2','user_id','user_fullname',"onchange='show_infos(\"to\")'")."</div>");
      $myPage->add_content("<div id='user_2' style='text-align:center;'></div>");
      $myPage->add_content("</div>");

      $myPage->add_content("</div>");

      print $myPage->get_html_code();
    }
    else
    {
      //************************************************************************************
      //AJAX Handling
      //************************************************************************************
      if($_GET['ajax']=='assign_stars')
      {
        $s_user_id = $_GET['source_user_id'];
        $t_user_id = $_GET['target_user_id'];
        
        $db->sql_query("UPDATE exam2user SET exam2user_user_id = '".$t_user_id."' WHERE exam2user_user_id='".$s_user_id."'");

        $my_user = new user($s_user_id);
        $my_user->create_star_image();

        $my_user = new user($t_user_id);
        $my_user->create_star_image();
      }

      if($_GET['ajax']=='assign_tournaments')
      {
        $s_user_id = $_GET['source_user_id'];
        $t_user_id = $_GET['target_user_id'];
        
        $db->sql_query("UPDATE group2user SET group2user_user_id = '".$t_user_id."'WHERE group2user_user_id='".$s_user_id."'");

        $db->sql_query("UPDATE games SET game_player1_id = '".$t_user_id."' WHERE game_player1_id='".$s_user_id."'");
        $db->sql_query("UPDATE games SET game_player2_id = '".$t_user_id."' WHERE game_player2_id='".$s_user_id."'");
        $db->sql_query("UPDATE games SET game_player3_id = '".$t_user_id."' WHERE game_player3_id='".$s_user_id."'");
        $db->sql_query("UPDATE games SET game_player4_id = '".$t_user_id."' WHERE game_player4_id='".$s_user_id."'");

        $db->sql_query("UPDATE games SET game_winner_id = '".$t_user_id."' WHERE game_winner_id='".$s_user_id."'");
        $db->sql_query("UPDATE games SET game_winner2_id = '".$t_user_id."' WHERE game_winner2_id='".$s_user_id."'");
      }

      if($_GET['ajax']=='assign_pic')
      {
        $main_path = '../app_user_admin/user_pics/';
        $s_user_id = $_GET['source_user_id'];
        $t_user_id = $_GET['target_user_id'];
        print "Path: ".$main_path.$s_user_id."\n";

        $arr_suffix =  array(".png","_t.png","_stars.png","_stars_t.png");

        foreach($arr_suffix as $suffix)
        {
          if(file_exists($main_path.$s_user_id.$suffix)) { print "Copy: ".$main_path.$s_user_id.$suffix." to ".$main_path.$t_user_id.$suffix."\n"; copy($main_path.$s_user_id.$suffix,$main_path.$t_user_id.$suffix); } else { print "Not found: ".$main_path.$s_user_id.".png\n"; }
        }
        $my_user = new user($t_user_id);
        $my_user->create_star_image();
      }

      if($_GET['ajax']=='get_user')
      {
        if(isset($_GET['user_id']) && $_GET['user_id']>0)
        {
          $my_user = new user($_GET['user_id']);
          print "<table style='width:80%;margin:0 auto;'>";
          print "<tr><td style='text-align:center;'>";
          print "<img style='margin:auto;width:50%;' src='".$my_user->get_pic_path()."?".time()."'></img>";
          print "</tr></td>";
          print "<tr><td style='text-align:center;font-size:3vw;border-bottom:1px solid black;'>";
          print "<b>".$my_user->firstname." ".$my_user->lastname."</b></br/>";
          print $my_user->birthday."<br/>";
          print "ID: ".$my_user->id;
          print "</tr></td>";
          print "<tr><td style='text-align:center;font-size:16pt;border-bottom:1px solid black;'>";
          print "<b>Turniere</b></ul>";
  
          $db->sql_query("SELECT *, DATE_FORMAT(group_created,'%d.%m.%Y') as group_created_c FROM group2user
          LEFT JOIN groups ON group2user_group_id = groups.group_id
          WHERE group2user_user_id='".$my_user->id."'
          ORDER BY group_created DESC");
          while($d = $db->get_next_res())
          {
            print "<li style='list-style:none;font-size:10pt;'><a href='".level."app_tournament/index.php?tournament_id=".$d->group_id."'>".$d->group_created_c." - ".$d->group_title."</a></li>";
          }
          print "</ul>";
          print "</tr></td>";
          print "<tr><td style='text-align:center;font-size:16pt;border-bottom:1px solid black;'>";
          print "<b>Trainingsorte</b></ul>";
          $db->sql_query("SELECT * FROM location2user 
                            LEFT JOIN locations on location2user_location_id = locations.location_id
                            WHERE location2user_user_id='".$my_user->id."'");
          while($d = $db->get_next_res())
          {
            print "<li style='list-style:none;font-size:12pt;'>".$d->location_name."</li>";
          }
          print "</ul>";
          print "</tr></td>";
          print "<tr><td style='text-align:center;font-size:16pt;border-bottom:1px solid black;'>";
          print "<b>Berechtigungen</b></ul>";
          $db->sql_query("SELECT * FROM location_permissions
                            LEFT JOIN locations on loc_permission_loc_id = locations.location_id
                            WHERE loc_permission_user_id='".$my_user->id."'");
          while($d = $db->get_next_res())
          {
            print "<li style='list-style:none;font-size:12pt;'>".$d->location_name."</li>";
          }
          $db->sql_query("SELECT * FROM permissions
                            WHERE permission_user_id='".$my_user->id."'");
          while($d = $db->get_next_res())
          {
            print "<li style='list-style:none;font-size:12pt;'>".$d->permission_path."</li>";
          }
          print "</ul>";
          print "</tr></td>";
          print "</table>";
  
        }
        else
        {
          print "";
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
?>