var tournamentId;
var base_link = String(window.location.origin + window.location.pathname);
var server_link = String(window.location);
if(!server_link.includes('?')) { server_link = server_link + '?x=y'; }

const queryString = window.location.search;
const params = new URLSearchParams(queryString);

const urlParams = {};
for (const [key, value] of params) {
  urlParams[key] = value;
}


$(document).ready(function() {
  tournamentId = $('#content').data('tournament-id');
  if(!tournamentId) $('#left_col').addClass('open'); 
  setEvents();
});

function setEvents() {

  //Remove all events from main part with the delegations
  $('#content').off();

  //Location filter
  $('#content').on('change', 'select[name="location"]', function() {
    const allSections = $('#left_content section');
    const selected = $(this).val();
    allSections.hide();

    if (!selected) {
      allSections.show();
    } else {
      allSections.each(function() {
        if ($(this).find('h1').text().includes($(this).find('h1').text().split('(')[0].trim())) {
          $(this).toggle($(this).find('h1').text().includes($('select[name="location"] option:selected').text().trim()));
        }
      });
    }
  });

  //Sort type
  $('img.img_sort').off('click').on('click', (e) => change_group_by(e));

  //New Tournament
  $('#content').on('submit','form#new_tournament', function(e) {
    e.preventDefault(); // Formular nicht normal absenden
  
    let formData = new FormData(this); // FormData aus Formular

    $.ajax({
      url: server_link+'&ajax=save_tournament',
      type: 'POST',
      data: formData,
      processData: false, // wichtig bei FormData
      contentType: false, // ebenfalls wichtig
      success: function(response) {
        if(response.substring(0,2)=='OK')
          {
            const url = new URL(window.location);
            url.searchParams.set('tournament_id',response.substring(2));
            window.location.href = url.toString();
          }
          else
          {
            alert(response);
          }
      },
      error: function(xhr, status, error) {
        $('#right_content').html(error);
      }
    });
  });

  //Main buttons on tournaments list
  $('#content').on('click','button#edit_tournament', function(e) {
    $('#left_col').toggleClass('open');
    get_tournament_form($(e.currentTarget));
  });
  $('#content').on('click', 'button#delete_tournament_permission', function(e) { 
    $('#left_col').toggleClass('open');
    delete_tournament_permission($(e.currentTarget));
  });
  $('#content').on('click', 'button#open_tournament', (e) => window.location = base_link + '?tournament_id=' + $(e.currentTarget).data('tournament-id'));

  //Buttons in deletion mode
  $('#content').on('click', 'button#delete_tournament', (e) => delete_tournament($(e.currentTarget)));
  $('#content').on('click', 'button#abort_tournament_delete', (e) => window.location = base_link);

  //Buttons in edit mode
  $('#content').on('click', 'button#reactivate_tournament', (e) => reactivate_tournament($(e.currentTarget)));

  //Buttons in opened tournament on header
  $('#content').on('click', 'button#stop_tournament', (e) => stop_tournament());
  $('#content').on('click', 'button#start_tournament', (e) => start_tournament());
  $('#content').on('click', 'button#close_tournament', (e) => close_tournament());
  $('#content').on('click', 'button#tournament_homepage', (e) => window.location = base_link + '?tournament_id=' + tournamentId);

  $('#content').on('click', 'button#draw', (e) => define_games());
  $('#content').on('click', 'button#delete_draw', (e) => clear_it());
  $('#content').on('click', 'button#close_round', (e) => close_round());
  $('#content').on('click', 'button#reset_round', (e) => reset_round($(e.currentTarget).data('round')));
  $('.change_round').off('click').on('click', function(e) {
    var round = $(e.currentTarget).data('round');
    const url = new URL(window.location);
    url.searchParams.set('round',round);
    window.location.href = url.toString();
  });

  //Seedings
  $('#content').on('click', 'button#define_seedings', (e) => define_seeded_players());
  $('#content').on('click', 'button#delete_last_seeding', (e) => delete_last_seeding());

  //Teams
  $('#content').on('click', 'button#define_teams', (e) => define_teams());
  $('#content').on('click', 'button#delete_team', (e) => delete_team(e.currentTarget));

  //Back button in stats Page for user in tournament
  $('#content').on('click', 'button#back_to_round', (e) => window.location = server_link);

  switch ($('#content').data('status'))
  {
    case 'New':
      $('#content').on('click','div.user_pic', (e) => add_user(e.currentTarget.id));
      break;

    case 'Define_teams':
      $('#content').on('click','.user_pic', (e) => add_as_partner(e.currentTarget));
      break;
    
    case 'Started':
      if($('#content').data('round-status')=='Drawn') {
        $('#content').on('click','img.img_court', function (e) {
          const freilos_field = $(e.currentTarget).data('freilos-field');
          if(freilos_field<1) {
            const gameId = $(e.currentTarget).data('game-id');
            const court = $(e.currentTarget).closest('div').attr('id');
            check_result(court,gameId);
          }
        });
      }
      $('#content').on('click','.user_pic', function(e) { show_user_games($(e.currentTarget).data('user-id')); });
      $('#content').on('click','.team_small', function(e) { show_user_games($(e.currentTarget).data('team-id')); });
      break;

    case 'Closed':
      $('#content').on('click','.user_pic', function(e) { show_user_games($(e.currentTarget).data('user-id')); });
      $('#content').on('click','.team_small', function(e) { show_user_games($(e.currentTarget).data('team-id')); });
      break;
  }

  if(urlParams['mode']=='details') {
    $('#award_ceremony').off('click').on('click', (e) => window.location = base_link + '?tournament_id=' + tournamentId + '&mode=award');
    $('#tournament_report').hide();
  } else {
    $('#tournament_report').off('click').on('click', (e) => window.location = base_link + '?tournament_id=' + tournamentId + '&mode=details');
    $('#award_ceremony').hide();
  }


  //Define which Buttons are shown
  switch($('#content').data('system')) {
    case 'Gruppenspiele':
      $('#close_tournament').show();
      $('#stop_tournament').show();
      break;
    case 'Schoch':
    case 'Doppel_fix':
    case 'Doppel_dynamisch':
      switch ($('#content').data('round-status')) {
        case 'New':
          $('#draw').show();
          $('#stop_tournament').show();
          if($('#content').data('round-id')>0) { $('#close_tournament').show(); } else { $('#close_tournament').hide(); }
          break;

        case 'Drawn':
          $('#delete_draw').show();
          $('#close_round').show();
          break;

        case 'Closed':
          $('#reset_round').show();
          $('#close_tournament').show();
          $('#stop_tournament').show();
          break;
      }
      break;
  }

  //Handling open/close of left panel on smartphone screens
  $('#left_col').off('click').on('click', function(e) {
    if (!$(e.target).closest('.user_pic, button, select, a, .dropdown,img').length) {
      $(this).toggleClass('open');
    }
  });

  $('#right_col').off('click').on('click', function(e) {
    if($('#left_col').hasClass('open')) { $('#left_col').toggleClass('open') };
  });

}


