<?php
define("level","../");
require_once(level."inc/standard_includes.php");

try {
    if(!IS_AJAX) {
        $myPage = new page();
        $myPage->set_title("Reaction Exercises");
        if(!$myPage->is_logged_in()) { 
            print $myPage->get_html_code(); 
            exit; 
        }

        $myPage->add_js_link('inc/js/index.js');
        $myPage->add_css_link('inc/css/index.css');

        $myPage->add_content("<h1>Reaction Exercises</h1>");
        $myPage->add_content("<button id='new_exercise'>Neu anlegen</button>");
        $myPage->add_content("<div id='reaction_list'></div>");

        // Modal (wie vorgegeben)
        $myPage->add_content("
            <div id='myModal' class='modal'>
                <div class='modal-content'>
                    <span class='close'>&times;</span>
                    <p id='myModalText'></p>
                </div>
            </div>
        ");

        print $myPage->get_html_code();
    } else {
        include('inc/php/ajax.php');
    }
} catch (Exception $e) {
    $myPage = new page();
    $myPage->error_text = $e->getMessage();
    print $myPage->get_html_code();
}
?>
