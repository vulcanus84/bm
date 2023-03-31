<?php
define("level","../");																//define the structur to to root directory (e.g. "../", for files in root set "")
require_once(level."inc/standard_includes.php");		//Load all necessary files (DB-Connection, User-Login, etc.)

try
{
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
		function show_infos(cat,level,user_id)
		{
			$('#info_div_user'+user_id).load('$_SERVER[link]&ajax=get_infos&cat='+cat+'&level='+level+'&user_id='+user_id,
							function()
							{
							});
		}

		function show_comments(user_id)
		{
			$('#info_div_user'+user_id).load('$_SERVER[link]&ajax=get_comments&user_id='+user_id,
							function()
							{
							});
		}

		function back_to_overview(user_id)
		{
			$('#info_div_user'+user_id).load('$_SERVER[link]&ajax=get_exam_completions&user_id='+user_id);
		}

		function new_comment(user_id)
		{
			$('#info_div_user'+user_id).load('$_SERVER[link]&ajax=new_comment&user_id='+user_id);
		}

		function save_comment(user_id,comment)
		{
			var my_url = '$_SERVER[link]&ajax=add_comment&comment='+comment+'&user_id='+user_id;
			$.ajax({ url: my_url }).done(
			function(data)
			{
				show_comments(user_id);
			});
		}

		function delete_comment(user_id,comment_id)
		{
			var my_url = '$_SERVER[link]&ajax=delete_comment&comment_id='+comment_id;
			$.ajax({ url: my_url }).done(
			function(data)
			{
				show_comments(user_id);
			});
		}

		function add_exam(cat,level,user_id)
		{
			var my_url = '$_SERVER[link]&ajax=add_exam&cat='+cat+'&level='+level+'&user_id='+user_id;
			$.ajax({ url: my_url }).done(
			function(data)
			{
				$('#info_div_user'+user_id).load('$_SERVER[link]&ajax=get_exam_completions&user_id='+user_id);
				$('#'+cat+level+user_id).attr('src','../inc/imgs/star_full.png');
			});
		}

		function remove_exam(exam2user_id,cat,level,user_id)
		{
			var my_url = '$_SERVER[link]&ajax=remove_exam&cat='+cat+'&level='+level+'&exam2user_id='+exam2user_id+'&user_id='+user_id;
			$.ajax({ url: my_url }).done(
			function(data)
			{
				$('#info_div_user'+user_id).load('$_SERVER[link]&ajax=get_exam_completions&user_id='+user_id);
				$('#'+cat+level+user_id).attr('src','../inc/imgs/star_empty.png');
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
		if(isset($_GET['location']) && $_GET['location']=='Alle') { $color = '#AAFFAA'; } else { $color = '#AAAAFF'; }
		$myPage->add_content("<a href='index.php?location=Alle'><span style='border-radius:1vw;background-color:$color;padding:1vw;'>Alle</span></a>");
		while($d = $db->get_next_res())
		{
			if(isset($_GET['location']) && $_GET['location']==$d->location_id) { $color = '#AAFFAA'; } else { $color = '#AAAAFF'; }
			$myPage->add_content("<a href='index.php?location=".$d->location_id."'><span style='border-radius:1vw;background-color:$color;padding:1vw;'>".$d->location_name."</span></a>");
		}
		if(isset($_GET['location']) && $_GET['location']=='Aufgaben') { $color = '#AAFFAA'; } else { $color = '#FFAAAA'; }
		$myPage->add_content("<a href='index.php?show_exercises=1'><span style='border-radius:1vw;background-color:$color;padding:1vw;'>Aufgaben</span></a>");

		$myPage->add_content("</div>");
		if(isset($_GET['location']))
		{
			$myPage->add_content("<div style='float:right;margin-top:2vw;margin-bottom:2vw;' id='sort_div'>");
			if(isset($_GET['view']) && $_GET['view']=='Tabelle') { $desc = 'Details'; } else { $desc = 'Tabelle'; }
			$myPage->add_content("<a href='index.php?location=$_GET[location]&view=$desc'><span style='border-radius:1vw;background-color:#DDD;color:black;padding:1vw;'>$desc</span></a>");
			$myPage->add_content("</div>");
		}
		$myPage->add_content("<div id='main_div' style='clear:both;'>");
		$myPage->add_content("<hr/>");

		if(isset($_GET['show_exercises']))
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
			if(isset($_GET['location']))
			{
				if($_GET['location']=='Alle')
				{
					//Check permissions
					$w_str = "user_id>1 AND user_hide<1 ";
					$db->sql_query("SELECT * FROM location_permissions
																LEFT JOIN locations ON loc_permission_loc_id = location_id
																WHERE loc_permission_user_id='".$_SESSION['login_user']->id."'");
					if($db->count()==0) { $w_str.= " AND user_training_location=0"; } else { $w_str.= " AND ("; }
					$i=0;
					while($d = $db->get_next_res())
					{
						if($i==0) { $w_str.= "user_training_location='$d->location_id'"; } else { $w_str.= " OR user_training_location='$d->location_id'"; }
						$i++;
					}
					if($db->count()>0) { $w_str.= ")"; }
				}
				else
				{
					$w_str = "user_training_location = '$_GET[location]' AND user_hide<1";
				}
			} else { $w_str = "user_id<1";}

			if(isset($_GET['view']) && $_GET['view']=='Tabelle')
			{
				$myPage->add_content("<table style='width:100%;border-spacing:1px;'>");
				$myPage->add_content("<tr><td></td>");
				$x="";
				$db->sql_query("SELECT MAX(exam_category) as cat, count(*) as anz FROM exams GROUP BY exam_category ORDER BY MAX(exam_category) ASC");
				while($d = $db->get_next_res())
				{
					$myPage->add_content("<td colspan='$d->anz' style='font-size:14pt;text-align:center;border:1px solid black;border-top-left-radius:10px;border-top-right-radius:10px;padding:1vh;background-color:#DDEEDD'>$d->cat</td>");
				}
				$myPage->add_content("</tr><tr><td></td>");

				$db->sql_query("SELECT * FROM exams ORDER BY exam_category, exam_level ASC");
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
				while($d = $db->get_next_res())
				{
					$myPage->add_content("<tr>");
					$myPage->add_content("<td style='border:1px solid black;border-top-left-radius:10px;border-bottom-left-radius:10px;padding-left:5px;background-color:#DDDDEE;'>$d->user_account</td>");
					$db2->sql_query("SELECT * FROM exams ORDER BY exam_category, exam_level ASC");
					while($d2 = $db2->get_next_res())
					{
						$db3->sql_query("SELECT * FROM exam2user WHERE exam2user_exam_id='$d2->exam_id' AND exam2user_user_id='$d->user_id'");
						if($db3->count()>0) { $img = 'star_full.png'; } else { $img = 'star_empty.png'; }
						$myPage->add_content("					<td style='border:1px solid black;text-align:center;'><img src='".level."inc/imgs/$img' style='height:20px;'/></td>");
					}
					$myPage->add_content("</tr>");
				}
				$myPage->add_content("</tr></table>");
			}
			else
			{
				$db->sql_query("SELECT * FROM users
												LEFT JOIN  (SELECT COUNT(*) as anz, MAX(exam2user_user_id) as exam_user_id FROM exam2user GROUP BY exam2user_user_id) as exams ON users.user_id = exams.exam_user_id
												WHERE $w_str
												ORDER BY exams.anz DESC, user_account ASC");
				while($d = $db->get_next_res())
				{
					$myPage->add_content(get_user_info(clone($db),$d->user_id));
				}
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
		if($_GET['ajax']=='get_infos')
		{
			$d = $db->sql_query_with_fetch("SELECT * FROM exams WHERE exam_category='$_GET[cat]' AND exam_level='$_GET[level]'");
			print "<h2 style='margin-top:0px;'>".$d->exam_category." / Aufgabe ".$d->exam_level."</h2>";
			print "<span style='margin-top:0px;font-weight:bold;'>".$d->exam_title."</span><br>";
			print nl2br($d->exam_description);
			print "<hr/>";
			$db->sql_query("SELECT * FROM exam2user WHERE exam2user_exam_id='$d->exam_id' AND exam2user_user_id='$_GET[user_id]'");
			if($db->count()>0)
			{
				$d2 = $db->get_next_res();
				print "<img style='cursor:pointer;' src='".level."inc/imgs/star_empty.png' onclick=\"remove_exam('$d2->exam2user_id','$_GET[cat]','$_GET[level]','$_GET[user_id]');\"/>";
				print "<img style='cursor:pointer;border:2px solid red;' src='".level."inc/imgs/star_full.png' onclick=\"back_to_overview('$_GET[user_id]');\"/>";
			}
			else
			{
				print "<img style='cursor:pointer;border:2px solid red;' src='".level."inc/imgs/star_empty.png' onclick=\"back_to_overview('$_GET[user_id]');\"/>";
				print "<img style='cursor:pointer;' src='".level."inc/imgs/star_full.png' onclick=\"add_exam('$_GET[cat]','$_GET[level]','$_GET[user_id]');\"/>";
			}
		}

		if($_GET['ajax']=='get_comments')
		{
			$db->sql_query("SELECT *,DATE_FORMAT(comment_date,'%d.%m.%Y') as comment_date_c FROM comments WHERE comment_user_id='$_GET[user_id]' ORDER BY comment_date DESC");
			while($d = $db->get_next_res())
			{
				print "<table><tr><td><img src='../inc/imgs/query/delete_big.png' onclick=\"delete_comment('".$_GET['user_id']."','".$d->comment_id."');\"></td><td>".$d->comment_date_c."<br/>".nl2br($d->comment_text)."</td></tr></table><hr/>";
			}
			if($db->count()==0)
			{
				print "Keine Kommentare";
			}
			print "<p><span style='border-radius:2vw;background-color:#88FF88;padding:1vw;' onclick='new_comment($_GET[user_id]);'>Neuer Kommentar</span>";
		}
		if($_GET['ajax']=='new_comment')
		{
			print "<textarea style='width:90%;' id='new_comment_".$_GET['user_id']."'></textarea>";
			print "<p><span style='border-radius:2vw;background-color:#88FF88;padding:1vw;' onclick=\"save_comment($_GET[user_id],$('#new_comment_".$_GET['user_id']."').val());\">Speichern</span>";
		}
		if($_GET['ajax']=='add_comment')
		{
			$db->insert(array('comment_text'=>$_GET['comment'],'comment_user_id'=>$_GET['user_id'],'comment_created_by'=>$_SESSION['login_user']->id),'comments');
		}

		if($_GET['ajax']=='delete_comment')
		{
			$db->delete('comments','comment_id',$_GET['comment_id']);
		}


		if($_GET['ajax']=='add_exam')
		{
			$d = $db->sql_query_with_fetch("SELECT * FROM exams WHERE exam_category='$_GET[cat]' AND exam_level='$_GET[level]'");
			$db->insert(array('exam2user_exam_id'=>$d->exam_id,'exam2user_user_id'=>$_GET['user_id']),'exam2user');
			$my_user = new user($_GET['user_id']);
			$my_user->create_star_image();

			//Logging
			$logger->write_to_log('Training Stars','Add Star for "'.$d->exam_title.'" to "'.$my_user->fullname.'"');
		}

		if($_GET['ajax']=='remove_exam')
		{
			$db->delete('exam2user','exam2user_id',$_GET['exam2user_id']);
			$my_user = new user($_GET['user_id']);
			$my_user->create_star_image();

			//Logging
			$d = $db->sql_query_with_fetch("SELECT * FROM exams WHERE exam_category='$_GET[cat]' AND exam_level='$_GET[level]'");
			$logger->write_to_log('Training Stars','Remove Star for "'.$d->exam_title.'" from "'.$my_user->fullname.'"');
		}

		if($_GET['ajax']=='get_exam_completions')
		{
			print get_exam_completions($db,$_GET['user_id']);
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
function get_user_info($db,$user_id)
{
	$db2 = clone($db);
	$db3 = clone($db);
	$my_user = new user($user_id);
	$x = "<table border='0' style='width:100%;'>";
	$x.= "	<tr>";
	$x.= "		<td style='width:20%;text-align:center;border-right:3px solid gray;padding-right:1vw;'>";
	$x.= "			<img src='".$my_user->get_pic_path()."' style='width:100%;' onclick='back_to_overview($user_id);'/>";
	if($my_user->firstname == '' OR $my_user->lastname == '') { $name = $my_user->account; } else { $name =  $my_user->firstname." ".$my_user->lastname; }
	$x.= "			<h2>".$name."</h2>".$my_user->birthday."<br><img src='".level."/inc/imgs/info.png' style='width:5vw;' onclick='show_comments($user_id);'/>";
	$x.= "		</td>";
	$x.= "		<td style='width:40%;border-right:3px solid gray;padding-right:1vw;'>";
	$x.= "			<table border='0' style='margin-left:1vw;width:100%;'>";
	$x.= "				<tr>";
	$x.= "					<td></td>";
	$db->sql_query("SELECT MAX(exam_level) as level FROM exams GROUP BY exam_level ORDER BY MAX(exam_level) DESC");
	$d = $db->get_next_res();
	$i=1;
	for($i;$i<=$d->level;$i++)
	{
		$x.= "					<td style='text-align:center;'>$i</td>";
	}
	$x.= "				</tr>";
	$db->sql_query("SELECT MAX(exam_category) as cat FROM exams GROUP BY exam_category ORDER BY MAX(exam_category) ASC");
	while($d = $db->get_next_res())
	{
		$x.= "				<tr>";
		$x.= "					<td>$d->cat</td>";
		$db2->sql_query("SELECT * FROM exams WHERE exam_category='$d->cat' ORDER BY exam_level ASC");
		while($d2 = $db2->get_next_res())
		{
			$db3->sql_query("SELECT * FROM exam2user WHERE exam2user_exam_id='$d2->exam_id' AND exam2user_user_id='$user_id'");
			if($db3->count()>0) { $img = 'star_full.png'; } else { $img = 'star_empty.png'; }
			$x.= "					<td><img id='".$d2->exam_category.$d2->exam_level.$user_id."' src='".level."inc/imgs/$img' class='star' onclick=\"show_infos('".$d2->exam_category."','".$d2->exam_level."',$user_id);\" /></td>";
		}
		$x.= "				</tr>";
	}
	$x.= "			</table>";
	$x.= "		<td style='width:40%;vertical-align:top;'>";
	$x.= "			<div id='info_div_user".$user_id."' style='width:100%;padding:0.5vw;'>";
	$x.= get_exam_completions($db,$user_id);
	$x.= "			</div>";
	$x.= "		<td>";
	$x.= "		</td>";
	$x.= "	<tr>";
	$x.= "</table><hr/>";

	return $x;
}

function get_exam_completions($db,$user_id)
{
	$x = "";
	$db->sql_query("SELECT *, DATE_FORMAT(exam2user_created_on,'%d.%m.%Y') as completed FROM exam2user
									LEFT JOIN exams ON exam2user_exam_id=exam_id
									WHERE exam2user_user_id='$user_id'
									ORDER BY exam2user_created_on DESC");
	while($d = $db->get_next_res())
	{
		//$x.= $d->exam_category." / Level ".$d->exam_level."<br/>".$d->exam_title." am ".$d->completed." bestanden<hr/>";
		$x.= $d->exam_title." / ".$d->completed."<br/>";
	}
	return $x;
}
?>