function perform_ajax(function_name,param_url,target_id=null) {
  var my_url = server_link+'&ajax='+function_name+'&tournament_id='+tournamentId+'&'+param_url;
  if(target_id!=null)   
  { 
    $('#'+target_id).load(my_url); 
  }
  else
  {
    $.ajax({ url: my_url }).done(
      function(data)
      {
        if(data=='OK') { window.location = server_link; } else { alert(data); }
      });  
  
  }
}

//***** Tournaments actions *****
function start_tournament() {
  var my_url = server_link+'&ajax=start_tournament';
  $.ajax({ url: my_url }).done(
  function(data)
  {
    if(data=='OK')
    {
      const url = new URL(window.location);
      url.searchParams.set('round','1');
      window.location.href = url.toString();
    }
    else
    {
      alert(data);
    }
  });
}

function stop_tournament() {
  if (confirm('Bist du sicher, dass du das Turnier abbrechen wilst? \n\n - Alle bisherigen Spiele und Partner werden gelöscht \n - Die zugewiesen Spieler und Setzlisten bleiben erhalten'))
  {
    var my_url = server_link+'&ajax=stopp_tournament&tournament_id='+tournamentId;
    $.ajax({ url: my_url }).done(
    function(data)
    {
      if(data!='') {
        alert(data); 
      } else {
        window.location = base_link + '?tournament_id='+tournamentId;
      }
    });
  }
}

