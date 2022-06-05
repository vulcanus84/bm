<?php
  define("level","../");                              //define the structur to to root directory (e.g. "../", for files in root set "")
  require_once(level."inc/standard_includes.php");    //Load all necessary files (DB-Connection, User-Login, etc.)
  require_once(level."inc/php/class_query.php");      //Class for the query

  try
  {
    $myPage = new page();
		$page->change_parameter('x','1');
		$_SERVER['link'] = $page->get_link();

    if(!IS_AJAX)
    {
      //Display page
      $myPage->set_title("Felder verwalten");
      $myPage->add_js_link(level."inc/js/jquery.ui.touch.js");
      $myPage->add_css("
      	div.draggable { width:24vw;float:left;touch-action: none; }
      	div.hover { background-color:#DDD; }
      	div.xxx { background-color:yellow; }
      ");
      
      $myPage->add_js("
      	var t_courts = [];
	      $( function() {
	      	
	      	get_open_games();

			    $('.droppable').droppable({
			      hoverClass: 'hover',
			      drop: function( event, ui ) {
		          var game_id = ui.draggable.attr('id');
		          var court = $(this).attr('id');
							$('#'+court).load('$_SERVER[link]&ajax=refresh_court&court='+court+'&game_id='+game_id,
							function()
							{
				       	ui.draggable.hide();
// 								$('#open_games').load('$_SERVER[link]&ajax=get_open_games',
// 									function() { 			    
// 										$('.draggable').draggable({
// 										revert: 'invalid'
// 						    	});
// 									$('#'+court).droppable('disable')
// 								});
							});
			      }
			    });
			  });
  		");
  		$db->sql_query("SELECT *, DATE_FORMAT(court_started_on,'%Y-%m-%dT%H:%i:%S') as js_date FROM courts WHERE court_status = 'play'");
  		while($d = $db->get_next_res())
  		{
				$myPage->add_js("t_courts[".$d->court_no."] = setInterval(function() { makeTimer('$d->court_no','$d->js_date+02:00'); }, 1000);");
  		}  		
  		
			$myPage->add_js("setInterval(function() { get_open_games(); }, 60000);");
			$myPage->add_js("
				function makeTimer(court,startTime) 
				{
					startTime = (Date.parse(startTime) / 1000);
		
					var now = new Date();
					now = (Date.parse(now) / 1000);
		
					var timeElapsed = now - startTime; 
					var days = Math.floor(timeElapsed / 86400); 
					var hours = Math.floor((timeElapsed - (days * 86400)) / 3600);
					var minutes = Math.floor((timeElapsed - (days * 86400) - (hours * 3600 )) / 60);
					var seconds = Math.floor((timeElapsed - (days * 86400) - (hours * 3600) - (minutes * 60)));
		  
					if (hours < '10') { hours = '0' + hours; }
					if (minutes < '10') { minutes = '0' + minutes; }
					if (seconds < '10') { seconds = '0' + seconds; }
		
					$('#timer_court'+court).html(hours + ':' + minutes + ':' + seconds);
				}
				
				
				function get_open_games()
				{
					$('#open_games').load('$_SERVER[link]&ajax=get_open_games',
						function() { 			    
							$('.draggable').draggable({
							revert: 'invalid'
			    	});
					});
				}
				
				function play_court(court_id,game_id,resume=false)
				{
					if(resume) 
					{ 
						var my_url = '$_SERVER[link]&ajax=resume&court='+court_id+ '&game_id='+game_id;
						$.ajax({ url: my_url }).done(
						function(data)
						{
							location.reload();
						});
					}
					else
					{
						$('#court'+court_id).load('$_SERVER[link]&ajax=set_start_time&court='+court_id+ '&game_id='+game_id,
						function()
						{
							var x = new Date();
							t_courts[court_id] = setInterval(function() { makeTimer(court_id,x.toUTCString()); }, 1000);
						});
					}
					
				}
				function stop_court(court_id)
				{
					clearInterval(t_courts[court_id]);
					$('#court'+court_id).load('$_SERVER[link]&ajax=stopp_time&court='+court_id,
					function()
					{
					});
				}

				function save_court(court_id)
				{
					clearInterval(t_courts[court_id]);
					$('#court'+court_id).load('$_SERVER[link]&ajax=save_court&court='+court_id,
					function()
					{
						$('#court'+court_id).droppable('enable');
					});
				}


				function clear_court(court_id,game_id)
				{
					$('#court'+court_id).load('$_SERVER[link]&ajax=refresh_court&court='+court_id+ '&game_id=',
					function()
					{
						$('#court'+court_id).droppable('enable');
						get_open_games();
					});
				}
			");
  
  		$myPage->add_content("<div id='open_games' style='float:left;height:80vh;border-right:5px solid #CCC;width:25vw;'>");
  		$myPage->add_content("</div>");

      $myPage->add_content(get_court_table($db,2,3));
            
      print $myPage->get_html_code();
    }
    else
    {
			if($_GET['ajax']=='set_start_time') 
			{
				$db->update(array('court_status'=>'play','court_started_on'=>'CURRENT_TIMESTAMP','court_stopped_on'=>NULL),'courts','court_no',$_GET['court']);
				//Get no court image
				print get_court($db, $_GET['court']);
			}

			if($_GET['ajax']=='stopp_time') 
			{
				$db->update(array('court_status'=>'stopped','court_stopped_on'=>'CURRENT_TIMESTAMP'),'courts','court_no',$_GET['court']);
				//Get no court image
				print get_court($db, $_GET['court']);
			}

			if($_GET['ajax']=='resume') 
			{
				$db->update(array('court_status'=>'play','court_stopped_on'=>NULL),'courts','court_no',$_GET['court']);
			}

			if($_GET['ajax']=='save_court') 
			{
	 			$data = $db->sql_query_with_fetch("SELECT *,(TIME_TO_SEC(court_stopped_on) - TIME_TO_SEC(court_started_on)) AS seconds FROM courts WHERE court_no='$_GET[court]'");
				$db->update(array('game_duration'=>$data->seconds,'game_started_on'=>$data->court_started_on,'game_stopped_on'=>$data->court_stopped_on),'games','game_id',$data->court_game_id);
				$db->update(array('court_status'=>'empty','court_started_on'=>'NULL','court_stopped_on'=>'NULL','court_game_id'=>'NULL'),'courts','court_no',$_GET['court']);
				//Get no court image
				print get_court($db, $_GET['court']);
			}


			if($_GET['ajax']=='get_open_games') 
			{
      	print "<div style='font-size:20pt;'>Offene Spiele  <img src='".level."inc/imgs/refresh.png' alt='Refresh' style='width:2vw;' onclick='get_open_games();'/><hr></div>";	
				$last_game_round = '';
				$last_tournament = '';
				$db2 = clone($db);
	      $db->sql_query("SELECT * FROM games 
	      									LEFT JOIN courts ON court_game_id = games.game_id
	      									LEFT JOIN groups ON game_group_id = group_id
	      									WHERE game_status='New' AND game_duration is NULL AND court_no IS NULL
	      									ORDER BY game_round ASC, group_title ASC 
	      									LIMIT 10");
	      while($d = $db->get_next_res())
	      {
	      	if($last_tournament!=$d->group_title) 
	      	{ 
		      	print "<div style='font-size:20pt;'>$d->group_title</div>";	
	      		$last_tournament = $d->group_title; 
	      		$last_game_round='';
	      	}
	      	if($last_game_round!=$d->game_round) 
	      	{ 
		      	print "<div style='font-size:12pt;'>Runde $d->game_round</div>";	
	      		$last_game_round = $d->game_round; 
	      	}
	      	$db2->sql_query("SELECT *,DATE_FORMAT(game_stopped_on,'%Y-%m-%d %H:%i:%s') as stopped,DATE_FORMAT(game_started_on,'%Y-%m-%d %H:%i:%s') as started FROM games
	      										LEFT JOIN groups ON game_group_id = group_id
	      										WHERE group_status='Started' AND game_stopped_on IS NOT NULL AND (game_player1_id='$d->game_player1_id' OR game_player2_id='$d->game_player1_id') ORDER BY game_stopped_on DESC");
	      	if($db2->count()>0)
	      	{
	      		$d2 = $db2->get_next_res();
	      		$resttime_p1 = $helper->datediff($d2->stopped,'','s');
	      		$resttime_p1_txt = gmdate("H:i",$resttime_p1);
	      	}
	      	else
	      	{
	      		$resttime_p1 = 9999;
	      		$resttime_p1_txt = "--:--";
	      	}
	      	$db2->sql_query("SELECT *,DATE_FORMAT(game_stopped_on,'%Y-%m-%d %H:%i:%s') as stopped,DATE_FORMAT(game_started_on,'%Y-%m-%d %H:%i:%s') as started FROM games
	      										LEFT JOIN groups ON game_group_id = group_id
	      										WHERE group_status='Started' AND game_stopped_on IS NOT NULL AND (game_player1_id='$d->game_player2_id' OR game_player2_id='$d->game_player2_id') ORDER BY game_stopped_on DESC");
	      	if($db2->count()>0)
	      	{
	      		$d2 = $db2->get_next_res();
	      		$resttime_p2 = $helper->datediff($d2->stopped,'','s');
	      		$resttime_p2_txt = gmdate("H:i",$resttime_p2);
	      	}
	      	else
	      	{
	      		$resttime_p2 = 9999;
	      		$resttime_p2_txt = "--:--";
	      	}
	      	if($resttime_p1<900 OR $resttime_p2<900) { $zus_txt = 'background-color:orange;'; } else { $zus_txt = ''; }
	      	print "<div class='draggable' style='border-radius:10px;margin-bottom:5px;$zus_txt' id='$d->game_id'>";
	      	print "	<table><tr><td style='text-align:center;width:10%;'><img src='sleep.svg' style='width:100%;'/><br/>$resttime_p1_txt</td>";
	      	print "	<td><img src='court.php?action=fill&game_id=$d->game_id' class='court'/></td>";
	      	print "	<td style='text-align:center;width:10%;'><img src='sleep.svg' style='width:100%;'/><br/>$resttime_p2_txt</td></tr></table>";
	      	print "</div>";	
	      }
			}

			if($_GET['ajax']=='refresh_court') 
			{ 
				//Update court information
				$court_no = str_replace('court','',$_GET['court']);
				$db->sql_query("SELECT * FROM courts WHERE court_no='$court_no'"); 
	  		if($db->count()>0) 
	  		{
	  			if(!isset($_GET['game_id']) OR $_GET['game_id']=='')
	  			{
						$db->update(array('court_no'=>$court_no,'court_game_id'=>NULL,'court_status'=>'empty','court_started_on'=>NULL,'court_stopped_on'=>NULL),'courts','court_no',$court_no);
	  			}
	  			else
	  			{
						$db->update(array('court_status'=>'assigned','court_game_id'=>$_GET['game_id']),'courts','court_no',$court_no);
	  			}
	  		}
	  		else
	  		{
	  			if(!isset($_GET['game_id']) OR $_GET['game_id']=='')
	  			{
	  			}
	  			else
	  			{
						$db->insert(array('court_no'=>$court_no,'court_status'=>'assigned','court_game_id'=>$_GET['game_id']),'courts');
	  			}
	  		}
				//Get no court image
				print get_court($db, $court_no);
				
			}
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
  	$x = "<div style='float:left;height:80vh;width:72vw;'>";
  	$x.= "<table style='width:100%;height:100%;'>";
  	for($i=0;$i<$rows;$i++)
  	{
	  	$x.= "	<tr>";
  		for($j=0;$j<$cols;$j++)
  		{
  			$court_no++;
  			$court_no_eff = $court_numbers[$court_no];
	  		$x.= "		<td>";
		
		 		$db->sql_query("SELECT * FROM courts WHERE court_no='$court_no_eff'"); 
	 			$data = $db->get_next_res(); 
		 		if($db->count()==1) 
		 		{ 
			 		if($data->court_status=='empty' OR $data->court_status=='')
			 		{
						$x.= "			<div id='court".$court_no_eff."' class='droppable' style='border:2px solid #ccc;border-radius:2vh;'>";
			 		}
			 		else
			 		{
						$x.= "			<div id='court".$court_no_eff."' style='border:2px solid #ccc;border-radius:2vh;'>";
			 		}
		 		}
		 		else
		 		{
					$x.= "			<div id='court".$court_no_eff."' class='droppable' style='border:2px solid #ccc;border-radius:2vh;'>";
		 		}
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
 		$x = "				<table><tr>";
 		$x.= "				<td style='text-align:center;font-size:24pt;background-color:#CCC;border-radius:20px;'>&nbsp;$court_no&nbsp;</td>";

 		if($db->count()==1) 
 		{ 
	 		if($data->court_status=='empty' OR $data->court_status=='')
	 		{
		 		$x.= "					<td><div style='position:relative;'><img src='court.php?action=$txt' class='court'/></td>";
		 		$x.= "					<td >";
	  		$x.= "						<img src='play.png' id='play_court$court_no' style='width:3vw;opacity:0.3;'/>";
	  		$x.= "						<img src='stop.png' id='stop_court$court_no' style='width:3vw;opacity:0.3;'/>";
	  		$x.= "						<img src='clear.png' id='clear_court$court_no' style='width:3vw;opacity:0.3;'/>";
		 		$x.= "					</td>";
	 		}
		  		
	 		if($data->court_status=='assigned')
	 		{
		 		$x.= "					<td><div style='position:relative;'><img src='court.php?action=$txt' class='court'/></td>";
		 		$x.= "					<td>";
	  		$x.= "						<img src='play.png' id='play_court$court_no' style='width:3vw;cursor:pointer;' onclick='play_court($court_no,".$data->court_game_id.");'/>";
	   		$x.= "						<a href='../app_tournaments/match_pdf.php?tournament_id=$data->game_group_id&game_id=$data->court_game_id' target='_blank'><img src='match_paper.png' id='stop_court$court_no' style='width:3vw;opacity:1;cursor:pointer;'/></a>";
	  		$x.= "						<img src='clear.png' id='clear_court$court_no' style='width:3vw;cursor:pointer;' onclick='clear_court($court_no);'/>";
		 		$x.= "					</td>";
	 		}
	
	 		if($data->court_status=='play')
	 		{
		 		$x.= "					<td><div style='position:relative;'><img src='court.php?action=$txt' class='court' style='opacity:0.4;'/>";
		 		$x.= "					<div id='timer_court$court_no' style='position:absolute;top:50%;left:50%;transform:translate(-50%, -50%);font-size:24pt;font-weight:bold;'></div></div></td>";
		 		$x.= "					<td>";
	   		$x.= "						<a href='../app_tournaments/match_pdf.php?tournament_id=$data->game_group_id&game_id=$data->court_game_id' target='_blank'><img src='match_paper.png' id='stop_court$court_no' style='width:3vw;opacity:1;cursor:pointer;'/></a>";
	  		$x.= "						<img src='stop.png' id='stop_court$court_no' style='width:3vw;opacity:1;cursor:pointer;' onclick='stop_court($court_no);' />";
	  		$x.= "						<img src='clear.png' id='clear_court$court_no' style='width:3vw;opacity:0.3;'/>";
		 		$x.= "					</td>";
	 		}
	
	 		if($data->court_status=='stopped')
	 		{
	 			$data = $db->sql_query_with_fetch("SELECT *,(TIME_TO_SEC(court_stopped_on) - TIME_TO_SEC(court_started_on)) AS seconds FROM courts WHERE court_no='$court_no'");
	 			$time_txt = gmdate("H:i:s", $data->seconds);
	 			
		 		$x.= "					<td><div style='position:relative;'><img src='court.php?action=$txt' class='court' style='opacity:0.4;'/>";
		 		$x.= "					<div style='position:absolute;top:50%;left:50%;transform:translate(-50%, -50%);font-size:30pt;font-weight:bold;'>$time_txt</div></div></td>";
		 		$x.= "					<td>";
	  		$x.= "						<img src='play.png' id='play_court$court_no' style='width:3vw;cursor:pointer;' onclick='play_court($court_no,".$data->court_game_id.",true);'/>";
	  		$x.= "						<img src='save_big.png' id='save_court$court_no' style='width:3vw;cursor:pointer;' onclick='save_court($court_no);'/>";
	  		$x.= "						<img src='clear.png' id='clear_court$court_no' style='width:3vw;cursor:pointer;' onclick='clear_court($court_no);'/>";
		 		$x.= "					</td>";
	 		}
 		}
 		else
 		{
	 		$x.= "					<td><div style='position:relative;'><img src='court.php?action=$txt' class='court'/></td>";
	 		$x.= "					<td>";
  		$x.= "						<img src='play.png' id='play_court$court_no' style='width:3vw;opacity:0.3;'/>";
  		$x.= "						<img src='stop.png' id='stop_court$court_no' style='width:3vw;opacity:0.3;'/>";
  		$x.= "						<img src='clear.png' id='clear_court$court_no' style='width:3vw;opacity:0.3;'/>";
	 		$x.= "					</td>";
 		}
 		
 		$x.= "				</tr></table>";
 		return $x;
  }
?>