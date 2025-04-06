<?php
//************************************************************************************
//CRUD Operations
//************************************************************************************
if($_GET['ajax']=='add_entry')
{
  $db->insert(array('journal_created_by'=>$_SESSION['login_user']->id),'journal');
}

if($_GET['ajax']=='confirm_delete')
{
  print "<h1 class='delete_confirmation'>Wirklich löschen?</h1>";
  print "<button class='delete' id='delete_".$_GET['journal_id']."'>Ja</button>";
  print "<button class='abort_delete'id='abort_delete_".$_GET['journal_id']."'>Nein</button>";
}

if($_GET['ajax']=='delete_entry')
{
  $db->delete('journal','journal_id',$_GET['journal_id']);
}

if($_GET['ajax']=='save_text')
{
  $db->update(array('journal_text'=>$_GET['text']),'journal','journal_id',$_GET['journal_id']);
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

//************************************************************************************
//Views
//************************************************************************************
if($_GET['ajax']=='show_text')
{
  $d = $db->sql_query_with_fetch("SELECT * FROM journal WHERE journal_id='".$_GET['journal_id']."'");
  print "<textarea id='training_description'>".$d->journal_text."</textarea>";
  print "<br/><button class='save_text' id='save_text_".$d->journal_id."'>Speichern</button>";
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
      print "<h1 class='show_players'>".$d->location_name."</h1><hr/>";
      $last_group = $d->location_name;
    }
    if($d->journal2user_user_id!='')
    {
      $player = new user($d->journal2user_user_id);
      print "<div class='activated' id='img_".$d->user_id."' ><img src='".$player->get_pic_path(true)."'/><br/>".$player->login."</div>";
    }
    else
    {
      $player = new user($d->user_id);
      print "<div class='deactivated' id='img_".$d->user_id."'><img src='".$player->get_pic_path(true)."'/><br/>".$player->login."</div>";
    }
  }
  print "<hr class='end_line' /><p/><button class='save_players' id='save_players_".$_GET['journal_id']."'>Speichern</button>";
}


if($_GET['ajax']=='show_trainer')
{
    try {
        $d = $db->sql_query_with_fetch("SELECT *, DATE_FORMAT(journal_created_on,'%Y-%m-%d')  as curr_date FROM journal WHERE journal_id='$_GET[journal_id]'");
    } catch (Exception $e) {

    }
    print "<h1>Datum</h1>";
  print "<input id='journal_date' type='date' value='".$d->curr_date."'/>";
  print "<hr/>";
  print "<h1>Trainer</h1>";
    try {
        $db->sql_query("SELECT * FROM users 
                        LEFT JOIN (SELECT * FROM journal WHERE journal_id='$_GET[journal_id]') as journal_temp ON journal_temp.journal_created_by = users.user_id
                        LEFT JOIN location2user ON location2user.location2user_user_id = users.user_id 
                        LEFT JOIN locations ON location2user.location2user_location_id = locations.location_id 
                        WHERE user_hide!='1' AND user_id>1 AND locations.location_name = '_Trainer'
                        ORDER BY locations.location_name, user_account
        ");
    } catch (Exception $e) {

    }
    while($d = $db->get_next_res())
  {
    if($d->journal_created_by!='')
    {
      $player = new user($d->journal_created_by);
      print "<div class='activated' id='img_".$d->user_id."'><img src='".$player->get_pic_path(true)."'/><br/>".$player->login."</div>";
    }
    else
    {
      $player = new user($d->user_id);
      print "<div class='deactivated' id='img_".$d->user_id."'><img src='".$player->get_pic_path(true)."'/><br/>".$player->login."</div>";
    }
  }
  print "<hr class='end_line'/><p/><button class='save_trainer' id='save_trainer_".$_GET['journal_id']."'>Speichern</button>";
}
//************************************************************************************
