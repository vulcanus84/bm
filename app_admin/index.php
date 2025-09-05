<?php
  define("level","../");                               //define the structur to to root directory (e.g. "../", for files in root set "")
  require_once(level."inc/standard_includes.php");     //Load all necessary files (DB-Connection, User-Login, etc.)
  require_once(level."inc/php/class_query.php");       //Load class query for the grid (includes class column)

  function show_dir($dir, $isSubfolder,$compare_string)
  {
    if(!isset($_GET['txt'])) { $_GET['txt'] = ""; }
    $handle = @opendir( $dir );
    if (is_resource($handle))
    {
      while (($file = readdir($handle)) !== false )
      {
        if (is_dir($dir.$file))
        {
          if(substr($file,0,4)=='app_')
          {
            $_GET['txt'] .= "<img src='../inc/imgs/query/plus.gif' onclick=\"if(document.getElementById('$file').style.display=='block') { document.getElementById('$file').style.display='none'; } else { document.getElementById('$file').style.display='block'; }\"/>";
            $_GET['txt'] .= "<input type='checkbox' name='cb[]' value='$file'";
            if(strpos($compare_string,$file.";")!==FALSE) { $_GET['txt'] .= " disabled='1' checked='1'";}
            $_GET['txt'] .= "/> ".$file."<br>\n";
            $_GET['txt'] .= "<div id='$file' style='margin-left:30px;display:none;'>\n";
            show_dir($dir.$file.'/', true,$compare_string);
          }
        }
        else
        {
          if($isSubfolder)
          {
            if(substr($file,strpos($file,"."))=='.php' AND $file!='menu.php')
            {
              $_GET['txt'] .= "  &nbsp;&nbsp;&nbsp;<input type='checkbox' name='cb[]' value='".$dir.$file."'";
              if(strpos($compare_string,str_replace("../","",$dir.$file))!==FALSE) { $_GET['txt'] .= " disabled='1' checked='1'";}
              $_GET['txt'] .= "/> ".$file."<br>\n";
            }
          }
        }
      }
      $_GET['txt'] .= "</div>\n";
      closedir($handle);
    }
    //Remove last </div>
    return substr($_GET['txt'],0,strlen($_GET['txt'])-8)."\n";
  }

  $myPage = new page();
  $myPage->set_title("Administration");
  $myPage->set_subtitle("Berechtigungen");

  try
  {
    if(isset($_GET['action']) && $_GET['action']=='ma_change')
    {
      $page->remove_parameter('action');
      $page->change_parameter('user_id',$_POST['user_id']);
      header("Location: ".$page->get_link());
    }

    if(isset($_GET['action']) && $_GET['action']=='append')
    {
      if(isset($_POST['cb']))
      {
        foreach($_POST['cb'] as $checkbox)
        {
          try
          {
            $db->sql_query("SELECT * FROM permissions WHERE permission_path='".str_replace("../","",$checkbox)."' AND permission_user_id='$_GET[user_id]'");
            if($db->count()==0)
            {
              $db->sql_query("INSERT INTO permissions (permission_path,permission_user_id,permission_read) VALUES ('".str_replace("../","",$checkbox)."','".$_GET['user_id']."','1')");
            }
            header("Location: ".$page->remove_parameter('action'));
          }
          catch (Exception $e)
          {
             $myPage->error_text = $e->getMessage();
          }
        }
      }
    }
    if(!isset($_GET['user_id'])) { $_GET['user_id'] = ""; }

    $myQuery = new query();
    $myQuery->set_default_order_by("permission_path");
    $myQuery->set_default_where("user_id='$_GET[user_id]'");
    $myQuery->set_sql_table("permissions");
    $myQuery->set_sql_select("SELECT * FROM ".$myQuery->get_sql_table()." LEFT JOIN users ON permission_user_id = users.user_id");
    $myQuery->width = 450;
    $myQuery->height = 350;
    $myQuery->set_edit_mode('edit_remove');
    $myQuery->set_reload(true);
    $myColumn = new column("permission_path","Pfad"); $myColumn->set_width(150); $myColumn->set_edit_typ('not_editable'); $myQuery->add_column($myColumn);
    $myColumn = new column("permission_read","Read"); $myColumn->set_width(30); $myColumn->set_edit_typ('checkbox'); $myQuery->add_column($myColumn);
    $myColumn = new column("permission_write","Write"); $myColumn->set_width(30); $myColumn->set_edit_typ('checkbox'); $myQuery->add_column($myColumn);
    $myColumn = new column("permission_delete","Add/Delete"); $myColumn->set_width(50); $myColumn->set_edit_typ('checkbox'); $myQuery->add_column($myColumn);

    if(!IS_AJAX)
    {
      include('menu.php');
      $db->sql_query($myQuery->get_sql_select()." WHERE ".$myQuery->get_default_where());
      $compare_string = "";
      while($data = $db->get_next_res())
      {
        $compare_string .= $data->permission_path.";";
      }

      $myHTML = new html();
      $myPage->add_content("<div style='float:left;width:500px;'>");
      $myPage->add_content("<div>");
      $myPage->add_content("<h2>Benutzer auswählen</h2>");
      $myPage->add_content("<form name='ma' action='index.php?action=ma_change' method='POST'>");
      $db->sql_query("SELECT CONCAT(user_lastname,' ',user_firstname,' (',user_account,')') as username, user_id
												FROM users
												WHERE user_id IS NOT NULL
												ORDER BY user_lastname, user_firstname");
      $myPage->add_content($myHTML->get_selection($db,'user_id','user_id','username',"onchange='document.forms.ma.submit();'"));
      $myPage->add_content("<a class='ccs_button gray' href='login_as.php?user_id=".$_GET['user_id']."'>&nbsp;&nbsp;>> Login as</a>");
      $myPage->add_content("</form>");
      $myPage->add_content("<p style='border-bottom:2px solid #888;margin-right:20px;'/>");
      $myPage->add_content("<h2>Zugewiesene Berechtigungen</h2>");
      $myPage->add_content("</div><div id='q'>");
      $myPage->add_content($myQuery->get_list());
      $myPage->add_content("</div>");
      $myPage->add_content("</div>");
      $myPage->add_content("<div class='test' style='float:left;width:50px;'>");
      $myPage->add_content("  <img src='".level."inc/imgs/query/previous_big.png' title='Zuweisen' style='padding-top:200px;cursor:pointer;' onclick='document.forms.append.submit();' />");
      $myPage->add_content("</div>");
      $myPage->add_content("<div style='float:left;width:250px;border-right:1px solid gray;'>");
      $myPage->add_content("<h1>Apps</h1>");
      $myPage->add_content("<form name='append' method='POST' action='".$page->change_parameter('action','append')."'>");
      $myPage->add_content(show_dir('../',false,$compare_string));
      $myPage->add_content("</div>");
      print $myPage->get_html_code();
    }
    else
    {
      print $myQuery->check_actions();
    }

  }
  catch (Exception $e)
  {
    $myPage = new page();
    $myPage->error_text = $e->getMessage();
    print $myPage->get_html_code();
  }

?>
