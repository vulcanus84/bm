<?php
	class obj
	{
		public $name;
		public $extension;
		public $comment;
		public $filename;
		public function __construct($name,$extension,$filename,$comment)
		{
			$this->name = $name;
			$this->extension = $extension;
			$this->filename = $filename;
			$this->comment = $comment;
		}
	}

  define("level","../");                               //define the structur to to root directory (e.g. "../", for files in root set "")
  require_once(level."inc/standard_includes.php");     //Load all necessary files (DB-Connection, User-Login, etc.)

	if(isset($_SESSION['arr_classes']))
	{
		foreach($_SESSION['arr_classes'] as $class) { if(!class_exists($class->name)){ include_once($class->filename); } }
	}

	function cmp($a,$b)
	{
		return strcmp($a->getName(),$b->getName());
	}

	function get_comment($class,$myid)
	{
		foreach($_SESSION['arr_classes'] as $curr_class)
		{
			if($curr_class->name==$class->getName()) { break; }
		}
		$file_handle = fopen($curr_class->filename, "r");
		$i=0;
		while (!feof($file_handle))
		{
			$i++;
			$x = "";
			$line = fgets($file_handle)." ";
			$lines[$i] = $line;
			if(strpos($line,$myid) !== FALSE)
			{
				for($j=$i-1;$j>2;$j--)
				{
					if(strpos($lines[$j],'*')!== FALSE)
					{
						if(trim(str_replace("/","",str_replace("*","",$lines[$j])))!='') { $x = $lines[$j]."<br>".$x; }
					} else { break; }
				}
				break;
			}
		}
		return trim(str_replace("*","",$x));
	}

	function get_properties($class)
	{
		$x = "";
		$properties = $class->getProperties(ReflectionProperty::IS_PUBLIC);
		usort($properties,'cmp');
		foreach($properties as $p)
		{
			$txt = "<span class='property_entry' style='cursor:pointer;' onclick=\"$('.method_entry').each(function(index) { $(this).css('color','black'); });
      																																			$('.property_entry').each(function(index) { $(this).css('color','black'); });
																																						$(this).css('color','blue');
																																						$('#description').load('index.php?class=".$class->getName()."&property=".$p->getName()."'); \">".$p->getName()."</span><br>";
			$x.=$txt;
		}
		return $x;
	}

	function get_methods($class)
	{
		$x = "";
		$methods = $class->getMethods(ReflectionMethod::IS_PUBLIC);
		usort($methods,'cmp');
		foreach($methods as $m)
		{
			if($m->class==$class->getName())
			{
				$txt = "<span class='method_entry' style='cursor:pointer;' onclick=\"$('.method_entry').each(function(index) { $(this).css('color','black'); });
	      																																			$('.property_entry').each(function(index) { $(this).css('color','black'); });
																																							$(this).css('color','blue');
																																							$('#description').load('index.php?class=".$class->getName()."&method=".$m->getName()."'); \";> public function <b>".$m->getName()."</b> (";
				$parameters = $m->getParameters();
				foreach($parameters as $param)
				{
					 if($param->isOptional())
					 {
			    	 $txt.= ", <i>".$param->getName()."</i>";
						}
						else
						{
			    	 $txt.=	", ".$param->getName();
						}
				}
				$txt = str_replace("(, ","(",$txt);
				$txt.= ")</span><br>";
				$x.= $txt;
			}
		}
		return $x;
	}


  if(!IS_AJAX)
  {
		$_SESSION['arr_classes'] = array();
		$it = new RecursiveDirectoryIterator(level."inc");
		foreach(new RecursiveIteratorIterator($it) as $file)
		{
			if(substr($file->getFilename(),0,5) == 'class' AND substr($file->getFilename(),-4) == '.php')
			{
				$file_handle = fopen($file, "r");
				while (!feof($file_handle))
				{
					$line = trim(fgets($file_handle))." ";
					if(substr($line,0,5) =='class')
					{
						$line = substr($line,6);
						$tmp = substr($line,strpos($line," "));
						$line = trim(substr($line,0,strpos($line," ")));
						if(!class_exists($line)){ include_once($file); }
						$class = new ReflectionClass($line);
						$_SESSION['arr_classes'][] = new obj($line,$tmp,$file->__toString(),$class->getDocComment());
					}
				}
				fclose($file_handle);
			}
		}
	  //Display page
	  $myPage = new page ($db);
	  $myPage->set_title("CCS Documentation");
	  $myPage->set_subtitle("Online Help");
		$myPage->add_css("div.frame { float:left;background-color:#FFF;padding:5px;}");
		$myPage->add_css("div.area { float:left;overflow:auto;padding:5px;border-radius:10px;padding:10px;}");
		$myPage->add_css("p.headline { font-size:16pt;margin-top:5px;margin-bottom:10px;font-weight:bold; }");

		$myPage->add_content("<div class='frame'><p class='headline'>Classes</p><div class='area' style='width:20vw;height:600px;background-color:#FFA56D;'>");

		usort($_SESSION['arr_classes'], function($a, $b) { return strcasecmp($a->name, $b->name); });

		foreach($_SESSION['arr_classes'] as $curr_class)
		{
			$class = new ReflectionClass($curr_class->name);
			$myPage->add_content("<span class='class_entry' onclick=\"$('.class_entry').each(function(index) { $(this).css('font-weight','lighter'); });
																																$(this).css('font-weight','bold');
																																$('#properties').load('index.php?class=".$curr_class->name."&typ=properties');
																																$('#methods').load('index.php?class=".$curr_class->name."&typ=methods');
																																$('#description').load('index.php?class=".$curr_class->name."&typ=description'); \");
																																style='cursor:pointer;font-size:12pt;' title='".$curr_class->filename."'>".$curr_class->name." <i style='font-weight:lighter;font-size:9pt;'>".$curr_class->extension."</i></span><br>");
		}

		$myPage->add_content("</div></div>");
		$myPage->add_content("<div class='frame'><p class='headline'>Properties</p><div id='properties' class='area' style='width:20vw;height:200px;background-color:#FFFAA5;'></div></div>");
		$myPage->add_content("<div class='frame'><p class='headline'>Methods</p><div id='methods' class='area' style='width:46vw;height:200px;background-color:#CCCCFF;'></div></div>");
		$myPage->add_content("<div class='frame'><p class='headline'>Description / Example</p><div id='description' class='area' style='background-color:#DDFFDD;width:68vw;height:330px;'></div></div>");

	  print $myPage->get_html_code();
  }
  else
  {
		if(isset($_GET['class']))
		{
			$class = new ReflectionClass($_GET['class']);
			if(isset($_GET['typ']))
			{
				if($_GET['typ']=='properties') { print get_properties($class); }
				if($_GET['typ']=='methods') { print get_methods($class); }
				if($_GET['typ']=='description')
				{
					$txt = trim(nl2br(substr(str_replace("*","",$class->getDocComment()),4,-3)));
					print $txt;
				}
			}
			if(isset($_GET['method'])) { print get_comment($class,'public function '.$_GET['method']); }
			if(isset($_GET['property'])) { print get_comment($class,'public $'.$_GET['property']); }
		}
    //Performe ajax requests..
  }
?>
