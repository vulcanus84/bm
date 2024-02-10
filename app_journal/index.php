<?php
define("level","../");																//define the structur to to root directory (e.g. "../", for files in root set "")
require_once(level."inc/standard_includes.php");		//Load all necessary files (DB-Connection, User-Login, etc.)
try
{
	
	$myPage = new page();
	$page->change_parameter('x','1');
	$_SERVER['link'] = $page->get_link();
	$myPage->add_js("

		function edit_text(id)
		{
			var my_url = '$_SERVER[link]&ajax=show_text&journal_id=' + id
			$.ajax({ url: my_url }).done(
				function(data)
				{
					$('#myModalText').html(data); 
					$('#myModal').show();
				});
		}
		function save_text(id)
		{
			var encoded_text = encodeURIComponent($('#textarea').val());
			var my_url = '$_SERVER[link]&ajax=save_text&journal_id=' + id + '&text=' + encoded_text
			$.ajax({ url: my_url }).done(
				function(data)
				{
					location.reload();
				});
		}

		function edit_players(id)
		{
			var my_url = '$_SERVER[link]&ajax=show_players&journal_id=' + id
			$.ajax({ url: my_url }).done(
				function(data)
				{
					$('#myModalText').html(data); 
					$('#myModal').show();
				});
		}
		function save_players(journal_id)
		{
			var players = '';
			$('.activated').each(function() {
					players = players + $(this).attr('id').replace('img_','') + ';';
			});
			var my_url = '$_SERVER[link]&ajax=save_players&players=' + players + '&journal_id=' + journal_id
			$.ajax({ url: my_url }).done(
				function(data)
				{
					location.reload();
				});
		}


		function edit_trainer(id)
		{
			var my_url = '$_SERVER[link]&ajax=show_trainer&journal_id=' + id
			$.ajax({ url: my_url }).done(
				function(data)
				{
					$('#myModalText').html(data); 
					$('#myModal').show();
				});
		}
		function save_trainer(journal_id)
		{
			var trainer = '';
			$('.activated').each(function() {
					trainer_id = $(this).attr('id').replace('img_','') + ';';
			});
			var journal_date = $('#journal_date').val();
			var my_url = '$_SERVER[link]&ajax=save_trainer&trainer_id=' + trainer_id + '&journal_id=' + journal_id + '&journal_date=' + journal_date
			$.ajax({ url: my_url }).done(
				function(data)
				{
					location.reload();
				});
		}

		function add_entry()
		{
			var my_url = '$_SERVER[link]&ajax=add_entry'
			$.ajax({ url: my_url }).done(
				function(data)
				{
					location.reload();
				});
		}

		function delete_entry(id)
		{
			var my_url = '$_SERVER[link]&ajax=delete_entry&journal_id=' + id
			$.ajax({ url: my_url }).done(
				function(data)
				{
					location.reload();
				});
		}

		function confirm_delete(id)
		{
			var my_url = '$_SERVER[link]&ajax=confirm_delete&journal_id=' + id
			$.ajax({ url: my_url }).done(
				function(data)
				{
					$('#myModalText').html(data); 
					$('#myModal').show();
				});
		}

		function toggle_activation(img_id)
		{
			if($('#img_' + img_id).hasClass('activated')) { $('#img_' + img_id).removeClass('activated'); $('#img_' + img_id).addClass('deactivated'); }
			else
			{
				if($('#img_' + img_id).hasClass('deactivated')) { $('#img_' + img_id).removeClass('deactivated'); $('#img_' + img_id).addClass('activated'); }
			}
		}

		function change_activation(img_id)
		{
			$(\"div[id^='img_']\").removeClass('activated');
			$(\"div[id^='img_']\").addClass('deactivated');
			$('#img_' + img_id).removeClass('deactivated');
			$('#img_' + img_id).addClass('activated');
		}


	");


	if(!IS_AJAX)
	{
		//Display page
		$myPage->add_css("
											div { font-size:14pt; }
											div.activated { border:3px solid purple;float:left;font-size:8pt;text-align:center;border-radius:10px; }
											div.deactivated { border:3px solid white;float:left;font-size:8pt;text-align:center;border-radius:10px; }
										
										");
		$db2 = clone($db);
		$last_date = null;

		$myPage->add_content("<!-- The Modal -->");
		$myPage->add_content("<div id='myModal' class='modal'>");
		$myPage->add_content("  <!-- Modal content -->");
		$myPage->add_content("  <div class='modal-content'>");
		$myPage->add_content("    <span onclick=\"$('#myModal').hide();\" class='close'>&times;</span>");
		$myPage->add_content("    <p id='myModalText'>Some text in the Modal..</p>");
		$myPage->add_content("  </div>");
		$myPage->add_content("</div>");

		$db->sql_query("SELECT *, DATE_FORMAT(journal_created_on,'%d.%m.%Y') as curr_date FROM journal ORDER BY journal_created_on DESC");

		if($db->count() < 1) {
			$myPage->add_content("<div style='float:right;' onclick='add_entry();'><img style='height:25px;' src='../inc/imgs/query/add.png'/></div>"); 
		}

		while($d = $db->get_next_res())
		{
			if($d->curr_date!=$last_date)
			{
				$myPage->add_content("<div style='margin-top:5px;float:left;width:30%;text-align:center;background-color:#CCC;border-radius:10px 10px 0px 10px;padding:5px;'>".$d->curr_date."</div>");
				if($last_date===null) { 
					$myPage->add_content("<div style='float:right;' onclick='add_entry();'><img style='height:25px;' src='../inc/imgs/query/add.png'/></div>"); 
				}
				$last_date = $d->curr_date;
			}
			$trainer = new user($d->journal_created_by);
			$myPage->add_content("<div style='width:100%;border:1px solid gray;display:flex;border-radius:10px;'>");
			$myPage->add_content("<div id='trainer_".$d->journal_id."' onclick='edit_trainer(".$d->journal_id.")' style='padding:5px;width:70px;text-align:center;border-right:1px solid gray;'>".$trainer->get_picture(false,'','60px',true)."</div>");
			$myPage->add_content("<div id='players_".$d->journal_id."' onclick='edit_players(".$d->journal_id.")' style='padding:5px;width:30%;border-right:1px solid gray;'>");
			$db2->sql_query("SELECT * FROM journal2user WHERE journal2user_journal_id='".$d->journal_id."'");
			while($d2 = $db2->get_next_res())
			{
				$player = new user($d2->journal2user_user_id);
				$myPage->add_content($player->get_picture(false,'','60px',true));
			}
			$myPage->add_content("</div>");
			$myPage->add_content("<div id='text_".$d->journal_id."' onclick='edit_text(".$d->journal_id.")' style='padding:5px;width:60%;border-right:1px solid gray;'>".nl2br($d->journal_text)."</div>");
			$myPage->add_content("<div onclick='confirm_delete(".$d->journal_id.")' style='padding:5px;width:5%;display:flex;justify-content:center;align-items:center;'><img src='../inc/imgs/query/delete_big.png'/></div>");
			$myPage->add_content("</div>");
		}
		$myPage->add_content("</table>");
		print $myPage->get_html_code();
	}
	else
	{
		//************************************************************************************
		//AJAX Handling
		//************************************************************************************
		if($_GET['ajax']=='show_text')
		{
			$d = $db->sql_query_with_fetch("SELECT * FROM journal WHERE journal_id='".$_GET['journal_id']."'");
			print "<textarea id='textarea' style='width:70vw;height:50vh;'>".$d->journal_text."</textarea>";
			print "<br/><button onclick='save_text(".$d->journal_id.");'>Speichern</button>";
		}
		if($_GET['ajax']=='save_text')
		{
			$db->update(array('journal_text'=>$_GET['text']),'journal','journal_id',$_GET['journal_id']);
		}


		if($_GET['ajax']=='confirm_delete')
		{
			print "<span style='font-size:5vh;'>Wirklich löschen?</span>";
			print "<p/><button style='background-color:red;margin:5px;font-size:5vh;' onclick='delete_entry(".$_GET['journal_id'].");'>Ja</button>";
			print "<button style='font-size:5vh;' onclick=\"$('#myModal').hide();\">Nein</button>";
		}


		if($_GET['ajax']=='add_entry')
		{
			$db->insert(array('journal_created_by'=>$_SESSION['login_user']->id),'journal');
		}

		if($_GET['ajax']=='delete_entry')
		{
			$db->delete('journal','journal_id',$_GET['journal_id']);
		}

		if($_GET['ajax']=='show_players')
		{
			$last_group = null;
			//$db->sql_query("SELECT * FROM journal2user WHERE journal2user_journal_id='".$_GET['journal_id']."'");
			$db->sql_query("SELECT * FROM users 
											LEFT JOIN (SELECT * FROM journal2user WHERE journal2user_journal_id='$_GET[journal_id]') as journal_temp ON journal_temp.journal2user_user_id = users.user_id 
											LEFT JOIN location2user ON location2user.location2user_user_id = users.user_id 
											LEFT JOIN locations ON location2user.location2user_location_id = locations.location_id 
											WHERE user_hide!='1' AND user_id>1 AND (locations.location_name = 'BCZ 1' OR locations.location_name = 'BCZ 2')
											ORDER BY locations.location_name, user_account
			");
			while($d = $db->get_next_res())
			{
				if($last_group!=$d->location_name)
				{
					print "<h1 style='clear:both;padding-top:10px;'>".$d->location_name."</h1><hr/>";
					$last_group = $d->location_name;
				}
				if($d->journal2user_user_id!='')
				{
					$player = new user($d->journal2user_user_id);
					print "<div id='img_".$d->user_id."' class='activated' onclick=\"toggle_activation('".$d->user_id."');\"><img style='width:60px;' src='".$player->get_pic_path(true)."'/><br/>".$player->login."</div>";
				}
				else
				{
					$player = new user($d->user_id);
					print "<div id='img_".$d->user_id."' class='deactivated' onclick=\"toggle_activation('".$d->user_id."');\"><img  style='width:60px;' src='".$player->get_pic_path(true)."'/><br/>".$player->login."</div>";
				}
			}
			print "<hr style='clear:both;'/><p/><button onclick='save_players(".$_GET['journal_id'].");'>Speichern</button>";
			print "<div style='height:100px;'/>";
		}

		if($_GET['ajax']=='save_players')
		{
			//Delete all players for current journal
			$db->delete('journal2user','journal2user_journal_id',$_GET['journal_id']);
			//Add selected players
			$arr_players = explode(';',$_GET['players']);
			foreach($arr_players as $player)
			{
				if($player > 0) { 
					$db->insert(array('journal2user_journal_id'=>$_GET['journal_id'],'journal2user_user_id'=>$player),'journal2user'); 
				}
			}
		}

		if($_GET['ajax']=='show_trainer')
		{
			$d = $db->sql_query_with_fetch("SELECT *, DATE_FORMAT(journal_created_on,'%Y-%m-%d')  as curr_date FROM journal WHERE journal_id='$_GET[journal_id]'");
			print "<h1>Datum</h1>";
			print "<input id='journal_date' type='date' value='".$d->curr_date."'/>";
			print "<hr/>";
			print "<h1>Trainer</h1>";
			$db->sql_query("SELECT * FROM users 
											LEFT JOIN (SELECT * FROM journal WHERE journal_id='$_GET[journal_id]') as journal_temp ON journal_temp.journal_created_by = users.user_id
											LEFT JOIN location2user ON location2user.location2user_user_id = users.user_id 
											LEFT JOIN locations ON location2user.location2user_location_id = locations.location_id 
											WHERE user_hide!='1' AND user_id>1 AND locations.location_name = '_Trainer'
											ORDER BY locations.location_name, user_account
			");
			while($d = $db->get_next_res())
			{
				if($d->journal_created_by!='')
				{
					$player = new user($d->journal_created_by);
					print "<div id='img_".$d->user_id."' class='activated' onclick=\"change_activation('".$d->user_id."');\"><img style='width:60px;' src='".$player->get_pic_path(true)."'/><br/>".$player->login."</div>";
				}
				else
				{
					$player = new user($d->user_id);
					print "<div id='img_".$d->user_id."' class='deactivated' onclick=\"change_activation('".$d->user_id."');\"><img  style='width:60px;' src='".$player->get_pic_path(true)."'/><br/>".$player->login."</div>";
				}
			}
			print "<br/><br/><br/><br/>";
			print "<hr style='clear:both;'/><p/><button onclick='save_trainer(".$_GET['journal_id'].");'>Speichern</button>";
		}

		if($_GET['ajax']=='save_trainer')
		{
			//Add selected players
			$arr_trainer = explode(';',$_GET['trainer_id']);
			foreach($arr_trainer as $trainer)
			{
				if($trainer > 0) { 
					$db->update(array('journal_created_on'=>$_GET['journal_date'],'journal_created_by'=>$trainer),'journal','journal_id',$_GET['journal_id']); 
				}
			}
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