function close_tournament() {
  var my_url = server_link+'&ajax=close_tournament';
  $.ajax({ url: my_url }).done(
  function(data)
  {
    if(data.substring(0, 2)=='OK')
    {
      window.location = base_link + '?tournament_id='+tournamentId;
    }
    else
    {
      alert(data);
    }
  });
}

function delete_tournament_permission(tournament) {
  $('.tournament-item').css('background-color', 'transparent');
  $(tournament).closest('div').parent().css('background-color','#FFE5B4');
  $('#right_col').load(server_link+'&ajax=delete_permission&tournament_id='+tournament.data('tournament-id'));
}

function delete_tournament(tournament) {
  var my_url = server_link+'&ajax=delete_tournament&tournament_id='+tournament.data('tournament-id');
  $.ajax({ url: my_url }).done(
  function(data)
  {
    if(data.substring(0, 2)=='OK')
    {
      window.location = base_link;
    }
    else
    {
      alert(data);
    }
  });
}

function reactivate_tournament(tournament) {
  var my_url = server_link+'&ajax=reactivate_tournament&tournament_id='+tournament.data('tournament-id');
  $.ajax({ url: my_url }).done(
  function(data)
  {
    if(data.substring(0, 2)=='OK')
    {
      window.location = base_link + '?tournament_id=' + data.substring(2);
    }
    else
    {
      alert(data);
    }
  });
}

function show_user_games(user_id) {
  $('#right_col').load(server_link+'&ajax=show_user_info&user_id='+user_id);
  $('#left_col').toggleClass('open');
}

function get_tournament_form(tournament) {
  $('.tournament-item').css('background-color', 'transparent');
  if(tournament) {
    $(tournament).closest('div').parent().css('background-color','#FFE5B4');
    $('#right_col').load(server_link+'&ajax=get_tournament_form&tournament_id='+tournament.data('tournament-id'));
  } else {
    $('#right_col').load(server_link+'&ajax=get_tournament_form');
  }
}

//***** Define players *****
function add_user(user_tag_id) {
  var pos = $('#'+user_tag_id).parent().closest('div')[0].id;
  var user_id = user_tag_id.replace(/user_/g, "");

  if(pos=='left_content')
  {
    var my_url = server_link+'&ajax=add_user&user_id=' + user_id;
    $.ajax({ url: my_url }).done(
    function(data)
    {
      let elem = $('#'+user_tag_id).clone();

      //No clean HTML I know, ID's should be unique but with the same person in different locations same ID's appear
      const elements = document.querySelectorAll('#'+user_tag_id); // Alle Elemente mit dieser ID finden
      elements.forEach(element => element.remove()); 

      $('#right_content').append(elem);
      update_number_of_players();
    });
  }
  else
  {
    var my_url = server_link+'&ajax=remove_user&user_id=' + user_id;
    $.ajax({ url: my_url }).done(
    function(data)
    {
      const items = data.trim().split(',');
      let elem = $('#'+user_tag_id).clone();
      $('#'+user_tag_id).remove();

      items.forEach(item => {
        let elem2 = elem.clone();
        $('#section_'+ item).append(elem2);
        let $container = $('#section_'+item);

        let $items = $container.find('.user_pic').get();        
        $items.sort(function(a, b) {
          let altA = ($(a).find('img').attr('alt') || '').toLowerCase();
          let altB = ($(b).find('img').attr('alt') || '').toLowerCase();
          return altA.localeCompare(altB);
        });
        
        $container.append($items);
      });
      update_number_of_players();
    });
  }
}

function new_user()
{
  $('#right_col').load(server_link+'&ajax=new_user');
}

