<?php

namespace Tournament;

define("level","../");																//define the structur to to root directory (e.g. "../", for files in root set "")
require_once(level."inc/standard_includes.php");		//Load all necessary files (DB-Connection, User-Login, etc.)
require_once("inc/php/class_tournament.php");	
if(!isset($_SESSION['login_user'])) { header("Location: ../index.php"); }
try
{
	$myPage = new \page();
	$myLogger = new \log();
	$myPage->add_js_link('inc/js/players.js');
	$myPage->add_css_link('inc/css/index.css');
	$myPage->add_css_link('inc/css/layout.css');

	$myTournament = new tournament();
	if(isset($_GET['action']) && $_GET['action']=='change_location_filter') 
	{ 
		$page->change_parameter('location_filter',$_POST['location']);
		$page->remove_parameter('action');
		header("Location: ".$page->get_link());
	}

	if(!isset($_GET['order_by'])) { $_GET['order_by']='location'; }

	if(isset($_GET['action']) && isset($_POST['user_account']))
	{
		$folder = 'user_pics/';
		$username = $_POST['user_account'];
		
		//Check if account exist
		if(isset($_POST['user_id']) && $_POST['user_id']>0) {
			$db->sql_query("SELECT * FROM users	WHERE user_account=:uacc AND user_id<>:uid",array('uacc'=>$_POST['user_account'],'uid'=>$_POST['user_id']));
		} else {
			$db->sql_query("SELECT * FROM users	WHERE user_account=:uacc",array('uacc'=>$_POST['user_account']));
		}

		//if exist, try to add with underline and number an check again for 10 times, after that a sql will appear
		if($db->count()>0) {
			$x = 1;
			while($x < 10)
			{
				$new_account = $_POST['user_account']."_".$x;
				$db->sql_query("SELECT * FROM users	WHERE user_account=:uid",array('uid'=>$new_account));
				if($db->count()==0) { $_POST['user_account'] = $new_account; break; }
				$x++;
			}
		}

		if(isset($_POST['user_id']) && $_POST['user_id']>0)
		{
			$birthday = $_POST['user_birthday'];
			$user_id = $_POST['user_id'];
			if(isset($_POST['user_hide'])) { $user_hide = '1'; } else { $user_hide = '0'; }
			//Update User
			if($birthday=='')
			{
				$db->update(array('user_account'=>$_POST['user_account'],'user_firstname'=>$_POST['user_firstname'],'user_lastname'=>$_POST['user_lastname'],'user_gender'=>$_POST['user_gender'],'user_birthday'=>null,'user_hide'=>$user_hide),'users','user_id',$user_id);
			}
			else
			{
				$birthday = $helper->date2iso($_POST['user_birthday']);
				$db->update(array('user_account'=>$_POST['user_account'],'user_firstname'=>$_POST['user_firstname'],'user_lastname'=>$_POST['user_lastname'],'user_gender'=>$_POST['user_gender'],'user_birthday'=>$birthday,'user_hide'=>$user_hide),'users','user_id',$user_id);
			}
		}
		else
		{
			$db->insert(array('user_account'=>$_POST['user_account'],'user_gender'=>$_POST['user_gender']),'users');
			$user_id = $db->last_inserted_id;
			$page->change_parameter('user_id',$user_id);
		}

		//Update Training locations
		//Check current locations
		$db->sql_query("SELECT * FROM location_permissions
							LEFT JOIN locations ON loc_permission_loc_id = location_id
							LEFT JOIN (SELECT * FROM location2user WHERE location2user_user_id='".$user_id."') as lj ON lj.location2user_location_id = locations.location_id
							WHERE loc_permission_user_id = '".$_SESSION['login_user']->id."'
							ORDER BY location_name");
		while($d = $db->get_next_res())
		{
			if($d->location2user_id>0) 
			{ 
				//Shoud be checked, otherwise it was changed
				if(!isset($_POST['loc_'.$d->location_id])) 
				{ 
					//Not checked, therefor remove location
					$db->delete('location2user','location2user_id',$d->location2user_id);
				}
			}
			else
			{
				//Shoud NOT be checked, otherwise it was changed
				if(isset($_POST['loc_'.$d->location_id])) 
				{ 
					//Checked, therefor add location
					$db->insert(array('location2user_user_id'=>$user_id,'location2user_location_id'=>$d->location_id),'location2user');
				}
			}
		}

		foreach ($_FILES["pictures"]["error"] as $key => $error) 
		{
		    if ($error == UPLOAD_ERR_OK) 
			{
				//Add user to DB
				if(file_exists($folder.$user_id.'.png')) { unlink($folder.$user_id.'.png'); }

		        $tmp_name = $_FILES["pictures"]["tmp_name"][$key];
		        // basename() kann Directory Traversal Angriffe verhindern; weitere
		        // Gültigkeitsprüfung/Bereinigung des Dateinamens kann angebracht sein
		        $name = basename($_FILES["pictures"]["name"][$key]);
		        move_uploaded_file($tmp_name, $folder.$name);

				//Crop image to 1:1 ratio
				$filename = $folder.$name;
				$im = imagecreatefromstring(file_get_contents($filename));

				$w = imagesx($im);
				$h = imagesy($im);

				$size = min($w,$h);

				if($w>$h) { $diff_x = ($w-$h)/2; } else { $diff_x = 0; }
				if($w<$h) { $diff_y = ($h-$w)/2; } else { $diff_y = 0; }

				$image_s = imagecrop($im, ['x' => $diff_x, 'y' => $diff_y, 'width' => $size, 'height' => $size]);
				imagedestroy($im);

				// $index = imagecolorclosest ( $image_s,  1,1,1 ); // get black color
				// imagecolorset($image_s,$index,10,10,10); // SET NEW COLOR

				//Round mask
				$width = imagesx($image_s);
				$height = imagesy($image_s);

				$newwidth = 500;
				$newheight = 500;

				$image = imagecreatetruecolor($newwidth, $newheight);
				imagealphablending($image, true);
				imagecopyresampled($image, $image_s, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);


				//Create mask (filled transparent circle)
				$mask = imagecreatetruecolor($newwidth, $newheight);
				$transparent = imagecolorallocate($mask, 255, 0, 0);
				imagecolortransparent($mask,$transparent);
				imagefilledellipse($mask, $newwidth/2, $newheight/2, $newwidth-5, $newheight-5, $transparent);

                //Merge player picture in the mask with the transparent circle
				imagecopymerge($image, $mask, 0, 0, 0, 0, $newwidth, $newheight, 100);
                //Define another color for transparency
                $red = imagecolorallocate($mask, 255, 0, 0);
				imagecolortransparent($image,$red);
                //Fill it from the top left corner (like a fill function in a drawing app)
				imagefill($image, 0, 0, $red);

				$exif = exif_read_data($filename);

				if (isset($exif['Orientation']))
				{
					switch ($exif['Orientation'])
					{
					case 3:
						// Need to rotate 180 deg
							$image = imagerotate($image, 180, 0);
						break;

					case 6:
						// Need to rotate 90 deg clockwise
							$image = imagerotate($image, -90, 0);
						break;

					case 8:
						// Need to rotate 90 deg counter clockwise
							$image = imagerotate($image, 90, 0);
						break;
					}
				}

				//output, save and free memory
				imagepng($image,$folder.$user_id.'.png');

				//*********************************
				//Create Thumbnail
				//*********************************

				//Round mask
				$width = imagesx($image_s);
				$height = imagesy($image_s);

				$newwidth = 120;
				$newheight = 120;

				$image = imagecreatetruecolor($newwidth, $newheight);
				imagealphablending($image, true);
				imagecopyresampled($image, $image_s, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);

				//create masking
				$mask = imagecreatetruecolor($newwidth, $newheight);

				$transparent = imagecolorallocate($mask, 255, 0, 0);
				imagecolortransparent($mask,$transparent);

				imagefilledellipse($mask, $newwidth/2, $newheight/2, $newwidth, $newheight, $transparent);

				$red = imagecolorallocate($mask, 0, 0, 0);
				imagecopymerge($image, $mask, 0, 0, 0, 0, $newwidth, $newheight, 100);
				imagecolortransparent($image,$red);
				imagefill($image, 0, 0, $red);

				$exif = exif_read_data($filename);

				if (isset($exif['Orientation']))
				{
					switch ($exif['Orientation'])
					{
					case 3:
						// Need to rotate 180 deg
							$image = imagerotate($image, 180, 0);
						break;

					case 6:
						// Need to rotate 90 deg clockwise
							$image = imagerotate($image, -90, 0);
						break;

					case 8:
						// Need to rotate 90 deg counter clockwise
							$image = imagerotate($image, 90, 0);
						break;
					}
				}

				//output, save and free memory
				imagepng($image,$folder.$user_id.'_t.png');

				$filename_new = 'uploads/'.microtime().$name;
				rename($folder.$name, $filename_new);

				imagedestroy($image);
				imagedestroy($image_s);
				imagedestroy($mask);

		    }
		}
		$page->remove_parameter('action');
		$page->remove_parameter('ajax');

		$myUser = new \user($user_id);
		$myUser->create_star_image();

		header("Location: ".$page->get_link());
	}

	//Javascript links need at least one parameter because of the &param
	if(!isset($_GET['user_id'])) { $page->change_parameter('x','1'); }
	$_SERVER['link'] = $page->get_link();


	if(!IS_AJAX)
	{
		//Display page
		//$myPage->set_title("Badminton Academy");
		$myPage->permission_required=false;
		$myPage->set_title("Spielerverwaltung");
		$myPage->add_content("<div id='left_col' style='flex: 0 0 30vw;'>");
		$myPage->add_content("	<div id='collapsed_label'>Spieler</div>");
		$myPage->add_content("	<div id='left_header'>");
		$myPage->add_content("		<span><a href='index.php'><button class='orange'>Turniere</button></a></span>");
		$myPage->add_content("		<span><a href='players.php'><button class='activated blue'>Spieler</button></a></span>");
		$myPage->add_content("	</div>");
		$myPage->add_content("	<div id='left_content'>");
		$myPage->add_content($myTournament->html->get_all_users($_GET['order_by']));
		$myPage->add_content("	</div>");
		$myPage->add_content("</div>");
		$myPage->add_content("<div id='right_col'>");
		$myPage->add_content("	<div class='menu_item'><button class='green' onclick='new_user();'>Neues Spieler</button></div>");
		$myPage->add_content("</div>");		
		print $myPage->get_html_code();
	}
	else
	{
		include('inc/php/ajax.php');
	}
}
catch (\Exception $e)
{
	$myPage = new \page();
	$myPage->error_text = $e->getMessage();
	print $myPage->get_html_code();
}
?>
