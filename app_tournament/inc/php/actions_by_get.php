<?php 
namespace Tournament;

if(isset($_GET['action']))
{
  if(isset($_GET['tournament_id']))
  {
    switch ($_GET['action']) 
    {
        //Reactivate tournament
        case 'reactivate_tournament':
          $db->update(array('group_status'=>'Started'),'groups','group_id',$_GET['tournament_id']);
          break;
        
        //Delete tournament
        case 'delete_tournament':
          $db->delete('groups','group_id',$_GET['tournament_id']);
          header("Location: index.php");
          break;

        case 'change_location_filter':
          $page->change_parameter('location_filter',$_POST['location']);
          $page->remove_parameter('action');
          header("Location: ".$page->get_link());
          break;
      }
  }
  else
  {
    switch ($_GET['action']) 
    {
        case 'save_tournament':
          if($_POST['tournament_title']!='')
          {
            $myTournament = new tournament($_POST['tournament_id']);
            $myTournament->title = $_POST['tournament_title'];
            $myTournament->description = $_POST['tournament_description'];
          
            $myTournament->system = $_POST['tournament_system'];
            $myTournament->counting = $_POST['tournament_counting'];
            $myTournament->location = new \location($_POST['created_by_location']);  
            $myTournament->save();
            
            if($myTournament->id == null)
            {
              header("Location: index.php?tournament_id=".$myTournament->id);
            }
            else
            {
              header("Location: index.php");
            }
          }
          else
          {
            throw new \Exception("Titel muss ausgefüllt werden");
          }
          break;
    }

  }
}