function remove_user()
{
  $('#right_col').load(server_link+'&ajax=remove_user&tournament_id=$_GET[tournament_id]');
}

function update_number_of_players()
{
  let left_col = $('#left_col');
  let sections = left_col.find('section');

  sections.each(function(index, element) {
    let $section = $(element);

    let count = $section.find('.user_pic').length;
    let $h1 = $section.find('h1');

    $h1.text(function(_, oldText) {
      return oldText.replace(/\(\d+\)/, `(${count})`);
    });
  });

  let count = $('#right_col').find('.user_pic').length;
  let $h1 = $('#right_col').find('h1');
  $h1.text(function(_, oldText) {
    return oldText.replace(/\(\d+\)/, `(${count})`);
  });
}

function add_as_partner(user_tag_id) {
  perform_ajax('add_as_partner','user_id='+user_tag_id.id.replace(/user_/g, ""));
}

function delete_team(team_tag) {
  const teamId = $(team_tag).closest('div[data-team-id]').data('team-id');
  perform_ajax('delete_team','team_id='+teamId);
}

function define_teams() {
  var my_url = server_link+'&ajax=define_teams';
  $.ajax({ url: my_url }).done(
  function(data)
  {
    if(data.substring(0, 2)=='OK')
    {
      window.location = server_link;
    }
    else
    {
      alert(data);
    }
  });
}
//***** Seeding players *****
function add_as_seeded(user_id)
{
  var my_url = server_link+'&ajax=add_as_seeded&&user_id='+user_id;
  $.ajax({ url: my_url }).done(
  function(data)
  {
    if(data=='OK')
    {
      window.location = server_link;
    }
    else
    {
      alert(data);
    }
  });
}

function delete_last_seeding()
{
  var my_url = server_link+'&ajax=delete_last_seeding';
  $.ajax({ url: my_url }).done(
  function(data)
  {
    if(data=='OK')
    {
      window.location = server_link;
    }
    else
    {
      alert(data);
    }
  });
}

function define_seeded_players()
{
  var my_url = server_link+'&ajax=define_seeded_players';
  $.ajax({ url: my_url }).done(
  function(data)
  {
    if(data=='OK')
    {
      window.location = server_link+'';
    }
    else
    {
      alert(data);
    }
  });
}

//***** Handling rounds *****
function define_games()
{
  $.ajax({ url: server_link+'&ajax=define_games&tournament_id='+tournamentId+'&round=1'  }).done(
  function(data)
  {
    if(data=='OK')
    {
      var i = 1;
      $('#content')
        .data('round-status','Drawn')
        .attr('data-round-status', 'Drawn');
      $('div.court').each(function () {
        court = this.id.replace(/court/g, "");
        shuffle_game(court);
      });
      $('#delete_draw').show();
      $('#close_round').show();
      $('#draw').hide();
      $('#close_tournament').hide();
      $('#stop_tournament').hide();
      setEvents();
    }
    else
    {
      alert(data);
    }
  });
}

function shuffle_game(court_no)
{
  var delay = court_no*500;
  $('#court'+court_no).load(server_link+'&ajax=load',
  function()
  {
    $('#court'+court_no).delay(1000).fadeTo(delay,1,
    function (data)
    {
      $('#court'+court_no).load(server_link+'&ajax=show&court_id='+court_no);
    });
  });
}

function close_round()
{
  var my_url = server_link+'&ajax=close_round&tournament_id='+tournamentId;
  $.ajax({ url: my_url }).done(
  function(data)
  {
    if(data.substring(0, 2)=='OK')
    {
      var myNewUrl = server_link+'';
      myNewUrl = myNewUrl.replace('round='+urlParams['round'],'round='+ data.substring(3));
      window.location = myNewUrl;
    }
    else
    {
      alert(data);
    }
  });
}

function reset_round(round_nr) {
  var my_url = server_link+'&ajax=reset_round&tournament_id='+tournamentId+'&round='+round_nr;
  $.ajax({ url: my_url }).done(
  function(data)
  {
    if(data.substring(0, 2)=='OK')
    {
      window.location = server_link;
    }
    else
    {
      alert(data);
    }
  });
}

