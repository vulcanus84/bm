<?php
  define("level","../");                              //define the structur to to root directory (e.g. "../", for files in root set "")
  require_once(level."inc/standard_includes.php");    //Load all necessary files (DB-Connection, User-Login, etc.)
  require_once(level."inc/php/class_query.php");      //Class for the query
  require_once(level."inc/php/class_tournament.php");

  try
  {
	$txt = "";
    $myPage = new page();
    $myPage->login_required=false;
    $myPage->set_title('Turnierübersicht');
    $myTournament = new tournament(clone($db),$_GET['tournament_id']);

    if(!IS_AJAX)
    {
      if(isset($_GET['tournament_id']))
      {
        $arr_tournaments = explode(',',$_GET['tournament_id']);
        $myPage->add_js("var tournaments = [\"".implode('","', $arr_tournaments)."\"];");
      }

      //Display page
      $myPage->add_content("<div style='float:left;'><img src='".level."inc/imgs/bcz_logo.jpg' style='height:50px;padding-right:15px;padding-left:5px;'/></div><div id='title' style='float:left;'></div>");
      $myPage->add_content("<div id='left_side' style='clear:both;float:left;width:23vw;min-height:80vh;'>");
      $myPage->add_content("<div id='xx' style='float:left;padding-bottom:10px;'><button>Teilnehmer</button></div>");
      $myPage->add_content("<div id='users' style='float:left;width:20vw;min-height:80vh;border:10px solid #DDD;padding:10px;border-radius:20px;'></div>");
      $myPage->add_content("</div>");
      $myPage->add_content("<div id='middle_side' style='float:left;width:52vw;min-height:80vh;'>");
      $myPage->add_content("<div id='rounds' style='padding-bottom:10px;margin-left:10px;'></div>");
      $myPage->add_content("<div id='all_courts' style='width:92%;float:left;min-height:80vh;border:10px solid #DDD;padding:10px;margin-left:10px;border-radius:20px;'></div>");
      $myPage->add_content("</div>");
      $myPage->add_content("<div id='right_side' style='margin-left:10px;float:left;min-height:80vh;width:20vw;'>");
      $myPage->add_content("<div id='xxx' style='padding-bottom:10px;'><button>Neuigkeiten</button></div>");
      $myPage->add_content("<div id='news' style='overflow:auto;float:left;width:20vw;height:80vh;border:10px solid #DDD;padding:10px;border-radius:20px;'></div>");
      $myPage->add_content("</div>");
      $myPage->add_js(" var rounds=0;
                        var curr_round = 0;
                        var curr_tournament = 0;
                        function loadlink()
                        {
                          if(curr_round<rounds) 
                          { 
                            curr_round++; 
                            $('#news').load('visualisation.php?action=get_news&tournament_id='+tournaments[curr_tournament]);
                          } 
                          else 
                          { 
                            curr_round = 1; 
                            if(tournaments.length>curr_tournament+1) { curr_tournament++; } else { curr_tournament = 0; }
                            $.ajax({
                              method: 'GET',
                              url: 'visualisation.php',
                              data: { action: 'get_number_of_rounds', tournament_id: tournaments[curr_tournament] }
                            })
                            .done(function(data) {
                              rounds=data;
                            });
                            $('#title').load('visualisation.php?action=get_title&tournament_id=".$_GET['tournament_id']."&curr_id='+tournaments[curr_tournament]);
                            $('#users').load('visualisation.php?action=get_users&tournament_id='+tournaments[curr_tournament]);
                            $('#rounds').load('visualisation.php?action=get_rounds&tournament_id='+tournaments[curr_tournament]);
                            $('#news').load('visualisation.php?action=get_news&tournament_id='+tournaments[curr_tournament]);
                          }
            							var i = 1;
            							for(i;i<rounds+1;i++)
            							{
                            $('#round'+i).css('background-color','#4CAF50');
            							}

                          $('#all_courts').fadeOut( function() { 
                          $('#all_courts').load('visualisation.php?action=get_courts&tournament_id='+tournaments[curr_tournament]+'&round='+curr_round,function () {
                            setTimeout(function(){ $('#all_courts').fadeIn()},500 );
                            $('#round'+curr_round).css('background-color','orange');

                          });
                          });
                          }
                          setInterval(function(){
                              loadlink()
                          }, 6000);
                          
                          $( document ).ready(function() {
                              $.ajax({
                                method: 'GET',
                                url: 'visualisation.php',
                                data: { action: 'get_number_of_rounds', tournament_id: tournaments[curr_tournament] }
                              })
                              .done(function(data) {
                                rounds=data;
                              });
                              $('#title').load('visualisation.php?action=get_title&tournament_id='+tournaments[curr_tournament]);
                              $('#users').load('visualisation.php?action=get_users&tournament_id='+tournaments[curr_tournament]);
                              $('#rounds').load('visualisation.php?action=get_rounds&tournament_id='+tournaments[curr_tournament]);
 		                          $('#news').load('visualisation.php?action=get_news&tournament_id='+tournaments[curr_tournament]);
                              loadlink();                         
                          });
                          ");


      print $myPage->get_html_code('x');
    }
    else
    {
      //Return the requested data
      if(isset($_GET['action']) && $_GET['action']=='get_title')
      {
      	$arr_tournaments = explode(',',$_GET['tournament_id']);
      	foreach($arr_tournaments as $tournament)
      	{
	        $myTournament = new tournament(clone($db),$tournament);
	        if($_GET['curr_id']==$tournament)
	        {
		        print "<div style='border-left:5px solid gray;padding:5px 20px 5px 20px;float:left;background-color:#CCC;font-size:24pt;font-weight:bold;'>".$myTournament->get_title()."</div>";      
	        }
	        else
	        {
		        print "<div style='border-left:5px solid gray;padding:5px 20px 5px 20px;float:left;font-size:24pt;font-weight:bold;'>".$myTournament->get_title()."</div>";      
	        }
      	}
      	print "<div style='clear:both;font-size:12pt;'>&nbsp;</div>";
      }

      if(isset($_GET['action']) && $_GET['action']=='get_news')
      {
        $db->sql_query("SELECT * FROM news WHERE news_tournament_id='".$_GET['tournament_id']."' ORDER BY news_date DESC");
        $x = "";
 				while($d = $db->get_next_res())
 				{
 					$x.= "<span style='font-weight:bold;font-size:12pt;'>".$d->news_title."</span><br/><span style='font-size:10pt;'>".$d->news_text."</span><hr/>";
 				}
      	print $x;
      }

      if(isset($_GET['action']) && $_GET['action']=='get_rounds')
      {
        $db->sql_query("SELECT MAX(game_round) as game_round,MAX(game_status) as game_status FROM games WHERE game_group_id='$_GET[tournament_id]' GROUP BY game_round");
  			if($db->count()>0)
  			{
	      	if($myTournament->get_system()=='Gruppenspiele')
	      	{
	          $one_round_more = 2;
						print "<button id='round1'>Tabelle</button>";
	      	}
	      	else
	      	{
	          $rounds = $db->count();
	          $one_round_more = $rounds+1;
	  				while($d = $db->get_next_res())
	  				{
							print "<button id='round$d->game_round'>Runde $d->game_round</button>";
		 				}
					}
	      	if($myTournament->get_status()=='Closed') { print "<button id='round$one_round_more'>Siegerehrung</button>"; }
  			}
  			else
  			{
						print "<button id='no_round'>Noch keine Runde definiert</button>";
  			}
      }
      
      if(isset($_GET['action']) && $_GET['action']=='get_number_of_rounds')
      {
      	if($myTournament->get_system()=='Gruppenspiele')
      	{
	        if($myTournament->get_status()=='Closed')
	        {
						print "2";
	        }
	        else
	        {
						print "1";
	        }
      	}
      	else
      	{
	        $db->sql_query("SELECT MAX(game_round) as game_round,MAX(game_status) as game_status FROM games WHERE game_group_id='$_GET[tournament_id]' GROUP BY game_round");
	        if($myTournament->get_status()=='Closed')
	        {
	        	print $db->count()+1;
	        }
	        else
	        {
	        	print $db->count();
	        }
      	}
      }

      if(isset($_GET['action']) && $_GET['action']=='get_users')
      {
        print $myTournament->get_users_from_tournament('narrow');
      }

      if(isset($_GET['action']) && $_GET['action']=='get_courts')
      {
				if($myTournament->get_system()=='Gruppenspiele' AND $_GET['round']=='1')
				{
					//Show overview of Gruppenspiele
					
					$db->sql_query("SELECT * FROM group2user WHERE group2user_group_id='$_GET[tournament_id]'");
					$anz = $db->count()+1;

					//Generate empty array with the right dimensions
					$arr_table = array();
					$i=0;
					for($i;$i<$anz;$i++)
					{
						$j=0;						
						for($j;$j<$anz;$j++)
						{
							$arr_table[$i][$j] = "<td style='text-align:center;'></td>";
						}
					}
					
					//Fill static fields
					$i=1;
					$arr_table[0][0] = "<td></td>";
					for($i;$i<$anz;$i++)
					{
						$arr_table[$i][$i] = "<td style='background: repeating-linear-gradient(45deg, transparent, #CCC 10px);'></td>";
					}
					
					//Fill Players
					$i = 1;
					$arr_players = array();
					$db_temp = clone($db);
					$pic_width = 30/$db->count().'vw';
					while($d = $db->get_next_res())
					{
						$myUser = new user($d->group2user_user_id);
						$arr_table[0][$i] = "<td style='text-align:center;'>".$myUser->get_picture(false,'show_user_games',$pic_width,true)."<br/>".$myUser->login."</td>";	
						$arr_table[$i][0] = "<td style='text-align:center;'>".$myUser->get_picture(false,'show_user_games',$pic_width,true)."<br/>".$myUser->login."</td>";	
						$arr_players[$i] = $d->group2user_user_id;
						$i++;
					}

					//Fill games
					$i = 1;
					foreach($arr_players as $player)
					{
						$db_temp->sql_query("SELECT * FROM games WHERE game_group_id='".$myTournament->id."' AND (game_player1_id='$player' OR game_player2_id='$player')");
						while($temp = $db_temp->get_next_res())
						{
							if($temp->game_player1_id == $player) { $opponent = $temp->game_player2_id; $player_is_first_player = true; } else { $opponent = $temp->game_player1_id; $player_is_first_player = false; }
							$j = 1;
							foreach($arr_players as $player_tmp)
							{
								if($player_tmp==$opponent)
								{
									if($temp->game_winner_id=='')
									{
										$arr_table[$i][$j] = "<td style='text-align:center;'>Spiel in Runde<br/><span style='font-weight:bold;'>".$temp->game_round."</span></td>";
									}
									else
									{
										if($player_is_first_player)
										{
											if($temp->game_set1_p1<1 AND $temp->game_set1_p2<1)
											{
												$myUser = new user($temp->game_winner_id);
												$txt = "<span style='font-size:16pt;font-weight:bold;'>".$myUser->login."</span><br/>hat gewonnen";
											}
											else
											{
												$txt = "<span style='font-size:16pt;font-weight:bold;'>".$temp->game_set1_p1.":".$temp->game_set1_p2;
												if($temp->game_set2_p1>0 OR $temp->game_set2_p2>0) {  $txt .= "<br/>".$temp->game_set2_p1.":".$temp->game_set2_p2; }
												if($temp->game_set3_p1>0 OR $temp->game_set3_p2>0) {  $txt .= "<br/>".$temp->game_set3_p1.":".$temp->game_set3_p2; }
												$txt.= "</span>";
											}
										}
										else
										{
											if($temp->game_set1_p1<1 AND $temp->game_set1_p2<1)
											{
												$myUser = new user($temp->game_winner_id);
												$txt = "<span style='font-size:16pt;font-weight:bold;'>".$myUser->login."</span><br/>hat gewonnen";
											}
											else
											{
												$txt = "<span style='font-size:16pt;font-weight:bold;'>".$temp->game_set1_p2.":".$temp->game_set1_p1;
												if($temp->game_set2_p1>0 OR $temp->game_set2_p2>0) {  $txt .= "<br/>".$temp->game_set2_p2.":".$temp->game_set2_p1; }
												if($temp->game_set3_p1>0 OR $temp->game_set3_p2>0) {  $txt .= "<br/>".$temp->game_set3_p2.":".$temp->game_set3_p1; }
												$txt.= "</span>";
											}
										}
										
										$arr_table[$i][$j] = "<td style='text-align:center;'>".$txt."</td>";
								}
								}
								$j++;
							}
						}
						$i++;
					}
					//Print array as table
					$x = "<table border='0' style='width:100%;'>";
					foreach($arr_table as $row)
					{
						$x.= "<tr>";
						foreach($row as $col)
						{
							$x.= $col;
						}
						$x.= "</tr>";
					}
					$x.= "</table>";
					print $x;
				}
				else
      	{
	  			if(isset($_GET['round']) && $myTournament->get_rounds()>=$_GET['round'] && $myTournament->get_system()!='Gruppenspiele') 
	  			{
	  				$i=1;
	  				for($i;$i<=$myTournament->get_number_of_courts();$i++)
	  				{
	  					$db->sql_query_with_fetch("SELECT * FROM games WHERE game_group_id='".$_GET['tournament_id']."' AND game_round='$_GET[round]' AND game_location='".$i."'");
	  					if($db->count()>0) 
	  					{
	  					 	$data = $db->sql_query_with_fetch("SELECT * FROM games WHERE game_group_id='".$_GET['tournament_id']."' AND game_round='$_GET[round]' AND game_location='".$i."'");
	  						if($data->game_status=='Closed')
	  						{
	  							print "<div class='court' style='height:unset;' id='court$i'><img src='court.php?action=fill&game_id=$data->game_id' class='court'/></div>"; 
	  						}
	  						else
	  						{
	  							print "<div class='court' style='height:unset;' id='court$i'><img src='court.php?action=fill&game_id=$data->game_id' class='court' onclick='check_result($i);'/></div>"; 
	  						}
	  					}
	  					else
	  					{
	  						print "<div class='court' id='court$i'><img src='court.php?action=clear' class='court'/></div>"; 
	  					}
	  				}
	  			}
  			}
  
  			if($myTournament->get_status()=='Closed')
  			{
  				if($myTournament->get_rounds()<$_GET['round'] OR ($myTournament->get_system()=='Gruppenspiele' AND $_GET['round']=='2'))
  				{
  					$x = "";
  					$i = 0;
				  	$db->sql_query("SELECT * FROM group2user
				  												LEFT JOIN users ON group2user_user_id = user_id 
				  												WHERE group2user_group_id = '$_GET[tournament_id]' 
				  												ORDER BY group2user_wins DESC, group2user_BHZ DESC, group2user_FBHZ DESC, user_birthday DESC LIMIT 3");
  					while($d = $db->get_next_res())
  					{
  						$i++;
  						if($i==1) { $p1 = $d->group2user_user_id; } 
  						if($i==2) { $p2 = $d->group2user_user_id; } 
  						if($i==3) { $p3 = $d->group2user_user_id; } 
  					}
  					$x.= "<div style='width:70%;margin:0 auto;'><img style='width:100%;' src='podest.php?p1=$p1&p2=$p2&p3=$p3'/>";
  					$x.= "</div><div style='clear:both;margin-left:3vw;margin-right:3vw;'>";
  	
				  	$db->sql_query("SELECT * FROM group2user
				  												LEFT JOIN users ON group2user_user_id = user_id 
				  												WHERE group2user_group_id = '$_GET[tournament_id]' 
				  												ORDER BY group2user_wins DESC, group2user_BHZ DESC, group2user_FBHZ DESC, user_birthday DESC LIMIT 3,100");
  					while($d = $db->get_next_res())
  					{
  						$i++;
  						$my_user = new user($d->group2user_user_id);
  						$x.= "<div style='float:left;width:15%;text-align:center;margin-left:5px;margin-top:30px;'><b>Rang ".$i."</b><br/>".$my_user->get_picture(false,null,'100%',false)."<br/>".$my_user->login."</div>";
  						$my_user = null;
  						$last_wins = $d->group2user_wins;
  					}
  					$x.= "</div>";
  	
  					print $x;
  				}
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