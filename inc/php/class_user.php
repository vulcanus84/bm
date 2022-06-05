<?php
class user
{
  public $firstname;
  public $lastname;
  public $fullname;
	public $gender;
	public $birthday;
	public $training_location;
  public $image_path;
  public $hidden;

	private $frontend_language;
	private $db;

  //Read-only konfiguriert
  protected $id;
  protected $login;


  public function __get($name)
  {
    if (isset($this->$name)) { return $this->$name; } else { return null;  }
  }

  public function __set($name, $value)
  {
      if ($name === 'login' OR $name === 'id') { throw new Exception("Error in class User: <br>Not allowed to change property $name"); }
      else { $this->$name = $value; }
  }


  public function __construct($user_id=0)
  {
    include(level.'inc/db.php');
    if($user_id!=0) { $this->load_user_by_id($user_id); }
  }

  public function save()
  {
    include(level.'inc/db.php');
    $db->sql_query("UPDATE users SET
                          user_firstname = '$this->firstname',
                          user_lastname = '$this->lastname',
													user_language = '".$this->get_frontend_language()."'
                          WHERE user_id='$this->id'");
  }


  public function load_user_by_login($login)
  {
    include(level.'inc/db.php');
		$res = $db->sql_query_with_fetch("SELECT * FROM users WHERE user_account=:uid",array('uid'=>$login));
    $this->load_user_by_id($res->user_id);
  }

  public function load_user_by_id($id)
  {
    include(level.'inc/db.php');

    $db->sql_query("SELECT *, DATE_FORMAT(user_birthday,'%d.%m.%Y') as user_birthday FROM users	WHERE user_id=:uid",array('uid'=>$id));
    if($db->count()>0)
    {
    	$res = $db->get_next_res();
	    $this->id = $id;
	    $this->login = $res->user_account;

	    $this->firstname = $res->user_firstname;
	    $this->lastname = $res->user_lastname;
	    $this->fullname = $this->firstname." ".$this->lastname;
			if(trim($this->fullname)=='') { $this->fullname = $this->login; }
	    $this->gender = $res->user_gender;
			$this->birthday = $res->user_birthday;
			$this->training_location = $res->user_training_location;

	    $this->set_frontend_language($res->user_language);
	    $this->image_path = "app_user_admin/pics/".$this->login.".jpg";
	    if($res->user_hide>0) { $this->hidden = true; } else { $this->hidden = false; }
    }
    else
    {
    	throw new Exception("Error in class User: <br>User with ID $id not found");
    }
  }

	/**
	 *The class page manage this function for all pages by default, also the class query
	 *To disable it, set the public variable "permission_required" from class page to false
	 *For special permissions use the parameter "path" for the name of the special permission
	 */
  public function check_permission($path,$permission_typ='read')
  {
    include(level.'inc/db.php');
    //links without required permission
    if(substr($path,0,9)=='index.php') { return true; }
    if(substr($path,0,10)=='/index.php') { return true; }

		//evaluate normal permissions
    $db->sql_query("SELECT * FROM permissions WHERE permission_user_id='$this->id'");
    while($d = $db->get_next_res())
    {
      switch($permission_typ)
      {
        case 'read':
          if(strpos($path,$d->permission_path)!==FALSE AND $d->permission_read=='1') { return true; }
          break;
        case 'write':
          if(strpos($path,$d->permission_path)!==FALSE AND $d->permission_write=='1') { return true; }
          break;
        case 'delete':
          if(strpos($path,$d->permission_path)!==FALSE AND $d->permission_delete=='1') { return true; }
          break;
        case 'app_permission':
          if(strpos($d->permission_path,$path)!==FALSE AND ($d->permission_read=='1' OR $d->permission_write=='1' OR $d->permission_delete=='1')) { return true; }
          break;
    	}
    }
  }

	public function set_frontend_language($language)
	{
		switch($language)
		{
			case 'german':
				$this->frontend_language = 'german';
				break;
			case 'english':
				$this->frontend_language = 'english';
				break;
			default:
				$this->frontend_language = 'german';
				break;
		}
	}

	public function get_frontend_language()
	{
    if(isset($this->frontend_language)) { return $this->frontend_language; } else { return null; }
	}


	function get_picture($with_name=true,$javascript_function=null,$size='',$thumbnail=false)
	{
		$css='';
		$pic_path = $this->get_pic_path($thumbnail);

		if($size!='') { $css = 'width:'.$size.';'; }
		if($with_name)
		{
			$js = null;
			if($javascript_function)
			{
				$js = " onclick='".$javascript_function."(".$this->id.");'";
				$css.= "cursor:pointer;";
			}
			if($this->hidden) { $css.= "opacity:0.3"; }
			$x = "<div class='user_mit_name' id='user".$this->id."'>";
			$x.= "<img alt='$this->login' title='$this->login' style='$css' class='user' src='$pic_path' $js/>";
			$x.= "<br/>".$this->login."</div>";
			return $x;
		}
		else
		{
			$js = null;
			if($javascript_function)
			{
				$js = " onclick='".$javascript_function."(".$this->id.");'";
				$css.= "cursor:pointer;";
			}
			if($css!='') { $css = "style='".$css."'"; }
			return "<img alt='$this->login' title='$this->login' $css class='user' src='$pic_path' $js/>";
		}
	}

	function get_ori_pic_path($thumbnail=false)
	{
		if($thumbnail)
		{
			$pic_path = level."app_user_admin/user_pics/".$this->id."_t.png";
		}
		else
		{
			$pic_path = level."app_user_admin/user_pics/".$this->id.".png";
		}
		if(!file_exists($pic_path))
		{
			if($this->gender=='Herr') { $pic_path = level.'inc/imgs/default_man.png'; } else { $pic_path = level.'inc/imgs/default_woman.png'; }
		}

		return $pic_path;
	}

	function get_pic_path($thumbnail=false)
	{
		if($thumbnail)
		{
			$pic_path = level."app_user_admin/user_pics/".$this->id."_t.png";
			$pic_path_star = level."app_user_admin/user_pics/".$this->id."_stars_t.png";
		}
		else
		{
			$pic_path = level."app_user_admin/user_pics/".$this->id.".png";
			$pic_path_star = level."app_user_admin/user_pics/".$this->id."_stars.png";
		}
		if(!file_exists($pic_path_star))
		{
			if(!file_exists($pic_path))
			{
				if($this->gender=='Herr') { $pic_path = level.'inc/imgs/default_man.png'; } else { $pic_path = level.'inc/imgs/default_woman.png'; }
			}
		}
		else
		{
			$pic_path = $pic_path_star;
		}

		return $pic_path;
	}

	function get_comments()
	{
    include(level.'inc/db.php');

		$txt = "<h1>".$this->fullname."</h1>";
		$txt.= "<button style='margin-bottom:20px;' onclick='new_comment(".$this->id.");'>Neuer Kommentar</button>";
		$txt.= "<table style='width:100%;'>";
		$db->sql_query("SELECT *, DATE_FORMAT(comment_date,'%d.%m.%Y') as c_date FROM comments WHERE comment_user_id='".$this->id."' ORDER BY comment_date DESC");
		while($data = $db->get_next_res())
		{
			$trainer = new user(clone($db),$data->comment_created_by);
			$txt.= "<tr>";
			$txt.= "<td>".$trainer->get_picture(false)."</td>";
			$txt.= "<td style='font-size:12pt;width:100px;'>".$data->c_date."</td>";
			$txt.= "<td style='font-size:16pt;'>".nl2br($data->comment_text)."</td>";
			$txt.= "<td><img onclick='delete_comment(".$data->comment_id.",".$data->comment_user_id.");' style='cursor:pointer;' src='".level."inc/imgs/query/delete_big.png' alt='Löschen' title='Löschen'/></td>";
			$txt.= "</tr>";
			$txt.= "<tr><td colspan='4'><hr></td></tr>";
		}
		$txt.= "</table>";
		return $txt;
	}

	function get_user_history()
	{
    include(level.'inc/db.php');

		$x = "";
		include('class_chart.php');

		$db->sql_query("SELECT *, DATE_FORMAT(group_created,'%d.%m.%Y') as group_created_c FROM group2user
													LEFT JOIN groups ON group2user_group_id = groups.group_id
													WHERE group2user_user_id='$this->id'
													ORDER BY group_created DESC");
		if($db->count()>0)
		{

			$x.= "<h1>Infos von ".$this->fullname."</h1>";
			$x.= "<h2>Gespielte Turniere</h2>";
			$x.= "<ul>";
			while($d = $db->get_next_res())
			{
				$x.= "<li><a href='".level."app_tournaments/index.php?tournament_id=".$d->group_id."'>".$d->group_created_c." - ".$d->group_title."</a></li>";
			}
			$x.= "</ul>";
			$db->sql_query("SELECT * FROM games WHERE game_player1_id='$this->id' OR game_player2_id='$this->id' OR game_player3_id='$this->id' OR game_player4_id='$this->id'");
			$games_played = $db->count();

			$db->sql_query("SELECT * FROM games WHERE game_winner_id='$this->id' OR game_winner2_id='$this->id'");
			$games_won = $db->count();

			$x.= "<h2>Statistiken</h2>";

			$x.= "<table>";
			$x.= "<tr>";
			$x.= "<td>Anzahl Spiele:</td>";
			$x.= "<td>$games_played</td>";
			$x.= "</tr>";
			$x.= "</table>";

			$myChart = new chart("circle","Gewonnen/Verloren", "",700,200);
	 		$myChart->add_row('Gewonnen',$games_won,'#AAFF00');
	 		$myChart->add_row('Verloren',$games_played-$games_won,'#FF0000');
	 		$x.= "<img style='width:100%;' src='data:image/jpeg;base64," . base64_encode($myChart->create()) . "'/>";
			$myChart = null;


			$myChart = new chart("bars","Ränge", "",700,200);

			$db->sql_query("SELECT * FROM group2user ORDER BY group2user_group_id, group2user_wins DESC, group2user_BHZ DESC");
			$pos = 0; $found=false;
			while($d = $db->get_next_res())
			{
				if(!isset($last_group)) { $last_group=$d->group2user_group_id; }
				if($d->group2user_group_id!=$last_group)
				{
					$pos = 0;
					$found = false;
					$last_group = $d->group2user_group_id;
				}
				$pos++;

				if(!$found)
				{
					if($d->group2user_user_id==$this->id) { $arr_ranks[] = $pos; $found=true; }
				}
			}
			$i=1;
			$max_count = 0;
			for($i;$i<16;$i++)
			{
				if($i<4) { $color = '#AAFF00'; }
				if($i>3) { $color = '#FFCC00'; }
				if($i>8) { $color = '#FF0000'; }

				$count = 0;
				foreach($arr_ranks as $rank)
				{
					if($i==$rank) { $count++; }
				}
				if($count>$max_count) { $max_count = $count; }
		 		$myChart->add_row('Rang '.$i,$count,$color);
			}
			$myChart->set_max_value($max_count);

	 		$x.= "<img style='width:100%;' src='data:image/jpeg;base64," . base64_encode($myChart->create()) . "'/>";
			$myChart = null;

			$x.= "<h2>Alle Spiele</h2>";
			$db->sql_query("SELECT *, DATE_FORMAT(group_created,'%d.%m.%Y') as group_created_c FROM games
													LEFT JOIN groups ON game_group_id = groups.group_id
													WHERE game_player1_id='$this->id' OR game_player2_id='$this->id' OR game_player3_id='$this->id' OR game_player4_id='$this->id'
													ORDER BY group_created DESC,game_round ASC");

			$x.= "<table style='width:100%;'>";
			$last_tournament_id = 0;
			while($data = $db->get_next_res())
			{
				if($last_tournament_id != $data->game_group_id)
				{
					$myTournament = new tournament(clone($db),$data->game_group_id);
					$last_tournament_id = $data->game_group_id;
					$x.= "<tr><td colspan='6'><h1 style='margin-bottom:0px;'>".$myTournament->get_title()."</h1><h2 style='font-style:italic;'>".$data->group_created_c."</h2></td></tr>";
				}

				$u1 = null; $u2 = null; $u3 = null; $u4 = null;
				$winner = null;
				$invert = null;

				if($data->game_player1_id==$_GET['user_id'] OR $data->game_player3_id==$_GET['user_id'])
				{
					$invert = true;
					$u1 = new user($data->game_player1_id);
					$u2 = new user($data->game_player2_id);
					if($data->game_player3_id>0)
					{
						$u3 = new user($data->game_player3_id);
						$u4 = new user($data->game_player4_id);
					}
				}

				if($data->game_player2_id==$_GET['user_id'] OR $data->game_player4_id==$_GET['user_id'])
				{
					$invert = false;
					$u2 = new user($data->game_player1_id);
					$u1 = new user($data->game_player2_id);
					if($data->game_player3_id>0)
					{
						$u3 = new user($data->game_player4_id);
						$u4 = new user($data->game_player3_id);
					}
				}

				$x.= "<tr>";
				$x.= "<td style='text-align:center;'><h2>Runde ".$data->game_round."</h2></td>";
				$x.= "<td style='text-align:center;'><img style='width:100px;' src='".$u1->get_pic_path()."'><br/>".$u1->login."</td>";
				if(isset($u3)) { $x.= "<td style='text-align:center;'><img style='width:100px;' src='".$u3->get_pic_path()."'><br/>".$u3->login."</td>"; }
				$x.= "<td style='text-align:center;'><h2>gegen</h2></td>";
				$x.= "<td style='text-align:center;'><img style='width:100px;cursor:pointer;' src='".$u2->get_pic_path()."' onclick=\"show_user_games('".$u2->id."');\"><br/>".$u2->login."</td>";
				if(isset($u4)) { $x.= "<td style='text-align:center;'><img style='width:100px;cursor:pointer;' src='".$u4->get_pic_path()."' onclick=\"show_user_games('".$u4->id."');\"><br/>".$u4->login."</td>"; }

				if($data->game_winner_id!='')
				{
					if($myTournament->get_counting()=='win')
					{
						if($data->game_winner_id==$_GET['user_id'] OR $data->game_winner2_id==$_GET['user_id'])
						{
							$x.= "<td style='text-align:center;'><h1 style='color:green;'>Gewonnen!</h1></td>";
						}
						else
						{
							$x.= "<td style='text-align:center;'><h1 style='color:red;'>Verloren!</h1></td>";
						}
					}
					else
					{
						$txt = "<span style='font-size:16pt;font-weight:bold;'>";
						if($invert)
						{
							if($data->game_set1_p1>0 OR $data->game_set1_p2>0) {	$txt .= $data->game_set1_p1.":".$data->game_set1_p2; }
							if($data->game_set2_p1>0 OR $data->game_set2_p2>0) {  $txt .= "<br/>".$data->game_set2_p1.":".$data->game_set2_p2; }
							if($data->game_set3_p1>0 OR $data->game_set3_p2>0) {  $txt .= "<br/>".$data->game_set3_p1.":".$data->game_set3_p2; }
						}
						else
						{
							if($data->game_set1_p1>0 OR $data->game_set1_p2>0) {	$txt .= $data->game_set1_p2.":".$data->game_set1_p1; }
							if($data->game_set2_p1>0 OR $data->game_set2_p2>0) {  $txt .= "<br/>".$data->game_set2_p2.":".$data->game_set2_p1; }
							if($data->game_set3_p1>0 OR $data->game_set3_p2>0) {  $txt .= "<br/>".$data->game_set3_p2.":".$data->game_set3_p1; }
						}
						$txt.= "</span>";

						if($data->game_winner_id==$_GET['user_id'])
						{
							$x.= "<td style='text-align:center;'><h1 style='color:green;'>".$txt."</h1></td>";
						}
						else
						{
							$x.= "<td style='text-align:center;'><h1 style='color:red;'>".$txt."</h1></td>";
						}

					}
					if($data->game_duration>0)
					{
						$x.= "<td style='text-align:center;font-size:14pt;'>Spieldauer<br/>".gmdate("H:i:s", $data->game_duration)."</td>";
					}
				}
				else
				{
					$x.= "<td style='text-align:center;' colspan='2'><h2 style='font-style:italic;'>Noch nicht gespielt</h2></td>";
				}
				$x.= "</tr>";
				$x.= "<tr><td colspan='6'><hr/></td></tr>";
			}
			$x.= "</table>";

		}
		else
		{
			$x.= "<p><span style='font-style:italic'>Noch keine Spiele gespielt</span>";
		}

		print $x;
	}

	function get_user_infos($with_form=true,$with_pic=true)
	{
    include(level.'inc/db.php');
	  $page = new header_mod();                               //about the current page and header modification functions
		$x = "";

		if($with_form)
    {
      $x.= "<form id='new_user' action='".$page->change_parameter('action','modify_user')."' method='post' enctype='multipart/form-data'>";
		  $x.= "<input type='hidden' id='user_id' name='user_id' value='".$this->id."' />";
    }
		$x.= "<table>";
    if($with_pic)
    {
  		$x.= " 	<tr>";
  		$x.= " 		<td colspan='2'>";
  		$x.= 				$this->get_picture(false,'test');
  		$x.= "		</td>";
  		$x.= "	</tr>";
  		$x.= " 	<tr>";
  		$x.= " 		<td></td>";
  		$x.= " 		<td>";
  		$x.= "			<input style='visibility:hidden;' onchange='$(\"#new_user\").submit();' name='pictures[]' id='inpPicture' type='file' accept='image/*'/>";
  		$x.= "		</td>";
  		$x.= "	</tr>";
  		$x.= "	<tr>";
    }
		$x.= "		<td>Anrede:</td>";
		$x.= " 		<td>";
		$x.= " 			<select name='user_gender'>";
		$x.= " 				<option"; if($this->gender=='Herr') { $x.=" selected='1'"; } $x.=" value='Herr'>Herr</option>";
		$x.= " 				<option"; if($this->gender=='Frau') { $x.=" selected='1'"; } $x.=" value='Frau'>Frau</option>";
		$x.= " 			</select>";
		$x.= " 		</td>";
		$x.= " 	</tr>";
		$x.= "	<tr>";
		$x.= "		<td>Nickname:</td>";
		$x.= " 		<td><input type='text' name='user_account' value='".$this->login."'/></td>";
		$x.= " 	</tr>";
		$x.= "	<tr>";
		$x.= "		<td>Vorname:</td>";
		$x.= " 		<td><input type='text' name='user_firstname' value='".$this->firstname."'/></td>";
		$x.= " 	</tr>";
		$x.= "	<tr>";
		$x.= "		<td>Nachname:</td>";
		$x.= " 		<td><input type='text' name='user_lastname' value='".$this->lastname."'/></td>";
		$x.= " 	</tr>";
		$x.= "	<tr>";
		$x.= "		<td>Geburtsdatum:</td>";
		$x.= " 		<td><input type='text' name='user_birthday' value='".$this->birthday."'/></td>";
		$x.= " 	</tr>";
		$x.= "	<tr>";
		$x.= "		<td>Trainingsort:</td>";
		$x.= " 		<td>";
		$x.= " 			<select name='user_training_location'>";

	  $db->sql_query("SELECT * FROM location_permissions
	  								LEFT JOIN locations ON loc_permission_loc_id = location_id
	  								WHERE loc_permission_user_id = '".$_SESSION['login_user']->id."'
	  								ORDER BY location_name");
	  while($d = $db->get_next_res())
	  {
		  $x.= "	   <option value='$d->location_id' "; if($this->training_location==$d->location_id) { $x.= " selected='1'"; } $x.= ">$d->location_name</option>";
	  }
		$x.= " 			</select>";
		$x.= " 		</td>";
		$x.= " 	</tr>";
		$x.= "	<tr>";
		$x.= "		<td>Ausgeblendet:</td>";
		if($this->hidden) { $val = 'checked=checked'; } else { $val = ""; }
		$x.= " 		<td><input type='checkbox' name='user_hide' $val/></td>";
		$x.= " 	</tr>";
		$x.= " 	<tr><td>&nbsp;</td></tr>";
    if($with_form)
    {
  		$x.= " 	<tr>";
  		$x.= " 		<td colspan='2'><button onclick='$('#right_col').hide(); $(\"#new_user\").submit();'>Speichern</button>";
  		$x.= "		<button type='button' style='background-color:blue;' onclick='delete_pic(".$this->id.");'>Bild entfernen</button>";
  		if($this->check_permission('app_user_admin')==false)
  		{
	  		$x.= "		<button type='button' style='background-color:red;' onclick='delete_permission(".$this->id.");'>Spieler löschen</button>";
  		}
  		$x.= "		<button type='button' style='background-color:purple;' onclick='show_history(".$this->id.");'>Infos</button></td>";
  		$x.= "	</tr>";
    }
		$x.= "</table>";
    if($with_form) { $x.= "</form>"; }

		$my_user = null;
		return $x;
	}

	function get_new_user()
	{
    include(level.'inc/db.php');

	  $page = new header_mod();                               //about the current page and header modification functions
		$x = "<h1>Neuer Spieler</h1>";
		$x.= "<form id='new_user' action='".$page->change_parameter('action','create_new_user')."' method='POST'>";
		$x.= "<input type='hidden' name='user_lastname'/>";
		$x.= "<input type='hidden' name='user_firstname'/>";
		$x.= "<table>";
		$x.= "	<tr>";
		$x.= "		<td>Anrede:</td>";
		$x.= " 		<td>";
		$x.= " 			<select name='user_gender'>";
		$x.= " 				<option value='Herr'>Herr</option>";
		$x.= " 				<option value='Frau'>Frau</option>";
		$x.= " 			</select>";
		$x.= " 		</td>";
		$x.= " 	</tr>";
		$x.= "	<tr>";
		$x.= "		<td>Nickname:</td>";
		$x.= " 		<td><input type='text' name='user_account'/></td>";
		$x.= " 	</tr>";
		$x.= "	<tr>";
		$x.= "		<td>Trainingsort:</td>";
		$x.= " 		<td>";
		$x.= " 			<select name='user_training_location'>";

	  $db->sql_query("SELECT * FROM location_permissions
	  								LEFT JOIN locations ON loc_permission_loc_id = location_id
	  								WHERE loc_permission_user_id = '".$_SESSION['login_user']->id."'
	  								ORDER BY location_name");
	  while($d = $db->get_next_res())
	  {
		  $x.= "	   <option value='$d->location_id'>$d->location_name</option>";
	  }
		$x.= " 			</select>";
		$x.= " 		</td>";
		$x.= " 	</tr>";
		$x.= " 	<tr>";
		$x.= " 		<td><button onclick='$(\"#new_user\").submit();'>Erstellen</button></td>";
		$x.= "</table>";
		$x.= "</form>";

		return $x;
	}

	function create_star_image()
	{
		//get thumbnail path, if no own image exist, don't show the stars
		$myPath = $this->get_ori_pic_path(true);

		if(strpos($myPath,'_t.png')>0)
		{
			$myPath = str_replace('_t.png','_stars.png',$myPath);

	    include(level.'inc/db.php');

	    $db->sql_query("SELECT * FROM exam2user WHERE exam2user_user_id='".$this->id."'");
	    $anz_stars = $db->count();
	    if($anz_stars > 0)
      {
        $im  = imagecreatefrompng($this->get_ori_pic_path());
  			$img_width = imagesx($im);
  			$img_height = imagesy($im);

  			$img_star = imagecreatefrompng(level.'inc/imgs/star_full_big.png');
  			$img_star_width = imagesx($img_star);
  			$img_star_height = imagesy($img_star);

  			$pos_x[] = $img_width/2-$img_star_width/2; $pos_y[] = 5;

  			$pos_x[] = $img_width/1.5-$img_star_width/2; $pos_y[] = 25;
  			$pos_x[] = $img_width/3-$img_star_width/2; $pos_y[] = 25;

  			$pos_x[] = $img_width/1.24-$img_star_width/2; $pos_y[] = 70;
  			$pos_x[] = $img_width/5-$img_star_width/2; $pos_y[] = 70;

  			$pos_x[] = $img_width-$img_star_width*1.25; $pos_y[] = 140;
  			$pos_x[] = 20; $pos_y[] = 140;

  			//Mitte
  			$pos_x[] = $img_width-$img_star_width; $pos_y[] = 220;
  			$pos_x[] = 5; $pos_y[] = 220;

  			$pos_x[] = $img_width-$img_star_width*1.25; $pos_y[] = 300;
  			$pos_x[] = 20; $pos_y[] = 300;

  			$pos_x[] = $img_width/1.24-$img_star_width/2; $pos_y[] = 360;
  			$pos_x[] = $img_width/5-$img_star_width/2; $pos_y[] = 360;

  			$pos_x[] = $img_width/1.5-$img_star_width/2; $pos_y[] = 400;
  			$pos_x[] = $img_width/3-$img_star_width/2; $pos_y[] = 400;

  			$pos_x[] = $img_width/2-$img_star_width/2; $pos_y[] = 420;


  			$i=0;
  			foreach($pos_x as $cur_pos_x)
  			{
  				if($i>=$anz_stars) { break; }
  				$cur_pos_y = $pos_y[$i];
  				imagecopyresized($im, $img_star, $cur_pos_x, $cur_pos_y, 0, 0, $img_star_width, $img_star_height, $img_star_width, $img_star_height);
  				$i++;
  			}

  			imagepng($im,$myPath);

  			//*********************************
  			//Create Thumbnail
  			//*********************************

  			$newwidth = 120;
  			$newheight = 120;

  			$image = imagecreatetruecolor($newwidth, $newheight);
  			imagealphablending($image, true);
  			imagecopyresampled($image, $im, 0, 0, 0, 0, $newwidth, $newheight, $img_width, $img_height);

  			//create masking
  			$mask = imagecreatetruecolor($newwidth, $newheight);

  			$transparent = imagecolorallocate($mask, 255, 0, 0);
  			imagecolortransparent($mask,$transparent);

  			imagefilledellipse($mask, $newwidth/2, $newheight/2, $newwidth-2, $newheight-2, $transparent);

  			$red = imagecolorallocate($mask, 0, 0, 0);
  			imagecopymerge($image, $mask, 0, 0, 0, 0, $newwidth, $newheight, 100);
  			imagecolortransparent($image,$red);
  			imagefill($image, 0, 0, $red);

  			//output, save and free memory
  			$myPath = str_replace('_stars.png','_stars_t.png',$myPath);
  			imagepng($image,$myPath);

  			imagedestroy($im);
  			imagedestroy($image);

      }
      else
      {
      	//If there are no starts, remove star images if existing
      	if(file_exists($myPath)) { unlink($myPath); }
  			$myPath = str_replace('_stars.png','_stars_t.png',$myPath);
      	if(file_exists($myPath)) { unlink($myPath); }
      }
		}
	}

  function check_password($pw)
  {
    include(level.'inc/db.php');
    $db->sql_query("SELECT * FROM users
                           WHERE user_account = :user_account",array('user_account'=>$this->login));
    $daten = $db->get_next_res();
    if ($db->count()==1)
    if (hash('sha256', $pw)==$daten->user_password)
    {
      return true;
    }
    else {
      return false;
    }
  }

  function update_password($old_pw,$new_pw)
  {
    include(level.'inc/db.php');
    if($this->check_password($old_pw))
    {
      $db->update(array('user_password'=>hash('sha256', $new_pw)),'users','user_id',$this->id);
    }
    else {
      throw new Exception("Altes Passwort falsch");

    }
  }

}

?>