function clear_it()
{
  if (confirm('Bist du sicher, dass du die Auslosung löschen wilst? \n\n (alle eingetragen Spiele der aktuellen Runde und die Auslosungen werden gelöscht)'))
  {
    $('div.court').each(function () {
      var court = this.id.replace(/court/g, "");
      $(this).load(server_link+'&ajax=clear&tournament_id='+tournamentId+'&court_id='+court);
    });
    $('#content')
    .data('round-status','New')
    .attr('data-round-status', 'New');
    $('#delete_draw').hide();
    $('#close_round').hide();
    $('#draw').show();
    $('#close_tournament').show();
    $('#stop_tournament').show();
    setEvents();
  }
}

//***** Handling results *****
function check_result(court,game_id)
{
  $('#'+court).load(server_link+'&ajax=get_result&game_id='+game_id+'&court='+court, function() {
    if($('#content').data('counting') == 'win') {
      $('img.img_user').on('click', function (e) 
      {  
        set_winner($(e.currentTarget).data('user-id'),court);
      });
    }
    $('.abort').on('click', (e)=> set_winner(0,court));
    $('button.save_game').on('click', (e) => set_points_and_winner(court))
  });
}

function set_winner(user_id,court)
{
  $('#'+court).load(server_link+'&ajax=set_winner&winner_id='+user_id+'&court='+court);
}

