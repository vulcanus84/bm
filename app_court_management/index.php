<?php
  define("level","../");                              //define the structur to to root directory (e.g. "../", for files in root set "")
  require_once(level."inc/standard_includes.php");    //Load all necessary files (DB-Connection, User-Login, etc.)

  try
  {
    $myPage = new page();
		$myPage->set_title("Felder verwalten");
		if(!$myPage->is_logged_in()) { print $myPage->get_html_code(); exit; }
		
		$myPage->add_js_link('inc/js/index.js');
		$myPage->add_css_link('inc/css/index.css');

    if(!IS_AJAX)
    {
      //Display page
      $myPage->add_js_link(level."inc/js/jquery.ui.touch.js");
      
  		$db->sql_query("SELECT *, DATE_FORMAT(court_started_on,'%Y-%m-%dT%H:%i:%S') as js_date FROM courts WHERE court_status = 'play'");
  		while($d = $db->get_next_res())
  		{
				$myPage->add_js("t_courts[".$d->court_no."] = setInterval(function() { makeTimer('$d->court_no','$d->js_date+02:00'); }, 1000);");
  		}  		
  		  
  		$myPage->add_content("<div id='open_games'></div>");
      $myPage->add_content(get_court_table($db,2,3));
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
  
  
  function get_court_table($db,$rows,$cols)
  {
  	$court_no=-1;
  	$court_numbers = array(4,5,6,1,2,3);
  	$x = "<div class='court_table'>";
  	$x.= "<table class='court_table'>";
  	for($i=0;$i<$rows;$i++)
  	{
	  	$x.= "	<tr>";
  		for($j=0;$j<$cols;$j++)
  		{
  			$court_no++;
  			$court_no_eff = $court_numbers[$court_no];
	  		$x.= "		<td>";
		
		 		$data = $db->sql_query_with_fetch("SELECT * FROM courts WHERE court_no='$court_no_eff'"); 
				if($data) { $game_id = $data->court_game_id; } else { $game_id = ''; }
				$x.= "			<div id='court{$court_no_eff}' data-game-id='{$game_id}' class='droppable real_court'>";
	  		$x.= get_court($db, $court_no_eff);
		 		$x.= "			</div>";
	  		$x.= "		</td>";
  		}
  	}
  	
  	return $x;
  }
  
  function get_court($db,$court_no)
  {
 		$db->sql_query("SELECT * FROM courts 
 										LEFT JOIN games ON court_game_id = game_id
 										WHERE court_no='$court_no'"); 
 		if($db->count()==1) 
 		{ 
 			$data = $db->get_next_res(); 
  		if($data->court_game_id>0) { $txt = "fill&game_id=".$data->court_game_id; } else { $txt = "clear"; }
 		}
 		else
 		{
 			$txt = "clear";
 		}
 		
		// linke Box mit Court-Nr.
		$x = "<div class='court_no'>{$court_no}</div>";

		// jetzt der mittlere Bereich mit dem Court-Bild
		$data2 = $db->sql_query_with_fetch("SELECT *,(TIME_TO_SEC(court_stopped_on) - TIME_TO_SEC(court_started_on)) AS seconds FROM courts WHERE court_no='$court_no'");
		$time_txt = gmdate("H:i:s", $data2->seconds ?? 0);

		$x .= "
			<div class='court_middle'>
				<img src='inc/php/court.php?action=$txt' class='court'/>
				<div class='court_timer' id='timer_court{$court_no}'>{$time_txt}</div>
			</div>";

		// rechts die Icons – auch wieder flex
		$x .= "<div class='court_buttons'>";

		// Buttons
		$main_path = 'inc/imgs/';
		$btn_play = $main_path."play.png";
		$btn_stop = $main_path."stop.png";
		$btn_clear = $main_path."clear.png";
		$btn_save = $main_path."save_big.png";
		$btn_match = $main_path."match_paper.png";

		if($db->count()==1) {
				if($data->court_status=='empty' OR $data->court_status=='') {
						$x .= "<img src='{$btn_play}' id='play_court{$court_no}' class='button_inactive''/>
									<img src='{$btn_stop}' id='stop_court{$court_no}' class='button_inactive''/>
									<img src='{$btn_clear}' id='clear_court{$court_no}' class='button_inactive''/>";
				}

				if($data->court_status=='assigned') {
						$x .= "<img src='{$btn_play}' id='play_court{$court_no}' class='button_active' onclick='play_court({$court_no},{$data->court_game_id});'/>
									<a href='../app_tournament/inc/php/match_pdf.php?tournament_id={$data->game_group_id}&game_id={$data->court_game_id}' target='_blank'>
											<img src='{$btn_match}' id='stop_court{$court_no}' class='button_active'/>
									</a>
									<img src='{$btn_clear}' id='clear_court{$court_no}' class='button_active' onclick='clear_court({$court_no});'/>";
				}

				if($data->court_status=='play') {
						$x .= "<a href='../app_tournament/inc/php/match_pdf.php?tournament_id={$data->game_group_id}&game_id={$data->court_game_id}' target='_blank'>
											<img src='{$btn_match}' id='stop_court{$court_no}' style='width:3vw;opacity:1;cursor:pointer;'/>
									</a>
									<img src='{$btn_stop}' id='stop_court{$court_no}' class='button_active' onclick='stop_court({$court_no});'/>
									<img src='{$btn_clear}' id='clear_court{$court_no}' class='button_inactive''/>";
				}

				if($data->court_status=='stopped') {
						$data = $db->sql_query_with_fetch("SELECT *,(TIME_TO_SEC(court_stopped_on) - TIME_TO_SEC(court_started_on)) AS seconds FROM courts WHERE court_no='{$court_no}'");
						$time_txt = gmdate("H:i:s", $data->seconds);

						$x .= "<img src='{$btn_play}' id='play_court{$court_no}' class='button_active' onclick='play_court({$court_no},".$data->court_game_id.",true);'/>
									<img src='{$btn_save}' id='save_court{$court_no}' class='button_active' onclick='save_court({$court_no});'/>
									<img src='{$btn_clear}' id='clear_court{$court_no}' class='button_active' onclick='clear_court({$court_no});'/>";
				}
		} else {
				$x .= "<img src='{$btn_play}' id='play_court{$court_no}' class='button_inactive''/>
							<img src='{$btn_stop}' id='stop_court{$court_no}' class='button_inactive''/>
							<img src='{$btn_clear}' id='clear_court{$court_no}' class='button_inactive''/>";
		}

		$x .= "</div>"; // Ende Icons
 		return $x;
  }
?>