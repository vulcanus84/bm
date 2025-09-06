<?php
define("level","../");																//define the structur to to root directory (e.g. "../", for files in root set "")
require_once(level."inc/standard_includes.php");		//Load all necessary files (DB-Connection, User-Login, etc.)
global $db;

try
{	
	$myPage = new page();
	$myPage->add_js_link('inc/js/index.js');
	$myPage->add_css_link('inc/css/index.css');

	if(!IS_AJAX)
	{
		$db2 = clone($db);
		$last_date = null;
		$myPage->add_content("<div id='myModal' class='modal'>");
		$myPage->add_content("  <div class='modal-content'>");
		$myPage->add_content("    <span class='close'>&times;</span>"); //&times = sign for multiplication
		$myPage->add_content("    <p id='myModalText'></p>");
		$myPage->add_content("  </div>");
		$myPage->add_content("</div>");

		$db->sql_query("SELECT *, DATE_FORMAT(journal_created_on,'%d.%m.%Y') as curr_date FROM journal ORDER BY journal_created_on DESC");

		$myPage->add_content("<div class='add_entry'><img src='../inc/imgs/query/add.png'/></div>"); 

		while($d = $db->get_next_res())
		{
			if($d->curr_date!=$last_date)
			{
				$myPage->add_content("<section>".$d->curr_date."</section>");
				$last_date = $d->curr_date;
			}
			$trainer = new user($d->journal_created_by);
			$myPage->add_content("<div class='row'>");
			$myPage->add_content("<div class ='col_trainer' id='trainer_".$d->journal_id."'>".$trainer->get_picture(true)."</div>");
			$myPage->add_content("<div class ='col_player' id='players_".$d->journal_id."'>");
			$db2->sql_query("SELECT * FROM journal2user WHERE journal2user_journal_id='".$d->journal_id."'");
			while($d2 = $db2->get_next_res())
			{
				$player = new user($d2->journal2user_user_id);
				$myPage->add_content("<div class='deactivated'><img src='".$player->get_pic_path(true)."'/><br/>".$player->login."</div>");
			}
			$myPage->add_content("</div>");
			$myPage->add_content("<div class='col_text' id='text_".$d->journal_id."'>".nl2br($d->journal_text ?? '')."</div>");
			$myPage->add_content("<div class='col_delete' id='delete_".$d->journal_id."'><img src='../inc/imgs/query/delete_big.png'/></div>");
			$myPage->add_content("</div>");
		}
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
?>