function set_points_and_winner(court) {
  //Check result
  var error = '';
  var wins_p1=0;
  var wins_p2=0;
  var points_p1=0;
  var points_p2=0;
  var reset_result = false;
  
  court = court.replace(/court/g, "");
  const modus = $('#content').data('counting');
  const set1_p1 = $('#'+court+'_set1_p1').val();
  const set1_p2 = $('#'+court+'_set1_p2').val();
  const set2_p1 = $('#'+court+'_set2_p1').val();
  const set2_p2 = $('#'+court+'_set2_p2').val();
  const set3_p1 = $('#'+court+'_set3_p1').val();
  const set3_p2 = $('#'+court+'_set3_p2').val();

  if(modus=='pointsOneSet')
  {
    if(set1_p1 == set1_p2)
    {
      if(set1_p1 > 0 || set1_p2 > 0)
      {
        error += 'Punkte dürfen nicht gleich sein';
      }
    }
  }

  if(modus=='official2sets')
  {
    for(var i=1;i<4;i++)
    {
      //Point differences and maxPoints
      var max_points = 0; var diff = 0;
      if(i==1) { diff = Math.abs(set1_p1 - set1_p2); max_points = Math.max(set1_p1,set1_p2); points_p1 = parseInt(set1_p1); points_p2 = parseInt(set1_p2); }
      if(i==2) { diff = Math.abs(set2_p1 - set2_p2); max_points = Math.max(set2_p1,set2_p2); points_p1 = parseInt(set2_p1); points_p2 = parseInt(set2_p2); }
      if(i==3) { diff = Math.abs(set3_p1 - set3_p2); max_points = Math.max(set3_p1,set3_p2); points_p1 = parseInt(set3_p1); points_p2 = parseInt(set3_p2); }

      //If nothing is inserted skip checks
      if(i==1 && max_points==0) { reset_result = true; break; }

      if(i==3 && wins_p1 - wins_p2 !=0)
      {
        if(set3_p1!=0 || set3_p2!=0)
        {
          error += '3. Satz wird nur bei unentschieden nach 2 Sätzen gespielt';
        }
        break;
      }

      if(max_points>20 && max_points < 31)
      {
        if((diff < 2 && max_points!=30) || (diff > 2 && max_points>21))
        {
          error +=  'Punktedifferenz in Satz ' + i + ' nicht gültig';
          break;
        }
        else
        {
          if(points_p1>points_p2) { wins_p1++; }
          if(points_p1<points_p2) { wins_p2++; }
        }
      }
      else
      {
        error += 'Punkte in Satz ' + i + ' nicht gültig';
        break;
      }
    }
  }

  if(modus=='2sets11points')
  {
    for(var i=1;i<4;i++)
    {
      //Point differences and maxPoints
      var max_points = 0; var diff = 0;
      if(i==1) { diff = Math.abs(set1_p1 - set1_p2); max_points = Math.max(set1_p1,set1_p2); points_p1 = parseInt(set1_p1); points_p2 = parseInt(set1_p2); }
      if(i==2) { diff = Math.abs(set2_p1 - set2_p2); max_points = Math.max(set2_p1,set2_p2); points_p1 = parseInt(set2_p1); points_p2 = parseInt(set2_p2); }
      if(i==3) { diff = Math.abs(set3_p1 - set3_p2); max_points = Math.max(set3_p1,set3_p2); points_p1 = parseInt(set3_p1); points_p2 = parseInt(set3_p2); }

      //If nothing is inserted skip checks
      if(i==1 && max_points==0) { reset_result = true; break; }

      if(i==3 && wins_p1 - wins_p2 !=0)
      {
        if(set3_p1!=0 || set3_p2!=0)
        {
          error += '3. Satz wird nur bei unentschieden nach 2 Sätzen gespielt';
        }
        break;
      }

      if(max_points==11)
      {
        if(diff < 1)
        {
          error +=  'Punktedifferenz in Satz ' + i + ' nicht gültig';
          break;
        }
        else
        {
          if(points_p1>points_p2) { wins_p1++; }
          if(points_p1<points_p2) { wins_p2++; }
        }
      }
      else
      {
        error += 'Punkte in Satz ' + i + ' nicht gültig';
        break;
      }
    }
  }

  if(modus=='2setswinning')
  {
    for(var i=1;i<4;i++)
    {
      //Point differences and maxPoints
      var max_points = 0; var diff = 0;
      if(i==1) { diff = Math.abs(set1_p1 - set1_p2); max_points = Math.max(set1_p1,set1_p2); points_p1 = parseInt(set1_p1); points_p2 = parseInt(set1_p2); }
      if(i==2) { diff = Math.abs(set2_p1 - set2_p2); max_points = Math.max(set2_p1,set2_p2); points_p1 = parseInt(set2_p1); points_p2 = parseInt(set2_p2); }
      if(i==3) { diff = Math.abs(set3_p1 - set3_p2); max_points = Math.max(set3_p1,set3_p2); points_p1 = parseInt(set3_p1); points_p2 = parseInt(set3_p2); }

      //If nothing is inserted skip checks
      if(i==1 && max_points==0) { reset_result = true; break; }
      
      if(i==3 && wins_p1 - wins_p2 !=0)
      {
        if(set3_p1!=0 || set3_p2!=0)
        {
          error += '3. Satz wird nur bei unentschieden nach 2 Sätzen gespielt';
        }
        break;
      }

      if(diff < 1)
      {
        error +=  'Punktedifferenz in Satz ' + i + ' nicht gültig';
        break;
      }
      else
      {
        if(points_p1>points_p2) { wins_p1++; }
        if(points_p1<points_p2) { wins_p2++; }
      }
    }
  }

  if(error!='')
  {
    alert(error);
  }
  else
  {
    if(reset_result) {
      $('#court'+court).load(server_link+'&ajax=set_points_and_winner&court='+court+'&set1_p1=0&set1_p2=0&set2_p1=0&set2_p2=0&set3_p1=0&set3_p2=0');
    } else {
      $('#court'+court).load(server_link+'&ajax=set_points_and_winner&court='+court+'&set1_p1='+set1_p1+'&set1_p2='+set1_p2+'&set2_p1='+set2_p1+'&set2_p2='+set2_p2+'&set3_p1='+set3_p1+'&set3_p2='+set3_p2);
    }
  }
}

function change_group_by(id) {
  let sort_by = id.currentTarget.id;
  //$('#left_content').html("<img src='../inc/imgs/query/loading.gif' />");
  $('#left_content').load(server_link+'&ajax=get_all_users&order_by='+sort_by, function() {
    setEvents();
  });
}