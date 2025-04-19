$(document).ready(function() {
  tournamentId = $('#content').data('tournament-id');
  $('.user_mit_name').on('click', (e) => perform_ajax('add_as_partner','user_id='+e.currentTarget.id));
});

var tournamentId;

var server_link = String(window.location);
if(!server_link.includes('?')) { server_link = server_link + '?x=y'; }

const queryString = window.location.search;
const params = new URLSearchParams(queryString);

const urlParams = {};
for (const [key, value] of params) {
  urlParams[key] = value;
}

function get_tournament_form(tournament_id)
{
  $('#right_col').load(server_link+'&ajax=get_tournament_form&tournament_id='+tournament_id);
}

function delete_tournament(tournament_id)
{
  $('#right_col').load(server_link+'&ajax=delete_permission&tournament_id='+tournament_id);
}

function check_result(court,game_id)
{
  $('#court'+court).load(server_link+'&ajax=get_result&game_id='+game_id+'&court='+court);
}

function set_winner(user_id,court)
{
  $('#court'+court).load(server_link+'&ajax=set_winner&tournament_id=$_GET[tournament_id]&round=$_GET[round]&winner_id='+user_id+'&court_id='+court,
  function()
  {
    $('#left_col').load(server_link+'&ajax=get_left_col&tournament_id=$_GET[tournament_id]&round=$_GET[round]');
  });
}

