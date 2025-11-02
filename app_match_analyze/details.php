<?php
define("level","../");																//define the structur to to root directory (e.g. "../", for files in root set "")
require_once(level."inc/standard_includes.php");		//Load all necessary files (DB-Connection, User-Login, etc.)
global $db;

try
{	
	if(!IS_AJAX)
	{
		$myPage = new page();
		$myPage->add_js_link('inc/js/details.js');
		$myPage->add_js_link('inc/js/chart.js');
		$myPage->add_css_link('inc/css/details.css');
		$myPage->add_content("<div id='myModal' class='modal'>");
		$myPage->add_content("  <div class='modal-content'>");
		$myPage->add_content("    <span class='close'>&times;</span>"); //&times = sign for multiplication
		$myPage->add_content("    <p id='myModalText'></p>");
		$myPage->add_content("  </div>");
		$myPage->add_content("</div>");

		$d = $db->sql_query_with_fetch("SELECT *, DATE_FORMAT(ma_created_on,'%d.%m.%Y') as curr_date FROM match_analyzes WHERE ma_id=:ma_id  ORDER BY ma_created_on DESC",array('ma_id'=>$_GET['ma_id']));

		$myPage->add_content("
			<div class='header_sets'>
				<a href='details.php?ma_id={$_GET['ma_id']}&set=1'><button id='btn_set1'>Satz 1</button></a>
				<a href='details.php?ma_id={$_GET['ma_id']}&set=2'><button id='btn_set2'>Satz 2</button></a>
				<a href='details.php?ma_id={$_GET['ma_id']}&set=3'><button id='btn_set3'>Satz 3</button></a>
			</div>
		");

		$d2 = $db->sql_query("SELECT Count(*) as points_count, MAX(ma_point_winner) as ma_point_winner
													FROM match_analyzes_points 
													LEFT JOIN match_analyzes ON match_analyzes.ma_id = match_analyzes_points.ma_point_ma_id
													LEFT JOIN match_analyzes_reasons ON match_analyzes_points.ma_point_reason_id = match_analyzes_reasons.ma_reason_id
													WHERE ma_point_ma_id=:ma_point_ma_id AND ma_point_set=:ma_point_set  
													GROUP BY ma_point_winner
													",array('ma_point_ma_id'=>$_GET['ma_id'], 'ma_point_set'=>$_GET['set'] ?? 1));
		$points_player = 0;
		$points_opponent = 0;

		while($d2 = $db->get_next_res())
		{
			if($d2->ma_point_winner=='trainee')
			{
				$points_player = $d2->points_count;
			}
			else
			{
				$points_opponent = $d2->points_count;
			}
		}


		$myPage->add_content("<div>
  <canvas id='chartMainReasons'></canvas>
  <canvas id='chartMainReasonsOpponent'></canvas>
	<canvas id='chartStrokes'></canvas>
	<canvas id='chartOuts'></canvas>
  <canvas id='chartPointIncreases'></canvas>

</div>");
		$myPage->add_content("
			<div class='header_points'>
				<div id='points_player'>0</div>
				<div class='double_point'>:</div>
				<div id='points_opponent'>0</div>
			</div>
		");

		$myPage->add_content("
			<div class='header_players'>
				<div class='player' id='point_for_trainee'>");
				if($d->ma_trainee_id>0) {
					$player = new user($d->ma_trainee_id);
					$myPage->add_content("<div class='deactivated' style='float:right;'><img style='width:20vw;' src='{$player->get_pic_path(true)}'/><br/>{$player->login}</div>");
					if($d->ma_trainee_partner_id>0) {
						$player = new user($d->ma_trainee_partner_id);
						$myPage->add_content("<div class='deactivated'><img src='{$player->get_pic_path(true)}'/><br/>{$player->login}</div>");
					}
				} 

				$myPage->add_content("</div>
				<div class='vs'>VS</div>
				<div class='opponent' id='point_for_opponent'>{$d->ma_opponent_name}</div>
			</div>
		");

		$myPage->add_content("<hr style='width:100%;'/>");
		$myPage->add_content("<table id='points_table'></table>");
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
