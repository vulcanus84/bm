<?php
      $myMenu = new menu($db);
      $myMenu->add_item("Benutzer berechtigen","index.php");
      $myMenu->add_item("Trainingsorte berechtigen","location_permissions.php");
      $myMenu->add_item("Berechtigungen auswerten","permission_report.php");
      $myMenu->add_item("Texte übersetzen","translation.php");
      $myMenu->add_item("Log","log.php");
			$myMenu->add_item("Passwort setzen","set_password.php");
      $myPage->menu = $myMenu->create_menu("tabsJ");
