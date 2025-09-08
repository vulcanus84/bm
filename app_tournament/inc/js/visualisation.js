let rounds=0;
let curr_round = 0;
let curr_tournament = -1;
let all_tournament_ids = "";
let arr_tournaments = [];

$( document ).ready(function() {
  all_tournament_ids = $('#title').data('tournament-ids');
  arr_tournaments = all_tournament_ids.split(',');
  refresh();                         
});

function refresh()
{
  //Load next tournament
  if(curr_round==rounds) {
    if(arr_tournaments.length>curr_tournament+1) { curr_tournament++; } else { curr_tournament = 0; }
    curr_round = 0; 
    $.ajax({
      method: 'GET',
      url: 'visualisation.php',
      data: { action: 'get_number_of_rounds', tournament_id: arr_tournaments[curr_tournament] }
    })
    .done(function(data) {
      rounds=data;
    });

    $('#title').load('visualisation.php?action=get_title&tournament_id='+all_tournament_ids+'&curr_id='+arr_tournaments[curr_tournament]);
    $('#users').load('visualisation.php?action=get_users&tournament_id='+arr_tournaments[curr_tournament]);
    $('#rounds').load('visualisation.php?action=get_rounds&tournament_id='+arr_tournaments[curr_tournament]);
    $('#news').load('visualisation.php?action=get_news&tournament_id='+arr_tournaments[curr_tournament]);
  }
  curr_round++;

  $('#rounds [id^=round]').css('background-color', '#4CAF50');

  let $courts = $('#all_courts');
  let url = 'visualisation.php?action=get_courts'
          + '&tournament_id=' + arr_tournaments[curr_tournament]
          + '&round=' + curr_round;

  $courts.fadeOut(function () {
    $courts.load(url, function () {
      setTimeout(() => $courts.fadeIn(), 500);
      $('#round' + curr_round).css('background-color', 'orange');
    });
  });

}
setInterval(function(){
    refresh()
}, 6000);

