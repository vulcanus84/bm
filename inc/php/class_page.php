<?php
/*
  Create a HTML Page
*/

class page
{
  public $error_text;                   //Save error text and display it on get_html_code instead of content
  public $permission_required = true;   //if its true, page filename must be added to users permission
  public $login_required = true;        //If its true, user must be logged in to view the page
  public $menu;                         //HTML code for menu (created by menu class)
  public $t;                           //Pointer to translation class

  private $db;                          //Datebase pointer (will be set on construct of class)
  private $title;                       //Title of page, will be set to HTML head and displayed on top of page
  private $subtitle;                    //Subtitle of page, will be displayed on top of page
  private $filename;                    //Filename of page
  private $own_folder;                  //Foldername in which is page
  private $path;                        //Full path to page filename
  private $space="      ";              //used for better looking html source code
  private $content="";                     //save the text until it will be printed by get_html_code
  private $arr_header_lines = array();  //array with all header lines (filled by the functions add_header_line, add_css_link, add_js_link)
  private $logger;
  private $arr_data = [];

  public function __construct()
  {
    $this->db = new db();
    $this->logger = new log();

    //Used for adding/removing parameters to URL
    $page = new header_mod();

    //Get filename
    $this->filename = basename($_SERVER["REQUEST_URI"]);
    if(strpos($this->filename,".")==0) { $this->filename = "index.php"; }
    if(strpos($this->filename,"?")!==FALSE) { $this->filename = substr($this->filename,0,strpos($this->filename,"?")); }

    //Get file path
    $this->path = str_replace($_SERVER['DOCUMENT_ROOT'],"",$_SERVER['SCRIPT_FILENAME']);
    $this->path = substr($this->path,1);

    //Get folder path
    $this->own_folder = substr($this->path,0,strrpos($this->path,"/"));
    if(strpos($this->own_folder,"/")!=0) { $this->own_folder = substr($this->own_folder,strrpos($this->own_folder,"/")+1); }

    //Set standard headers for all files
		$this->add_header_line("<meta http-equiv='X-UA-Compatible' content='IE=edge'>");
    $this->add_header_line("<meta http-equiv='Content-Type' content='text/html; charset=UTF-8'/>");

    $this->add_header_line("<link rel='apple-touch-icon' sizes='180x180' href='".level."inc/imgs/favicon/apple-icon-180x180.png'>");
		$this->add_header_line("<link rel='icon' type='image/png' sizes='192x192'  href='".level."inc/imgs/favicon/android-icon-192x192.png'>");
		$this->add_header_line("<link rel='icon' type='image/png' sizes='96x96' href='".level."inc/imgs/favicon/favicon-96x96.png'>");
		$this->add_header_line("<meta name='msapplication-TileImage' content='".level."inc/imgs/favicon/ms-icon-144x144.png'>");
    $this->add_header_line("<meta name='theme-color' content='#ffffff'>");

		$this->add_header_line("<meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no\"/>");

    //Set standard style sheets for all files
    //$this->add_css_link(level."inc/css/styles.css");
    $this->add_css_link(level."inc/css/general.css");
    $this->add_css_link(level."inc/css/main_menu.css");
    $this->add_css_link(level."inc/css/buttons.css");
    $this->add_css_link(level."inc/css/modal.css");
    $this->add_css_link(level."inc/css/query.css");

    $this->add_css_link(level."inc/js/calendar/theme.css");

    //Set standard javascript includes for all files
    $this->add_js_link(level."inc/js/calendar/calendar.js");
    $this->add_js_link(level."inc/js/calendar/calendar-en.js");
    $this->add_js_link(level."inc/js/calendar/calendar-setup.js");
    $this->add_js_link(level."inc/js/jquery-3.5.1.min.js");
    $this->add_js_link(level."inc/js/jquery-1.11.3-ui.min.js");

		try
		{
	    if(isset($_GET['change_language']))
			{
				$_SESSION['login_user']->set_frontend_language($_GET['change_language']);
        $_SESSION['login_user']->save();
				$page->remove_parameter('change_language');
				header("Location: ".$page->get_link());
			}

	    if(isset($_SESSION['login_user']))
			{
				$this->t = new translation($_SESSION['login_user']->get_frontend_language());
			}
			else
			{
				$this->t = new translation("german");
			}

	    if(isset($_POST['user_login'])) { $this->check_login($_POST['user_login'],$_POST['pw']); }
	    if(isset($_GET['user_login'])) { $this->check_login($_GET['user_login'],$_GET['pw']); }
	    if(isset($_GET['action']) && $_GET['action']=='logout') { $this->logout(); }
	    if(isset($_GET['action']) && $_GET['action']=='login') { $this->login(); }
		}
	  catch (Exception $e)
	  {
	    $this->error_text = $e->getMessage();
			$this->t = new translation("german");
	  }
  }

