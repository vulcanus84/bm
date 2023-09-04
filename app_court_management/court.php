<?php
//header("Content-type: image/png");
define("level","../");                              //define the structur to to root directory (e.g. "../", for files in root set "")
require_once(level."inc/standard_includes.php");    //Load all necessary files (DB-Connection, User-Login, etc.)

$im    = imagecreatefrompng("spielfeld.png");
$background = imagecolorallocate($im , 255, 255, 255);
imagecolortransparent($im, $background);

$girl = imagecolorallocate($im, 255, 0, 255);
$boy = imagecolorallocate($im, 0, 0, 255);
$black = imagecolorallocate($im, 255, 255, 255);
$font = level."inc/CSM.ttf";
$font_size = 24;
$color = null;

if(isset($_GET['action']) && $_GET['action']=='fill')
{
	if(isset($_GET['game_id']))
	{
		$game_data = $db->sql_query_with_fetch("SELECT * FROM games WHERE game_id='$_GET[game_id]'");
	}
	$c_user1 = new user($game_data->game_player1_id);    
	$pic_path = $c_user1->get_pic_path();
	$user1 = imagecreatefrompng($pic_path);
	$user1_width = imagesx($user1);
	$user1_height = imagesy($user1);

	$c_user2 = new user($game_data->game_player2_id);    
	$pic_path = $c_user2->get_pic_path();
	$user2 = imagecreatefrompng($pic_path);
	$user2_width = imagesx($user2);
	$user2_height = imagesy($user2);
	
	$f1 = 150 / $user1_height;
	$f2 = 150 / $user2_height;
	
	imagecopyresized($im, $user1, 72, 0, 0, 0, $user1_width*$f1, $user1_height*$f1, $user1_width, $user1_height);
	imagecopyresized($im, $user2, 350, 40, 0, 0, $user2_width*$f2, $user2_height*$f2, $user2_width, $user2_height);

	$text = $c_user1->login;
	if($c_user1->gender=='Herr') { $color = $boy; } else { $color = $girl; } 
	list($left, $bottom, $right, , , $top) = imageftbbox($font_size, 0, $font, $text);
	// Determine offset of text
  $left_offset = ($right - $left) / 2;
	// Generate coordinates
  $x = 145 - $left_offset;
	imagettftext($im, $font_size, 0, $x, 170, $color, $font, $text);

	$text = $c_user2->login;
	if($c_user2->gender=='Herr') { $color = $boy; } else { $color = $girl; } 
	list($left, $bottom, $right, , , $top) = imageftbbox($font_size, 0, $font, $text);
	// Determine offset of text
  $left_offset = ($right - $left) / 2;
	// Generate coordinates
  $x = 425 - $left_offset;
	imagettftext($im, $font_size, 0, $x, 210, $color, $font, $text);

	$img_crown = imagecreatefrompng(level.'inc/imgs/crown.png');
	$img_crown_width = imagesx($img_crown);
	$img_crown_height = imagesy($img_crown);
	if($game_data->game_winner_id==$c_user1->id)
	{
		imagecopyresized($im, $img_crown, 65, -5, 0, 0, $img_crown_width/3, $img_crown_height/3, $img_crown_width, $img_crown_height);
	}
	if($game_data->game_winner_id==$c_user2->id)
	{
		imagecopyresized($im, $img_crown, 344, 44, 0, 0, $img_crown_width/3, $img_crown_height/3, $img_crown_width, $img_crown_height);
	}
	
	if($game_data->game_set1_p1>0 OR $game_data->game_set1_p2>0)
	{
		if($game_data->game_set2_p1>0 OR $game_data->game_set2_p2>0)
		{
			if($game_data->game_set3_p1>0 OR $game_data->game_set3_p2>0)
			{
				$font_size = 30;
				$text = $game_data->game_set1_p1.':'.$game_data->game_set1_p2;
				list($left, $bottom, $right, , , $top) = imageftbbox($font_size, 0, $font, $text);
				// Determine offset of text
			  $left_offset = ($right - $left) / 2;
			  $x = 280 - $left_offset;
				imagettftext($im, $font_size, 0, $x, 120, $black, $font, $text);

				$text = $game_data->game_set2_p1.':'.$game_data->game_set2_p2;
				list($left, $bottom, $right, , , $top) = imageftbbox($font_size, 0, $font, $text);
				// Determine offset of text
			  $left_offset = ($right - $left) / 2;
			  $x = 280 - $left_offset;
				imagettftext($im, $font_size, 0, $x, 160, $black, $font, $text);

				$text = $game_data->game_set3_p1.':'.$game_data->game_set3_p2;
				list($left, $bottom, $right, , , $top) = imageftbbox($font_size, 0, $font, $text);
				// Determine offset of text
			  $left_offset = ($right - $left) / 2;
			  $x = 280 - $left_offset;
				imagettftext($im, $font_size, 0, $x, 200, $black, $font, $text);
			}
			else
			{
				$font_size = 30;
				$text = $game_data->game_set1_p1.':'.$game_data->game_set1_p2;
				list($left, $bottom, $right, , , $top) = imageftbbox($font_size, 0, $font, $text);
				// Determine offset of text
			  $left_offset = ($right - $left) / 2;
			  $x = 280 - $left_offset;
				imagettftext($im, $font_size, 0, $x, 135, $black, $font, $text);

				$text = $game_data->game_set2_p1.':'.$game_data->game_set2_p2;
				list($left, $bottom, $right, , , $top) = imageftbbox($font_size, 0, $font, $text);
				// Determine offset of text
			  $left_offset = ($right - $left) / 2;
			  $x = 280 - $left_offset;
				imagettftext($im, $font_size, 0, $x, 175, $black, $font, $text);
			}
		}
		else
		{
			$font_size = 30;
			$text = $game_data->game_set1_p1.':'.$game_data->game_set1_p2;
			list($left, $bottom, $right, , , $top) = imageftbbox($font_size, 0, $font, $text);
			// Determine offset of text
		  $left_offset = ($right - $left) / 2;
		  $x = 280 - $left_offset;
			imagettftext($im, $font_size, 0, $x, 150, $black, $font, $text);
		}
	}
	
}

imagepng($im);
imagedestroy($im);
?>
