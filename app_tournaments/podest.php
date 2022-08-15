<?php
//header("Content-type: image/png");
define("level","../");                              //define the structur to to root directory (e.g. "../", for files in root set "")
require_once(level."inc/standard_includes.php");    //Load all necessary files (DB-Connection, User-Login, etc.)
$im    = imagecreatefrompng("podest.png");
$background = imagecolorallocate($im , 255, 255, 255);
imagecolortransparent($im, $background);

$color = imagecolorallocate($im, 254, 255, 255);
$font = level."inc/CSM.ttf";
$font_size = 24;

if(isset($_GET['p4']) && $_GET['p4']!='')
{
	for($i=1;$i<7;$i++)
	{
		$c_user[$i] = new user($_GET['p'.$i]);    
		$user[$i] = imagecreatefrompng($c_user[$i]->get_pic_path());
	}

	$x_pos[1] = 250; $y_pos[1] = 118;
	$x_pos[2] = 350; $y_pos[2] = 118;

	$x_pos[3] = 40; $y_pos[3] = 170;
	$x_pos[4] = 140; $y_pos[4] = 170;

	$x_pos[5] = 470; $y_pos[5] = 195;
	$x_pos[6] = 570; $y_pos[6] = 195;

	$i=0;
	for($i=1;$i<7;$i=$i+2)
	{
		imagecopyresized($im, $user[$i], $x_pos[$i], $y_pos[$i], 0, 0, 120, 120, 500, 500);
		imagecopyresized($im, $user[$i+1], $x_pos[$i+1], $y_pos[$i+1], 0, 0, 120, 120, 500, 500);
		$my_user1 = $c_user[$i];
		$my_user2 = $c_user[$i+1];
		$text = $my_user1->login." & ".$my_user2->login;
		list($left, $bottom, $right, , , $top) = imageftbbox($font_size/1.5, 0, $font, $text);
		// Determine offset of text
	  $left_offset = ($right - $left) / 2;
		// Generate coordinates
	  $x = $x_pos[$i] - $left_offset + 110;
		imagettftext($im, $font_size/1.5, 0, $x, $y_pos[$i]+145, $color, $font, $text);
	}

}
elseif(isset($_GET['p1']))
{
	for($i=1;$i<4;$i++)
	{
		$c_user[$i] = new user($_GET['p'.$i]);    
		$user[$i] = imagecreatefrompng($c_user[$i]->get_pic_path());
	}

	$x_pos[1] = 265; $y_pos[1] = 60;
	$x_pos[2] = 55; $y_pos[2] = 110;
	$x_pos[3] = 475; $y_pos[3] = 130;

	$i=0;
	foreach($c_user as $my_user)
	{
		$i++;
		imagecopyresized($im, $user[$i], $x_pos[$i], $y_pos[$i], 0, 0, 200, 200, 500, 500);
		$text = $my_user->login;
		list($left, $bottom, $right, , , $top) = imageftbbox($font_size, 0, $font, $text);
		// Determine offset of text
	  $left_offset = ($right - $left) / 2;
		// Generate coordinates
	  $x = $x_pos[$i] - $left_offset + 100;
		imagettftext($im, $font_size, 0, $x, $y_pos[$i]+205, $color, $font, $text);
	}
}

$img_crown = imagecreatefrompng(level.'inc/imgs/crown.png');
$img_crown_width = imagesx($img_crown);
$img_crown_height = imagesy($img_crown);
imagecopyresized($im, $img_crown, 240, 0, 0, 0, $img_crown_width/2, $img_crown_height/2, $img_crown_width, $img_crown_height);

imagepng($im);
imagedestroy($im);
?>
