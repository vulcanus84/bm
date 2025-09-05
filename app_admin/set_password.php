<?php
  define("level","../");                               //define the structur to to root directory (e.g. "../", for files in root set "")
  require_once(level."inc/standard_includes.php");     //Load all necessary files (DB-Connection, User-Login, etc.)
  require_once(level."inc/php/class_query.php");       //Load class query for the grid (includes class column)

  try
  {
    //Display page
    $myPage = new page();
    if(isset($_POST['new_password']) && $_POST['new_password']!='')
    {
      if(isset($_POST['user']) && $_POST['user']!='')
      {
        $newPassword = hash('sha256', $_POST['new_password']);
				$db->sql_query("SELECT * FROM users WHERE user_id = '".$_POST['user']."'");
				if($db->count()>0)
				{
					$db->sql_query("UPDATE users SET user_password = '$newPassword' WHERE user_id='".$_POST['user']."'");
	        $myPage->add_content("<span style='color:green;font-size:16pt;'>Passwort erfolgreich gespeichert</span><p/>");
				}
      }
    }

    $myPage->set_title("Administration");
    $myPage->set_subtitle("Passwort setzen");
    include('menu.php');
    $myPage->menu = $myMenu->create_menu("tabsJ");
    $myHTML = new html();
    $db->sql_query($myPage->get_setting('sql_user_selection'));
    $myPage->add_content("<form name='change_password' action='' method='POST'>");
    $myPage->add_content("<table><tr><td>Select user</td><td>Password</td></tr><tr>");
    $myPage->add_content("<td>".$myHTML->get_selection($db,'user','user_id','user_fullname','')."</td>");
    $myPage->add_content("<td><input type='password' name='new_password'/></td>");
    $myPage->add_content("<td><input type='submit' value='Change password'/></td>");
    $myPage->add_content("</tr></table></form>");
    print $myPage->get_html_code();
  }
  catch (Exception $e)
  {
    $myPage = new page();
    $myPage->error_text = $e->getMessage();
    print $myPage->get_html_code();
  }
?>