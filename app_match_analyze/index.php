<?php
define("level","../");																//define the structur to to root directory (e.g. "../", for files in root set "")
require_once(level."inc/standard_includes.php");		//Load all necessary files (DB-Connection, User-Login, etc.)
global $db;

try
{	
	if(!IS_AJAX)
	{
		$myPage = new page();
	  $myPage->set_title("Match Analysen");
		if(!$myPage->is_logged_in()) { print $myPage->get_html_code(); exit; }

		$myPage->add_js_link('inc/js/index.js');
		$myPage->add_css_link('inc/css/index.css');
		$last_date = null;
		$myPage->add_content("<div id='myModal' class='modal'>");
		$myPage->add_content("  <div class='modal-content'>");
		$myPage->add_content("    <span class='close'>&times;</span>"); //&times = sign for multiplication
		$myPage->add_content("    <p id='myModalText'></p>");
		$myPage->add_content("  </div>");
		$myPage->add_content("</div>");

		$db->sql_query("
				SELECT DISTINCT 
						ma.*, 
						trainee.*, 
						DATE_FORMAT(ma_created_on, '%d.%m.%Y') AS curr_date
				FROM match_analyzes AS ma
				LEFT JOIN users AS trainee 
						ON ma.ma_trainee_id = trainee.user_id
				INNER JOIN location2user AS l2u 
						ON l2u.location2user_user_id = trainee.user_id
				INNER JOIN location_permissions AS perm 
						ON perm.loc_permission_loc_id = l2u.location2user_location_id
				WHERE perm.loc_permission_user_id = '" . $_SESSION['login_user']->id . "'
				ORDER BY DATE(ma_created_on) DESC, ma_id DESC
		");

		$myPage->add_content("<div class='add_entry'><img src='../inc/imgs/query/add.png'/></div>"); 

		while($d = $db->get_next_res())
		{
			if($d->curr_date!=$last_date)
			{
				$myPage->add_content("<section>{$d->curr_date}</section>");
				$last_date = $d->curr_date;
			}
			$trainer = new user($d->ma_created_by);
			$myPage->add_content("<div class='row'>");
			$myPage->add_content("<div class='col_details' id='details_{$d->ma_id}'><a href='details.php?ma_id={$d->ma_id}&set=1'><img src='inc/imgs/icon_match_details.png'/></a></div>");
			$myPage->add_content("<div class ='col_trainer' id='trainer_{$d->ma_id}'><h3>Coach</h3><div class='deactivated'><img src='{$trainer->get_pic_path(true)}'/><br/>{$trainer->login}</div></div>");
			$myPage->add_content("<div class ='col_player' id='players_{$d->ma_id}'>");
			$myPage->add_content("<h3>Spieler</h3>");
			if($d->ma_trainee_id>0) {
				$player = new user($d->ma_trainee_id);
				$myPage->add_content("<div class='deactivated'><img src='{$player->get_pic_path(true)}'/><br/>{$player->login}</div>");
				if($d->ma_trainee_partner_id>0) {
					$player = new user($d->ma_trainee_partner_id);
					$myPage->add_content("<div class='deactivated'><img src='{$player->get_pic_path(true)}'/><br/>{$player->login}</div>");
				}
			} 
			$myPage->add_content("</div>");

			$myPage->add_content("<div class ='col_opponent' id='opponent_{$d->ma_id}'>");
			$myPage->add_content("<h3>Gegner</h3>");
			$myPage->add_content("{$d->ma_opponent_name}");

			$myPage->add_content("</div>");
			$myPage->add_content("<div class='col_text' id='text_{$d->ma_id}'><h3>Bemerkungen</h3>".nl2br($d->ma_description ?? '')."</div>");
			$myPage->add_content("<div class='col_delete' id='delete_{$d->ma_id}'><img src='../inc/imgs/query/delete_big.png'/></div>");
			$myPage->add_content("</div>");
		}
		print $myPage->get_html_code();
	}
	else
	{
		try {
			include('inc/php/ajax.php');
		} catch (\Throwable $th) {
			print $th->getMessage();
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
