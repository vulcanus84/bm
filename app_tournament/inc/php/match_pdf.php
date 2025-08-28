<?php 
namespace Tournament;

define("level","../../../");																//define the structur to to root directory (e.g. "../", for files in root set "")
require_once(level."inc/standard_includes.php");						//Load all necessary files (DB-Connection, User-Login, etc.)
require_once(level."inc/php/tcpdf-master/tcpdf_import.php");
require_once("class_tournament.php");												//Load the tournament class
if(!isset($_SESSION['login_user'])) { header("Location: ../../../index.php"); }

if(isset($_GET['tournament_id'])) { $myTournament = new tournament($_GET['tournament_id']); }
else
{
	die('Please send tournament ID by GET variable');
}

// Extend the TCPDF class to create custom Header and Footer
class MYPDF extends \TCPDF {

    //Page header
    public function Header() {
        // Logo
         $image_file = '../inc/imgs/bcz_logo.jpg';
         $this->Image($image_file, 10, 5, 15, '', 'JPG', '', 'T', false, 300, '', false, false, 0, false, false, false);
         // Set font
         $this->SetFont('helvetica', 'B', 20);
         // Title
//         $this->Cell(0, 15, '<< TCPDF Example 003 >>', 0, false, 'C', 0, '', 0, false, 'M', 'M');
    }

    // Page footer
    public function Footer() {
         // Position at 15 mm from bottom
         $this->SetY(-15);
         // Set font
         $this->SetFont('helvetica', 'I', 8);
         // Page number
         $txt = 'Seite '.$this->PageNo().' von '.$this->getAliasNbPages();
         $this->Cell(0, 10, $txt, 0, false, 'C', 0, '', 0, false, 'T', 'M');
    }
}

// create new PDF document
if($format_matchletters=='A5')
{
	$pdf = new MYPDF('landscape', 'mm', 'A5', true, 'UTF-8', true);
}
else
{
	$pdf = new MYPDF('portrait', 'mm', 'A4', true, 'UTF-8', true);
}
$pdf->AddPage();