	public function get_setting($setting_name)
	{
		switch($setting_name)
		{
			case 'sql_user_selection':
				return "SELECT *, CONCAT(COALESCE(user_firstname,''),' ',COALESCE(user_lastname,''),' (',COALESCE(user_account,''),')') as user_fullname
								FROM users
								ORDER BY user_account, user_firstname, user_lastname";
		}
	}

  /**
   * Show standardized info text
  */
	public function show_info($text)
	{
	  $txt = "<div style='border:1px solid gray;border-radius:5px;padding:5px;'>";
    $txt.= "<table width='100%;'><tr>";
    $txt.= "<td style='padding-right:10px;width:50px;'><img style='height:30px;' src='".level."inc/imgs/info.png' alt='Information'/></td>";
	  $txt.= "<td>".$text."</td>";
	  $txt.= "</tr></table></div>";
		return $txt;
	}

  public function show_error($text)
	{
	  $txt = "<div style='border:1px solid gray;border-radius:5px;padding:5px;'>";
    $txt.= "<table width='100%;'><tr>";
    $txt.= "<td style='padding-right:10px;width:50px;'><img style='height:30px;' src='".level."inc/imgs/error.png' alt='Information'/></td>";
	  $txt.= "<td>".$text."</td>";
	  $txt.= "</tr></table></div>";
		return $txt;
	}

  public function add_data($key,$val) {
    $this->arr_data[$key] = $val;
  }

  //Add content to the page
  public function add_content_with_translation($txt)
	{
		$this->add_content($this->t->translate($txt));
	}

  //Add content to the page
  public function add_content($txt)
  {
    if(strpos($txt,"\n")===FALSE)
    {
      if(substr($this->content,strlen($this->content)-strlen($this->space))==$this->space)
      {
        $txt = $txt."\n";
      }
      else
      {
        $txt = $this->space.$txt."\n";
      }
    }
    $this->content.= $txt;
  }


