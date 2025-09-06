<?php
define("level","../");																//define the structur to to root directory (e.g. "../", for files in root set "")
require_once(level."inc/standard_includes.php");		//Load all necessary files (DB-Connection, User-Login, etc.)
require_once(level."app_tournament/inc/php/class_tournament.php");

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
	$myPage->add_js_link('inc/js/index.js');
	$myPage->add_css_link('inc/css/index.css');

	$myTournament = new Tournament\tournament();
	$logger = new log();

	if(!IS_AJAX)
	{
		//Display page
		$myPage->add_content("<!-- The Modal -->");
		$myPage->add_content("<div id='myModal' class='modal'>");
		$myPage->add_content("  <!-- Modal content -->");
		$myPage->add_content("  <div class='modal-content'>");
		$myPage->add_content("    <span onclick=\"$('#myModal').hide();\" class='close'>&times;</span>");
		$myPage->add_content("    <p id='myModalText'>Some text in the Modal..</p>");
		$myPage->add_content("  </div>");
		$myPage->add_content("</div>");
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
		$myPage->add_content("<select id='location_select' onchange=\"window.location = 'index.php?location=' + this.value;\" style='width:95vw;font-size:3vh;color:black;'>");
		$myPage->add_content("<option value=''>-- Auswählen --</option>");
		$myPage->add_content("<option value='Aufgaben'");
		if(isset($_GET['location']) && $_GET['location']== 'Aufgaben') { $myPage->add_content(" selected='1'" ); }
		$myPage->add_content(">Aufgaben</option>");

		$myPage->add_content("<option value='Sternchen pro Tag'");
		if(isset($_GET['location']) && $_GET['location']== 'Sternchen pro Tag') { $myPage->add_content(" selected='1'" ); }
		$myPage->add_content(">Sternchen pro Tag</option>");

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
			if($_GET['location']=='Aufgaben' OR $_GET['location']=='Sternchen pro Tag')
			{
				if($_GET['location']=='Aufgaben')
				{
					$last_topic = "";
					$db->sql_query("SELECT * FROM exams ORDER BY exam_category, exam_level");
					while($d = $db->get_next_res())
					{
						$txt = "";
						if($last_topic!=$d->exam_category) 
						{ 
							if($last_topic!='') { $txt.="</table>"; }
							$txt.= "<h1>".$d->exam_category."</h1><table style='width:100%;'>"; $last_topic = $d->exam_category;
						}
						$txt.= "<tr><td style='width:10vw;border-right:2px solid black;border-bottom:2px solid black;text-align:center;'><h2 style='font-size:24pt;'>".$d->exam_level."</h2></td>";
						$txt.= "<td style='padding-left:2vw;border-bottom:2px solid black;'><span style='margin-top:0px;font-weight:bold;font-size:14pt;'>".$d->exam_title."</span><br>";
						$txt.= "<span style='font-size:12pt;'>".nl2br($d->exam_description)."</span>";
						$txt.= "</td></tr>";
						$myPage->add_content($txt);
					}
				}

				if($_GET['location']=='Sternchen pro Tag')
				{
					$last_date = "";
					$last_location = "";
					$last_name = "";
					$db->sql_query("SELECT trainer.user_account as trainer, locations.location_name, exams.exam_category, exams.exam_title,exams.exam_level, users.user_account, DATE_FORMAT(exam2user_created_on,'%Y.%m.%d') as date_day_sort, DATE_FORMAT(exam2user_created_on,'%d.%m.%Y') as date_day 
										FROM `exam2user`
										LEFT JOIN users ON exam2user_user_id = users.user_id
										LEFT JOIN users as trainer ON exam2user_created_by = trainer.user_id
										LEFT JOIN exams ON exam2user_exam_id = exams.exam_id
										LEFT JOIN location2user ON location2user_user_id = exam2user_user_id
										LEFT JOIN locations On location2user_location_id = locations.location_id
										$w_str
										ORDER BY date_day_sort DESC, locations.location_name, users.user_account,exams.exam_category, exams.exam_level");
					while($d = $db->get_next_res())
					{
						$txt = "";
						if($last_date!=$d->date_day) 
						{ 
							if($last_date!='') { $txt.="</table>"; $last_name = ""; $last_location = ""; }
							$txt.= "<h1>".$d->date_day."</h1>"; 
							$last_date = $d->date_day;
						}
						if($last_location!=$d->location_name)
						{
							if($last_location!='') { $txt.="</table>"; $last_name = ""; }
							$txt.= "<table cellspacing='0' style='width:100%;border:3px solid gray;border-radius:10px;margin-top:5px;'>
										<tr><td colspan='2' style='padding-left:5px;'><h2 style='font-size:20pt;'>".$d->location_name."</h2></td></tr>";
							$last_location = $d->location_name;
						}
						if($last_name!=$d->user_account)
						{
							if($last_name!='') { $txt.="</tr></td><tr><td colspan='2'><hr/></tr></td>"; }
							$txt.= "<td style='width:30vw;padding-left:5px;vertical-align:top;'>
										<span style='font-size:14pt;font-weight:bold;'>".$d->user_account."</span>
									</td>
									<td>";
							$last_name = $d->user_account;
						}
						$txt.= "<span style='margin-top:0px;font-size:12pt;'>".$d->exam_category." > ".$d->exam_title." <i style='font-size:8pt;'>(zugeteilt von ".$d->trainer.")</i></span><br/>";
						$myPage->add_content($txt);
					}
					$myPage->add_content("</table>");
				}
			}
			else
			{
				$w_str = "location2user_location_id = '$_GET[location]' AND user_hide<1";

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
					$myPage->add_content("<td onclick=\"show_filter();\" colspan='$d->anz' style='cursor:pointer;font-size:14pt;text-align:center;border:1px solid black;border-top-left-radius:10px;border-top-right-radius:10px;padding:1vh;background-color:#DDEEDD'>$d->cat</td>");
				}
				$myPage->add_content("</tr><tr><td></td>");

				$db->sql_query("SELECT * FROM exams $cat_w_str ORDER BY exam_category, exam_level ASC");
				while($d = $db->get_next_res())
				{
					$myPage->add_content("<td style='font-size:9pt;text-align:center;border:1px solid black;vertical-align:center;background-color:#EEDDDD;'><p style='writing-mode: vertical-lr;text-orientation:mixed;white-space:nowrap;margin:auto;padding:1vh;'>$d->exam_title</p></td>");
				}
				$myPage->add_content("</tr>");

				$db->sql_query("SELECT * FROM location2user 
										LEFT JOIN users on location2user_user_id = users.user_id
												WHERE $w_str
												ORDER BY user_account ASC");
				$db2 = clone($db);
				$db3 = clone($db);
				$star_count=0;
				while($d = $db->get_next_res())
				{
					$myPage->add_content("<tr>");
					$myPage->add_content("<td onclick=\"show_user_info('$d->user_id');\" style='cursor:pointer;border:1px solid black;border-top-left-radius:10px;border-bottom-left-radius:10px;padding-left:5px;background-color:#DDDDEE;font-size:3vh;'>$d->user_account</td>");
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
