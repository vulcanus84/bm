<?php
      $myMenu = new menu($db);
      $myMenu->add_item("Turniere","index.php");
      $myMenu->add_item("Turniere zuweisen","group2user.php");
      $myMenu->add_item("Spiele","games.php");
      $myMenu->add_item("Kommentare","comments.php");
      $myMenu->add_item("Journal","journal.php");
      $myMenu->add_item("News","news.php");
      $myMenu->add_item("Benutzer","users.php");
      $myMenu->add_item("Prüfungen","exams.php");
      $myMenu->add_item("Prüfungen zuweisen","exam2user.php");
      $myMenu->add_item("Trainingsorte","locations.php");
      $myMenu->add_item("Trainingsorte zuweisen","location2user.php");
      $myMenu->add_item("Admin Functions","admin.php");
      $myPage->menu = $myMenu->create_menu("tabsJ");
