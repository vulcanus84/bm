//Event handlers
var t_courts = [];

$(document).ready(function() {
  get_open_games();

  $('.droppable').droppable({
    hoverClass: 'hover',
    drop: function( event, ui ) {
      var game_id = ui.draggable.attr('id');
      var court = $(this).attr('id');
      $('#'+court).load('index.php?ajax=refresh_court&court='+court+'&game_id='+game_id,
      function()
      {
        ui.draggable.hide();
        $('#'+court).droppable('disable');
      });
    }
  });

  $('.droppable').each(function() {
    var gameId = parseInt($(this).data('game-id'), 10);
    if (gameId > 0) {
        $(this).droppable('disable');    // deaktiviert Droppable
    }
  });
  
  setInterval(function() { get_open_games(); }, 60000);

});

function makeTimer(court,startTime) 
{
  startTime = (Date.parse(startTime) / 1000);

  var now = new Date();
  now = (Date.parse(now) / 1000);

  var timeElapsed = now - startTime; 
  var days = Math.floor(timeElapsed / 86400); 
  var hours = Math.floor((timeElapsed - (days * 86400)) / 3600);
  var minutes = Math.floor((timeElapsed - (days * 86400) - (hours * 3600 )) / 60);
  var seconds = Math.floor((timeElapsed - (days * 86400) - (hours * 3600) - (minutes * 60)));

  if (hours < '10') { hours = '0' + hours; }
  if (minutes < '10') { minutes = '0' + minutes; }
  if (seconds < '10') { seconds = '0' + seconds; }

  $('#timer_court'+court).html(hours + ':' + minutes + ':' + seconds);
}


function get_open_games()
{
  $('#open_games').load('index.php?ajax=get_open_games',
    function() { 			    
      $('.draggable').draggable({
      revert: 'invalid'
    });
  });
}

function play_court(court_id,game_id,resume=false)
{
  if(resume) 
  { 
    var my_url = 'index.php?ajax=resume&court='+court_id+ '&game_id='+game_id;
    $.ajax({ url: my_url }).done(
    function(data)
    {
      location.reload();
    });
  }
  else
  {
    $('#court'+court_id).load('index.php?ajax=set_start_time&court='+court_id+ '&game_id='+game_id,
    function()
    {
      var x = new Date();
      t_courts[court_id] = setInterval(function() { makeTimer(court_id,x.toUTCString()); }, 1000);
    });
  }
  
}
function stop_court(court_id)
{
  clearInterval(t_courts[court_id]);
  $('#court'+court_id).load('index.php?ajax=stopp_time&court='+court_id,
  function()
  {
  });
}

function save_court(court_id)
{
  clearInterval(t_courts[court_id]);
  $('#court'+court_id).load('index.php?ajax=save_court&court='+court_id,
  function()
  {
    $('#court'+court_id).droppable('enable');
  });
}


function clear_court(court_id,game_id)
{
  $('#court'+court_id).load('index.php?ajax=refresh_court&court='+court_id+ '&game_id=',
  function()
  {
    $('#court'+court_id).droppable('enable');
    get_open_games();
  });
}