$db2 = clone($db);
if(isset($_GET['round'])) 
{
	$db->sql_query("SELECT *, DATE_FORMAT(group_created,'%d.%m.%Y') as c_date FROM games  
									LEFT JOIN groups ON game_group_id = group_id 
									LEFT JOIN courts ON court_game_id = game_id
									WHERE game_group_id='".$myTournament->id."' AND game_round='".$_GET['round']."' AND game_player1_id>1 AND game_player2_id>1");
}
else
{
	$db->sql_query("SELECT *, DATE_FORMAT(group_created,'%d.%m.%Y') as c_date FROM games  
									LEFT JOIN groups ON game_group_id = group_id 
									LEFT JOIN courts ON court_game_id = game_id
									WHERE game_group_id='".$myTournament->id."' AND game_player1_id>1 AND game_player2_id>1");
}

if(isset($_GET['game_id']))
{
	$db->sql_query("SELECT *, DATE_FORMAT(group_created,'%d.%m.%Y') as c_date FROM games  
									LEFT JOIN groups ON game_group_id = group_id 
									LEFT JOIN courts ON court_game_id = game_id
									WHERE game_id='".$_GET['game_id']."' AND game_player1_id>1 AND game_player2_id>1");
}

$i = 1;
$y_pic = 0;
while($d = $db->get_next_res())
{
	$p1 = new \user($d->game_player1_id);
	$p2 = new \user($d->game_player2_id);
	
	$p1_name = $p1->firstname.' '.$p1->lastname;
	$p2_name = $p2->firstname.' '.$p2->lastname;
	
	if(trim($p1_name)=='') { $p1_name = $p1->login; }
	if(trim($p2_name)=='') { $p2_name = $p2->login; }
	
	$txt = "<table>";
	
	$txt.= "<tr>";
	$txt.= "<td style=\"text-align:center;font-size:14pt;\"><div style=\"font-size:4pt\">&nbsp;</div>Runde ".$d->game_round."</td>";
	$txt.= "<td style=\"text-align:center;font-size:20pt;font-weight:bold;\">".$myTournament->title."</td>";
	if(isset($d->court_no))
	{
		$txt.= "<td style=\"text-align:center;font-size:14pt;padding-top:2px;\"><div style=\"font-size:4pt\">&nbsp;</div>Feld $d->court_no</td>";
	}
	else
	{
		$txt.= "<td style=\"text-align:center;font-size:14pt;padding-top:2px;\"><div style=\"font-size:4pt\">&nbsp;</div>Feld&nbsp;&nbsp;&nbsp;&nbsp;</td>";
	}
	$txt.= "</tr>";
	
	$txt.= "<tr><td colspan=\"3\" style=\"text-align:center;font-size:8pt;\"></td></tr>";
	$txt.= "<tr><td colspan=\"3\" style=\"text-align:center;font-size:8pt;\"><hr/></td></tr>";
	$txt.= "<tr><td style=\"text-align:center;font-size:16pt;font-weight:bold;\">".$p1_name."</td><td></td><td style=\"text-align:center;font-size:16pt;font-weight:bold;\">".$p2_name."</td></tr>";
	$txt.= "<tr><td colspan=\"3\" style=\"text-align:center;font-size:12pt;\"></td></tr>";

	if($myTournament->counting=='official2sets' OR $myTournament->counting=='2sets11points' OR $myTournament->counting=='2setswinning')
	{
		$txt.= "<tr><td style=\"border:1px solid black;font-size:36pt;\"></td><td style=\"text-align:center;font-size:36pt;\">:</td><td style=\"border:1px solid black;\"></td></tr>";
		$txt.= "<tr><td colspan=\"3\" style=\"text-align:center;font-size:20pt;\"></td></tr>";
		$txt.= "<tr><td style=\"border:1px solid black;font-size:36pt;\"></td><td style=\"text-align:center;font-size:36pt;\">:</td><td style=\"border:1px solid black;\"></td></tr>";
		$txt.= "<tr><td colspan=\"3\" style=\"text-align:center;font-size:20pt;\"></td></tr>";
		$txt.= "<tr><td style=\"border:1px solid black;font-size:36pt;\"></td><td style=\"text-align:center;font-size:36pt;\">:</td><td style=\"border:1px solid black;\"></td></tr>";
		$txt.= "<tr><td colspan=\"3\" style=\"text-align:center;font-size:20pt;\"></td></tr>";
		$txt.= "<tr><td colspan=\"3\" style=\"text-align:center;font-size:14pt;\">Punkte in die Felder eintragen und Gewinner/in einkreisen</td></tr>";
	}

	if($myTournament->counting=='pointsOneSet')
	{
		$txt.= "<tr><td style=\"border:1px solid black;font-size:100pt;\"></td><td style=\"text-align:center;font-size:80pt;\">:</td><td style=\"border:1px solid black;\"></td></tr>";
		$txt.= "<tr><td colspan=\"3\" style=\"text-align:center;font-size:20pt;\"></td></tr>";
		$txt.= "<tr><td colspan=\"3\" style=\"text-align:center;font-size:14pt;\">Punkte in die Felder eintragen und Gewinner/in einkreisen</td></tr>";
	}

	if($myTournament->counting=='win')
	{
		$txt.= "<tr><td style=\"border:1px solid black;font-size:100pt;\"></td><td style=\"text-align:center;font-size:80pt;\">:</td><td style=\"border:1px solid black;\"></td></tr>";
		$txt.= "<tr><td colspan=\"3\" style=\"text-align:center;font-size:20pt;\"></td></tr>";
		$txt.= "<tr><td colspan=\"3\" style=\"text-align:center;font-size:14pt;\">Gewinner/in bitte ankreuzen</td></tr>";
	}

	$txt.= "</table>";
	
  $pdf->writeHTML($txt);
	if($format_matchletters=='A5')
	{
	  if($i<$db->count()) { $pdf->AddPage(); }
	}
	else
	{
	  if($i % 2 == 0 AND $i<$db->count()) { $pdf->AddPage(); } else { $pdf->setXY(10,155); }
	}
  $i++;
}


// reset pointer to the last page
$pdf->lastPage();
//Close and output PDF document
$pdf->Output('matches.pdf', 'I');

?>