<?php
define("level","../");																//define the structur to to root directory (e.g. "../", for files in root set "")
require_once(level."inc/standard_includes.php");		//Load all necessary files (DB-Connection, User-Login, etc.)
try
{
	if(isset($_GET['action']) && $_GET['action']=='filter')
	{
		$x = "";
		foreach($_POST as $id => $value)
		{
			$x.= $id.",";
		}
		$x = substr($x,0,-1);
		if($x!='') { $_SESSION['star_filter'] = $x; } else { $_SESSION['star_filter']=null; }
		header("Location: index.php?location=$_GET[location]");
	}
	
	$myPage = new page();
	$myTournament = new tournament($db);
	$logger = new log();

	$page->change_parameter('x','1');
	$_SERVER['link'] = $page->get_link();
	$myPage->add_css("
		td { font-size:2vw; }
		h2 { font-size:2.5vw; }
		img.star { width:100%;cursor:pointer; }
	");
	$myPage->add_js("

		function show_filter()
		{
			var my_url = '$_SERVER[link]&ajax=show_filter'
			$.ajax({ url: my_url }).done(
				function(data)
				{
					$('#myModalText').html(data); 
					$('#myModal').show();
				});
		}


		function confirmed(exam_id,user_id,star_id,modus)
		{
			if(modus=='add') { var my_url = '$_SERVER[link]&ajax=add_exam&exam_id='+exam_id+'&user_id='+user_id; }
			if(modus=='remove') { var my_url = '$_SERVER[link]&ajax=remove_exam&exam_id='+exam_id+'&user_id='+user_id; }
			if(my_url!='')
			{
				$.ajax({ url: my_url }).done(
					function(data)
					{
						$('#myModalText').html(data); 
						$('#myModal').hide();
						if(modus=='add')
						{
							$('#star_' + star_id).attr('src','".level."inc/imgs/star_full.png');
						}
						else
						{
							$('#star_' + star_id).attr('src','".level."inc/imgs/star_empty.png');
						}
					});
		
			}
			else
			{
				alert('Fehler');
			}

		}

		function aborted()
		{
			$('#myModal').hide()
		}

		function show_conf(exam_id, user_id,star_id)
		{
			var my_url = '$_SERVER[link]&ajax=get_text_add_exam&exam_id='+exam_id+'&user_id='+user_id+'&star_id='+star_id;
			$.ajax({ url: my_url }).done(
			function(data)
			{
				$('#myModalText').html(data); 
				$('#myModal').show();
			});

		}

	");


	if(!IS_AJAX)
	{
		//Display page
		$myPage->add_content("<div style='float:left;margin-top:2vw;margin-bottom:2vw;' id='sort_div'>");

		//Check permissions
		$w_str = "WHERE location_name NOT LIKE '\_%'";
		$db->sql_query("SELECT * FROM location_permissions
													LEFT JOIN locations ON loc_permission_loc_id = location_id
													WHERE loc_permission_user_id='".$_SESSION['login_user']->id."'");
		if($db->count()==0) { $w_str.= " AND location_id=0"; } else { $w_str.= " AND ("; }
		$i=0;
		while($d = $db->get_next_res())
		{
			if($i==0) { $w_str.= "location_id='$d->location_id'"; } else { $w_str.= " OR location_id='$d->location_id'"; }
			$i++;
		}
		if($db->count()>0) { $w_str.= ")"; }

		$db->sql_query("SELECT * FROM locations $w_str ORDER BY location_name");
		$myPage->add_content("<select onchange=\"window.location = 'index.php?location=' + this.value;\" style='width:95vw;height:10vh;font-size:5vh;color:black;'>");
		$myPage->add_content("<option value=''>-- Auswählen --</option>");
		$myPage->add_content("<option value='Aufgaben'");
		if(isset($_GET['location']) && $_GET['location']== 'Aufgaben') { $myPage->add_content(" selected='1'" ); }
		$myPage->add_content(">Aufgaben</option>");
		while($d = $db->get_next_res())
		{
			$myPage->add_content("<option value='".$d->location_id."'");
			if(isset($_GET['location']) && $_GET['location']== $d->location_id) { $myPage->add_content(" selected='1'" ); }
			$myPage->add_content(">".$d->location_name."</option>");
		}
		$myPage->add_content("</select>");
		$myPage->add_content("</div>");
		$myPage->add_content("<div id='main_div' style='clear:both;'>");
		$myPage->add_content("<hr/>");

		if(isset($_GET['location']) && $_GET['location']!='')
		{
			if($_GET['location']=='Aufgaben')
			{
				$db->sql_query("SELECT * FROM exams ORDER BY exam_category, exam_level");
				while($d = $db->get_next_res())
				{
					$txt = "";
					$txt.= "<h2 style='margin-top:0px;'>".$d->exam_category." / Aufgabe ".$d->exam_level."</h2>";
					$txt.= "<span style='margin-top:0px;font-weight:bold;'>".$d->exam_title."</span><br>";
					$txt.= nl2br($d->exam_description);
					$txt.= "<hr/>";
					$myPage->add_content($txt);
				}
			}
			else
			{
				$w_str = "user_training_location = '$_GET[location]' AND user_hide<1";

				if(isset($_SESSION['star_filter']))
				{
					$my_star_filter = explode(',',$_SESSION['star_filter']);
					$cat_w_str = "WHERE ";
					foreach($my_star_filter as $val)
					{
						$cat_w_str.= "exam_category = '".$val."' OR ";
					}
					$cat_w_str = substr($cat_w_str,0,-4);
				}
				else { $cat_w_str = ""; }

				$myPage->add_content("<table style='width:100%;border-spacing:1px;'>");
				$myPage->add_content("<tr><td></td>");
				$x="";
				$db->sql_query("SELECT MAX(exam_category) as cat, count(*) as anz FROM exams $cat_w_str GROUP BY exam_category ORDER BY MAX(exam_category) ASC");
				while($d = $db->get_next_res())
				{
					$myPage->add_content("<td onclick=\"show_filter();\" colspan='$d->anz' style='font-size:14pt;text-align:center;border:1px solid black;border-top-left-radius:10px;border-top-right-radius:10px;padding:1vh;background-color:#DDEEDD'>$d->cat</td>");
				}
				$myPage->add_content("</tr><tr><td></td>");

				$db->sql_query("SELECT * FROM exams $cat_w_str ORDER BY exam_category, exam_level ASC");
				while($d = $db->get_next_res())
				{
					$myPage->add_content("<td style='font-size:9pt;text-align:center;border:1px solid black;vertical-align:center;background-color:#EEDDDD;'><p style='writing-mode: vertical-lr;text-orientation:mixed;white-space:nowrap;margin:auto;padding:1vh;'>$d->exam_title</p></td>");
				}
				$myPage->add_content("</tr>");

				$db->sql_query("SELECT * FROM users
												WHERE $w_str
												ORDER BY user_account ASC");
				$db2 = clone($db);
				$db3 = clone($db);
				$star_count=0;
				while($d = $db->get_next_res())
				{
					$myPage->add_content("<tr>");
					$myPage->add_content("<td style='border:1px solid black;border-top-left-radius:10px;border-bottom-left-radius:10px;padding-left:5px;background-color:#DDDDEE;font-size:3vh;'>$d->user_account</td>");
					$db2->sql_query("SELECT * FROM exams $cat_w_str ORDER BY exam_category, exam_level ASC");
					while($d2 = $db2->get_next_res())
					{
						$star_count++;
						$db3->sql_query("SELECT * FROM exam2user 
											WHERE exam2user_exam_id='$d2->exam_id' AND exam2user_user_id='$d->user_id'");
						if($db3->count()>0) { $img = 'star_full.png'; } else { $img = 'star_empty.png'; }
						$myPage->add_content("					<td style='border:1px solid black;text-align:center;'><img id='star_".$star_count."' onclick=\"show_conf(".$d2->exam_id.",".$d->user_id.",".$star_count.");\" src='".level."inc/imgs/$img' style='height:20px;'/></td>");
					}
					$myPage->add_content("</tr>");
				}
				$myPage->add_content("</tr></table>");
			}
		}


		$myPage->add_content("</div>");
		print $myPage->get_html_code();
	}
	else
	{
		//************************************************************************************
		//AJAX Handling
		//************************************************************************************

		if($_GET['ajax']=='show_filter')
		{
			$my_star_filter = array();
			if(isset($_SESSION['star_filter']))
			{
				$my_star_filter = explode(',',$_SESSION['star_filter']);
			}
			$x = "";
			$x.= "<form method = 'POST' action='?location=$_GET[location]&action=filter'>";
			$x.= "<table>";
			$db->sql_query("SELECT MAX(exam_category) as cat, count(*) as anz, MAX(exam_id) as exam_id FROM exams GROUP BY exam_category ORDER BY MAX(exam_category) ASC");
			while($d = $db->get_next_res())
			{
				$x.= "<tr><td><input name='".$d->cat."' type='checkbox'";
				if(in_array($d->cat,$my_star_filter)) { $x.= "checked='1' "; }
				$x.= "style='width:20px;'/></td><td style='font-size:16pt;'>$d->cat</td></tr>";
			}
			$x.= "<tr><td colspan='2'><input type='submit' value='Filtern'/></td></tr>";
			$x.= "</table>";
			$x.= "</form>";
			print $x;
		}

		if($_GET['ajax']=='get_text_add_exam')
		{
			$exam = $db->sql_query_with_fetch("SELECT * FROM exams WHERE exam_id='$_GET[exam_id]'");
			$user = $db->sql_query_with_fetch("SELECT * FROM users WHERE user_id='$_GET[user_id]'");
			$db->sql_query("SELECT * FROM exam2user WHERE exam2user_user_id='$_GET[user_id]' AND exam2user_exam_id='$_GET[exam_id]'");
			$my_user = new user($_GET['user_id']);

			if($db->count()>0) 
			{
				$x = "Willst du <b>".$exam->exam_title. "</b> von <b>".$my_user->fullname."</b> <span style='color:red;'>entfernen</span>?";
				$x.= "<p><button onclick=\"confirmed($_GET[exam_id],$_GET[user_id],$_GET[star_id],'remove');\" style='width:37vw;'>Ja</button>&nbsp;<button onclick='aborted();' style='width:37vw;background-color:red;'>Nein</button>";
			}
			else
			{
				$x = "Willst du <b>".$exam->exam_title. "</b> zu <b>".$my_user->fullname."</b> hinzufügen?<p/>";
				$x.= "Es muss folgendes erreicht worden sein:<p/>".nl2br($exam->exam_description);
				$x.= "<p><button onclick=\"confirmed($_GET[exam_id],$_GET[user_id],$_GET[star_id],'add');\" style='width:37vw;'>Ja</button>&nbsp;<button onclick='aborted();' style='width:37vw;background-color:red;'>Nein</button>";
			}
			print $x;
		}


		if($_GET['ajax']=='add_exam')
		{
			$d = $db->sql_query_with_fetch("SELECT * FROM exams WHERE exam_id='$_GET[exam_id]'");
			$db->insert(array('exam2user_exam_id'=>$_GET['exam_id'],'exam2user_user_id'=>$_GET['user_id']),'exam2user');
			$my_user = new user($_GET['user_id']);
			$my_user->create_star_image();

			//Logging
			$logger->write_to_log('Training Stars','Add Star for "'.$d->exam_title.'" to "'.$my_user->fullname.'"');
		}

		if($_GET['ajax']=='remove_exam')
		{
			$d = $db->sql_query_with_fetch("SELECT * FROM exams WHERE exam_id='$_GET[exam_id]'");
			$db->sql_query("DELETE FROM exam2user WHERE exam2user_exam_id='$_GET[exam_id]' AND exam2user_user_id='$_GET[user_id]'");
			$my_user = new user($_GET['user_id']);
			$my_user->create_star_image();

			//Logging
			$logger->write_to_log('Training Stars','Remove Star for "'.$d->exam_title.'" from "'.$my_user->fullname.'"');
		}

		//************************************************************************************
	}
}
catch (Exception $e)
{
	$myPage = new page();
	$myPage->error_text = $e->getMessage();
	print $myPage->get_html_code();
}

//************************************************************************************
//own PHP Functions
//************************************************************************************


?>