function set_points_and_winner(modus,court,set1_p1,set1_p2,set2_p1=0,set2_p2=0,set3_p1=0,set3_p2=0)
{
  //Check result
  var error = '';
  var wins_p1=0;
  var wins_p2=0;
  var points_p1=0;
  var points_p2=0;

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

      if(i==3 && wins_p1 - wins_p2 !=0)
      {
        if(set3_p1!=0 || set3_p2!=0)
        {
          error += '3. Satz wird nur bei unentschieden nach 2 Sätzen gespielt';
        }
        break;
      }

      if(i==1 && max_points==0)
      {
        $('#court'+court).load(server_link+'&ajax=show&tournament_id=$_GET[tournament_id]&round=$_GET[round]&court_id='+court);
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

      if(i==3 && wins_p1 - wins_p2 !=0)
      {
        if(set3_p1!=0 || set3_p2!=0)
        {
          error += '3. Satz wird nur bei unentschieden nach 2 Sätzen gespielt';
        }
        break;
      }

      if(i==1 && max_points==0)
      {
        $('#court'+court).load(server_link+'&ajax=show&tournament_id=$_GET[tournament_id]&round=$_GET[round]&court_id='+court);
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

      if(i==3 && wins_p1 - wins_p2 !=0)
      {
        if(set3_p1!=0 || set3_p2!=0)
        {
          error += '3. Satz wird nur bei unentschieden nach 2 Sätzen gespielt';
        }
        break;
      }

      if(i==1 && max_points==0)
      {
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
    $('#court'+court).load(server_link+'&ajax=set_points_and_winner&tournament_id=$_GET[tournament_id]&round=$_GET[round]&court_id='+court+'&set1_p1='+set1_p1+'&set1_p2='+set1_p2+'&set2_p1='+set2_p1+'&set2_p2='+set2_p2+'&set3_p1='+set3_p1+'&set3_p2='+set3_p2,
    function()
    {
      $('#left_col').load(server_link+'&ajax=get_left_col&tournament_id=$_GET[tournament_id]&round=$_GET[round]');
    });
  }
}


function clear_it()
{
  if (confirm('Bist du sicher, dass du die Auslosung löschen wilst? \\n\\n(alle eingetragen Spiele der aktuellen Runde und die Auslosungen werden gelöscht)'))
  {
    var i = 1;
    for(i;i<$anz_felder+1;i++)
    {
      $('#court'+i).load(server_link+'&ajax=clear&tournament_id=$_GET[tournament_id]&round=$_GET[round]');
    }
    $('#loeschen').hide();
    $('#runde_schliessen').hide();
    $('#auslosen').show();
    $('#runde_starten').hide();
  }
}

function define_games()
{
  $.ajax({ url: server_link+'&ajax=define_games&tournament_id='+tournamentId+'&round=1'  }).done(
  function(data)
  {
    if(data=='OK')
    {
      var i = 1;
      for(i;i<$anz_felder+1;i++)
      {
        do_it(i);
      }
      $('#loeschen').show();
      $('#runde_schliessen').show();
      $('#auslosen').hide();
      $('#runde_starten').show();
    }
    else
    {
      alert(data);
    }
  });
}

function do_it(court_no)
{
  var delay = court_no*500;
  $('#court'+court_no).load(server_link+'&ajax=load',
  function()
  {
    $('#court'+court_no).delay(1000).fadeTo(delay,1,
    function (data)
    {
      $('#court'+court_no).load(server_link+'&ajax=show&tournament_id=$_GET[tournament_id]&round=$_GET[round]&court_id='+court_no);
    });
  });
}
function close_round()
{
  var my_url = server_link+'&ajax=close_round&tournament_id=$_GET[tournament_id]&round=$_GET[round]';
  $.ajax({ url: my_url }).done(
  function(data)
  {
    if(data.substring(0, 2)=='OK')
    {
      var myNewUrl = server_link+'';
      myNewUrl = myNewUrl.replace('round=$_GET[round]','round='+ data.substring(3));
      window.location = myNewUrl;
    }
    else
    {
      alert(data);
    }
  });
}

function reset_round(round_nr)
{
  var my_url = server_link+'&ajax=reset_round&tournament_id=$_GET[tournament_id]&round='+round_nr;
  $.ajax({ url: my_url }).done(
  function(data)
  {
    if(data.substring(0, 2)=='OK')
    {
      window.location = server_link+'';
    }
    else
    {
      alert(data);
    }
  });
}

function stopp_tournament()
{
  if (confirm('Bist du sicher, dass du das Turnier abbrechen wilst? \\n\\n(alle bisherigen Spiele und Partner werden gelöscht, die zugewiesen Spieler und Setzlisten bleiben erhalten)'))
  {
    var my_url = server_link+'&ajax=stopp_tournament&tournament_id=$_GET[tournament_id]';
    $.ajax({ url: my_url }).done(
    function(data)
    {
      window.location = server_link+'';
    });
  }
}

function update_number_of_player_in_sections()
{
  let left_col = $('#left_col');
  let sections = left_col.find('section');

  sections.each(function(index, element) {
  let $section = $(element); // DOM → jQuery

  let count = $section.find('.user_mit_name').length;
  let $h1 = $section.find('h1');

  $h1.text(function(_, oldText) {
    return oldText.replace(/\(\d+\)/, `(${count})`);
  });
});
}

function add_user(user_id)
{
  var pos = $('#user'+user_id).parent().closest('div')[0].id;
  if(pos=='left_col')
  {
    var my_url = server_link+'&ajax=add_user&user_id=' + user_id;
    $.ajax({ url: my_url }).done(
    function(data)
    {
      let elem = $('#user'+user_id).clone();

      //No clean HTML I know, ID's should be unique but with the same person in different locations same ID's appear
      const elements = document.querySelectorAll('#user'+user_id); // Alle Elemente mit dieser ID finden
      elements.forEach(element => element.remove()); 

      update_number_of_player_in_sections();
      $('#right_col').append(elem);
    });
  }
  else
  {
    var pos = $('#user'+user_id).parent().closest('div')[0].id;
    var my_url = server_link+'&ajax=remove_user&user_id=' + user_id;
    $.ajax({ url: my_url }).done(
    function(data)
    {
      const items = data.trim().split(',');
      let elem = $('#user'+user_id).clone();
      $('#user'+user_id).remove();

      items.forEach(item => {
        let elem2 = elem.clone();
        $('#section_'+ item).append(elem2);
        let $container = $('#section_'+item);

        let $items = $container.find('.user_mit_name').get();        
        $items.sort(function(a, b) {
          let altA = $(a).find('img').attr('alt')?.toLowerCase() || '';
          let altB = $(b).find('img').attr('alt')?.toLowerCase() || '';
          return altA.localeCompare(altB);
        });
        
        $container.append($items);
      });
      update_number_of_player_in_sections();
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

function define_seeded_players()
{
  var my_url = server_link+'&ajax=define_seeded_players&tournament_id=$_GET[tournament_id]';
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

function start_tournament(id)
{
  var my_url = server_link+'&ajax=start_tournament&tournament_id='+id;
  $.ajax({ url: my_url }).done(
  function(data)
  {
    if(data=='OK')
    {
      window.location = server_link+'&round=1';
    }
    else
    {
      alert(data);
    }
  });
}

function close_tournament(id)
{
  var my_url = server_link+'&ajax=close_tournament&tournament_id='+id;
  $.ajax({ url: my_url }).done(
  function(data)
  {
    if(data.substring(0, 2)=='OK')
    {
      window.location = server_link+'';
    }
    else
    {
      alert(data);
    }
  });
}

function show_user_games(user_id)
{
  $('#right_col').load(server_link+'&ajax=show_user_info&tournament_id=$_GET[tournament_id]&user_id='+user_id);
}

function add_as_seeded(user_id)
{
  var my_url = server_link+'&ajax=add_as_seeded&tournament_id=$_GET[tournament_id]&user_id='+user_id;
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

function delete_last_seeding()
{
  var my_url = server_link+'&ajax=delete_last_seeding&tournament_id=$_GET[tournament_id]';
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

function add_as_partner(user_id)
{
  var my_url = server_link+'&ajax=add_as_partner&tournament_id='+tournamentId+'&user_id='+user_id;
  $.ajax({ url: my_url }).done(
  function(data)
  {
    if(data=='OK') { window.location = server_link; } else { alert(data); }
  });
}

function delete_team(user_id)
{
  var my_url = server_link+'&ajax=delete_team&tournament_id='+tournamentId+'&user_id='+user_id;
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


function perform_ajax(function_name,param_url,target_id=null) 
{
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
