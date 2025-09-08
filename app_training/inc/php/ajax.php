<?php

switch($_GET['ajax'])
{
  case 'show_user_info':
    if(isset($_GET['user_id']))
    {
      $my_user = new user($_GET['user_id']);
      $last_date = "";
      $db->sql_query("SELECT *, DATE_FORMAT(exam2user_created_on,'%d.%m.%Y') as datum FROM exam2user 
              LEFT JOIN exams ON exam2user_exam_id = exams.exam_id
              LEFT JOIN users ON exam2user_created_by = users.user_id
              WHERE exam2user_user_id = '$_GET[user_id]'
              ORDER BY exam2user_created_on DESC");
      $x = "<h1 style='margin-bottom:0px;'>".$my_user->fullname."</h1>";
      if($db->count() != 1) { $x.= "<span style='font-style:italic;'>(".$db->count()." Sterne)</span>"; } else { $x.= "<span style='font-style:italic;'>(".$db->count()." Stern)</span>"; }
      $x.= "<hr/><table style='width:100%;'>";
      while($d = $db->get_next_res())
      {
        if($last_date != $d->datum)
        {
          $x.="<tr><td colspan='3' style='font-size:14pt;font-weight:bold;padding-top:10px;'>".$d->datum."</td></tr>";
          $last_date = $d->datum;
        }
        $x.= "<tr><td style='font-size:12pt;font-weight:light;'>".$d->exam_category." > ".$d->exam_title."</td><td style='font-size:10pt;font-style:italic;'>zugeteilt von ".$d->user_account."</td></tr>";
      }
      print $x;

    } else { print "No user id"; }        
    break;
  case 'show_filter':
    $my_star_filter = array();
    if(isset($_SESSION['star_filter']))
    {
      $my_star_filter = explode(',',$_SESSION['star_filter']);
    }

    $x = "";
    $x.= "<form method='POST' action='?location=$_GET[location]&action=filter'>";
    $x.= "<div style='display:flex;flex-wrap:wrap;gap:0.5rem;'>";

    $db->sql_query("SELECT MAX(exam_category) as cat, count(*) as anz, MAX(exam_id) as exam_id 
                    FROM exams 
                    GROUP BY exam_category 
                    ORDER BY MAX(exam_category) ASC");

    while($d = $db->get_next_res())
    {
      $x.= "<div style='flex:0 0 calc(33.333% - 0.5rem);display:flex;align-items:center;gap:0.5rem;'>";
      $x.= "<input name='".$d->cat."' type='checkbox' ";
      if(in_array($d->cat,$my_star_filter)) { $x.= "checked='1' "; }
      $x.= "style='width:20px;'/>";
      $x.= "<span style='font-size:16pt;'>$d->cat</span>";
      $x.= "</div>";
    }

    $x.= "<div style='flex:0 0 100%;margin-top:1rem;'>
            <button type='button' id='abort_button' class='gray'/>Abbrechen</button>
            <button class='green' type='submit'/>Filtern</button>
          </div>";

    $x.= "</div>";
    $x.= "</form>";

    print $x;

    break;

  case 'get_text_add_exam':
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

    break;

  case 'add_exam':
    $d = $db->sql_query_with_fetch("SELECT * FROM exams WHERE exam_id='$_GET[exam_id]'");
    $db->insert(array('exam2user_exam_id'=>$_GET['exam_id'],'exam2user_user_id'=>$_GET['user_id'],'exam2user_created_by'=>$_SESSION['login_user']->id),'exam2user');
    $my_user = new user($_GET['user_id']);
    $my_user->create_star_image();

    //Logging
    $logger->write_to_log('Training Stars','Add Star for "'.$d->exam_title.'" to "'.$my_user->fullname.'"');
    break;  

  case 'remove_exam':
			$d = $db->sql_query_with_fetch("SELECT * FROM exams WHERE exam_id='$_GET[exam_id]'");
			$db->sql_query("DELETE FROM exam2user WHERE exam2user_exam_id='$_GET[exam_id]' AND exam2user_user_id='$_GET[user_id]'");
			$my_user = new user($_GET['user_id']);
			$my_user->create_star_image();

			//Logging
			$logger->write_to_log('Training Stars','Remove Star for "'.$d->exam_title.'" from "'.$my_user->fullname.'"');
    break;

}