	/**
	 *Returns the HTML code from the page class
	 *
	 *Possible Modus:
	 *- full (standard) -> whole HTML Code with all CCS elements
	 *- only_html -> all HTML elements without header and footer from CCS
	 *- only_content -> only added content without HTML (usefull to get content over AJAX)
	 */
  public function get_html_code($modus='full')
  {
    try
    {
      if($this->get_title() OR $this->get_subtitle())
      {
        if($this->get_subtitle()) { $this->arr_header_lines[] = "<title>".$this->get_title()." > ".$this->get_subtitle()."</title>"; } else { $this->arr_header_lines[] = "<title>".$this->get_title()."</title>"; }
      }
			$txt = "";
			if(!isset($_SESSION['login_user']))
			{
		    $page = new header_mod();
		    $page->change_parameter('x','y');
		  	$this->add_js("
	  			function select_user(user_id)
					{
						window.location = '".$page->get_link()."&user_id=' + user_id;
					}
				");
			}

      if($modus!='only_content')
			{
				$txt.= "<!DOCTYPE html>\n";
	      $txt.= "<html xmlns=\"http://www.w3.org/1999/xhtml\">\n";
	      $txt.= "  <head>\n";
	      //include special headerlines
	      foreach($this->arr_header_lines as $akt_header_line)
	      {
	        $txt .= "    ".$akt_header_line."\n";
	      }

	      $txt.= "  </head>\n";
	      $txt.= "  <body>\n";
			}
			if($modus=='full')
			{
	      $txt.= "    <div class='page'>\n";
      	$txt.= $this->get_header();
			}
      //If a error occured show the error and no content
      if(isset($this->error_text))
      {
        $txt.= "<div style='background-color:#FFFFAA;color:black;border-radius:15px;padding:10px;margin-bottom:10px;border:1px solid red;'>".$this->error_text."</div>";
      }
      else
      {
        if (($this->permission_required===false AND $this->login_required===false) OR (isset($_SESSION['login_user']) AND $_SESSION['login_user']->check_permission($this->path)))
        {
          if(isset($this->menu)) { $txt.= $this->get_menu(); }
          $txt.= $this->get_content();
        }
        else
        {
          if($this->permission_required===false)
					{
						if($this->login_required===true AND isset($_SESSION['login_user']))
						{
              if(isset($this->menu)) { $txt.= $this->get_menu(); }
		          $txt.= $this->get_content();
						}
						else
						{
							$txt.= $this->get_login_mask();
						}
					}
					else
					{
						$txt.= $this->get_login_mask();
					}
        }
      }
      if($modus!='only_content')
			{
				if($modus=='full')
				{
					$txt.= $this->get_footer();
	      	$txt.= "    </div><!--End Page-->\n";
				}
	      $txt.= "  </body>\n";
	      $txt.= "</html>";
			}
      return $txt;
    }
    catch (Exception $e)
    {
      return $e->getMessage();
    }
  }

  public function add_css_link($css)
  {
    $this->arr_header_lines[] = "<link rel='stylesheet' href='".$css."' type='text/css'/>";
  }

  public function add_css($css)
  {
    $arr_css = explode("\n",$css);
    $this->arr_header_lines[] = "<style type='text/css'>";
    foreach($arr_css as $css)
    {
      $this->arr_header_lines[] = "  ".$css;
    }
    $this->arr_header_lines[] = "</style>";
  }

  public function add_js_link($js)
  {
    $this->arr_header_lines[] = "<script type='text/javascript' src='".$js."'></script>";
  }

  public function add_js($js)
  {
    $arr_js = explode("\n",$js);
    $this->arr_header_lines[] = "<script type='text/javascript'>";
    foreach($arr_js as $js)
    {
      $this->arr_header_lines[] = "  ".$js;
    }
    $this->arr_header_lines[] = "</script>";
  }

  public function add_header_line($header_line)
  {
    $this->arr_header_lines[] = $header_line;
  }

  private function get_header()
  {
    $txt = "      <div class='header'>\n";
    if(isset($_SESSION['login_user'])) { $txt.= "      <div id='topmenu_button'><img src='".level."inc/imgs/small_menu.png' alt='Menu' onclick=\"$('#topmenu').toggle();\"/></div>"; }
    $txt.= "      <div id='topmenu'>";
    if(isset($_SESSION['login_user']))
    {
      $txt.= "<a href='".level."my_user.php'><img style='height:40px;' src='".$_SESSION['login_user']->get_pic_path(true)."' alt='".$_SESSION['login_user']->fullname."' title='".$_SESSION['login_user']->fullname."'/></a>";
      $od = opendir(level);
      while ($entry = readdir($od))
      {
        if(substr($entry,0,4)=='app_')
        {
          if ($_SESSION['login_user']->check_permission($entry,'app_permission'))
          {
            $pic = "";
            $desc = "";
            //search for description File of the app
            if(file_exists(level.$entry."/app_description.txt"))
            {
              $desc = file_get_contents(level.$entry."/app_description.txt");
            }
            else
            {
              //Get folder name and make it look better
              $desc = str_replace("_"," ",$entry);
              $desc = trim(str_replace("app","",$desc));
              $desc = strtoupper(substr($desc,0,1)).substr($desc,1);
            }

            //search for the icon of the app
            if(file_exists(level.$entry."/app_picture.gif")) { $pic = level.$entry."/app_picture.gif"; }
            if(file_exists(level.$entry."/app_picture.png")) { $pic = level.$entry."/app_picture.png"; }

            //Check for permission to the root of the app, if it failed, find the first link in the app with permissions
            if ($_SESSION['login_user']->check_permission($entry,false))
            {
              $txt.= "              <a href='".level.$entry."'><img style='height:40px;' src='$pic' alt='$desc' title='$desc'/></a>\n";
            }
            else
            {
              $this->db->sql_query("SELECT * FROM permissions WHERE permission_user_id='".$_SESSION['login_user']->id."' AND permission_path LIKE '".$entry."%' ORDER BY permission_path ASC");
              $d = $this->db->get_next_res();
              $txt.= "              <a href='".level.$d->permission_path."'><img style='height:40px;' src='$pic' alt='$desc' title='$desc'/></a>\n";
            }
          }
        }
      }
      closedir($od);
      $txt.= "<a href='".level."index.php?action=logout'><img src='".level."inc/imgs/logout.png' alt='Logout' title='Logout'/></a>";
    }
    $txt.= "            </div>\n";
    $txt.= "          <hr style='clear:both;'/>\n";
    $txt.= "      </div><!--End Header-->\n";
    return $txt;
  }

  private function get_menu()
  {
    $txt = "      <div class='menu'>\n";
    $txt.= $this->menu;
    $txt.= "      </div><!--End Menu-->\n";
    return $txt;
  }

  private function get_content()
  {
    $str_data = "";
    foreach($this->arr_data as $key => $val) {
      $str_data.= "data-".$key."='".$val."' ";
    }
    $txt = "      <div id='content' $str_data>\n";
    $txt.= $this->content;
    $txt.= "      </div><!--End Content-->\n";
    return $txt;
  }

  private function get_footer()
  {
    $txt = "      <div class='footer'>\n";
    $txt.= "        <table style='width:100%;' >\n";
    $txt.= "          <tr>\n";
    $txt.= "            <td colspan='3'><hr/></td>\n";
    $txt.= "          </tr>\n";
    $txt.= "          <tr>\n";
    $txt.= "            <td style='width:33%;text-align:left;font-size:8pt;'>";
    if(isset($_SESSION['login_user'])){ $txt.= $_SESSION['login_user']->fullname; }
    $txt.= "</td>\n";
    $txt.= "            <td style='width:33%;text-align:center;font-size:8pt;'>".date('d.m.Y')."</td>\n";
    $txt.= "            <td style='width:33%;text-align:right;font-size:8pt;'></td>\n";
    $txt.= "          </tr>\n";
    $txt.= "        </table>\n";
    $txt.= "      </div><!--End Footer-->\n";
    return $txt;
  }

  public function get_login_mask() {
	  $txt = "<div id='login_mask'>\n";
		$txt.= "<form id='login' action='' method='POST' name='login'>\n";
		$txt.= "<h2>Benutzer</h2>";
    $txt.= "<input type='text' name='user_login'>";
		$txt.= "<h2>Passwort</h2>";
		$txt.= "<input type='password' name='pw'/><br/>";
		$txt.= "<button class='green' onclick='this.submit();'>Login</button>";
		$txt.= "</form>\n";
		$txt.= "<a href='index.php'><button class='blue'>Zurück</button></a>";
		$txt.= "<a href='app_drawing/excercises.php'><button class='orange'>Übungen</button></a>";
		$txt.= "<a href='new/app_tournament/index.php'><button class='purple'>Login (Beta)</button></a>";
	  $txt.= "</div>\n";
	  return $txt;

  }

  private function logout()
  {
		$_SESSION['login_user']->save();
    $this->logger->write_to_log('User','Logout');
    session_destroy();
    header("Location: index.php");
    die();
  }

  private function login()
  {
    header("Location: index.php");
    die();
  }

  private function check_login($user_login,$pw)
  {
    $page = new header_mod();                               //about the current page and header modification functions
    $result = $this->db->sql_query("SELECT * FROM users
                           WHERE user_account = :user_account OR user_email = :user_account",array('user_account'=>$user_login));
    $daten = $this->db->get_next_res();
    if ($this->db->count()==0)
    {
      if(trim($user_login)!='')
      {
   			$this->logger->write_to_log('User','Try to login with unknown user '.$user_login);
        $this->error_text  = "Der angegeben User existiert nicht in der Datenbank.<br> Bitte überprüfen Sie ihr Kurzzeichen";
      }
    }
    else
    {
      if (hash('sha256', $pw)==$daten->user_password)
      {
        $_SESSION['login_user'] = new user($daten->user_id);
        $page->remove_parameter('logout');
        $page->remove_parameter('user_login');
        $page->remove_parameter('user_id');
        $page->remove_parameter('pw');
        $page->remove_parameter('x');
   			$this->logger->write_to_log('User','Login');
        header("Location: ".$page->get_link());
      }
      else
      {
   			$this->logger->write_to_log('User','Try to login with wrong password for user '.$user_login);
        $this->error_text = "Falsches Passwort";
        $page->remove_parameter('action');
        $page->remove_parameter('user');
      }
    }

  }

  public function set_title($title)
  {
    $this->title = $title;
  }

  public function get_title()
  {
    if(isset($this->title)) { return $this->title; } else { return null; }
  }

  public function set_subtitle($title)
  {
    $this->subtitle = $title;
  }

  public function get_subtitle()
  {
    if(isset($this->sub_title)) { return $this->subtitle; } else { return null; }
  }

  public function get_path()
  {
    if(isset($this->path)) { return $this->path; } else { return null; }
  }

}
?>
