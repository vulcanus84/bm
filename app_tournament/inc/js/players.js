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
  playerId = $('#content').data('player-id');
  if(playerId>0) {
    $('#right_col').load(server_link+'&ajax=show_infos');
  }
  setEvents();
});

function setEvents() {
  const allSections = $('#left_content section'); // alle Gruppen (z.B. BCZ 1, BCZ 2 etc.)
  // Standort-Filter
  $('select[name="location"]').off('change').on('change', function() {
    const selected = $(this).val();
    allSections.hide(); // alle verstecken

    if (!selected) {
      allSections.show(); // alles zeigen wenn "-- Alle Standorte --" gewählt
    } else {
      allSections.each(function() {
        if ($(this).find('h1').text().includes($(this).find('h1').text().split('(')[0].trim())) {
          $(this).toggle($(this).find('h1').text().includes($('select[name="location"] option:selected').text().trim()));
        }
      });
    }
  });

  //Remove click event from main part with the delegations
  $('#content').off('click');

  $('#left_col').off('click').on('click', function(e) {
    if (!$(e.target).closest('.user_mit_name, button, select, a, .dropdown,img').length) {
      $(this).toggleClass('open');
    }
  });
  $('#content').on('click','div.user_mit_name', (e) => show_infos(e.currentTarget.id));

  $('img.img_sort').off('click').on('click', (e) => change_group_by(e));

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


function new_user() {
  $('#right_col').load(server_link+'&ajax=new_user');
}


function show_infos(user_tag_id) {
  var user_id = user_tag_id.replace(/user/g, "");
  $('#right_col').load(server_link+'&ajax=show_infos&user_id='+user_id);
  $('#left_col').toggleClass('open');
}

function show_history(user_id) {
  $('#right_col').load(server_link+'&ajax=show_history&user_id='+user_id);
}


function delete_permission(user_id) {
  $('#right_col').load(server_link+'&ajax=delete_permission_user&user_id='+user_id);
}

function delete_user(user_id)
{
  var my_url = server_link +'&ajax=delete_user&user_id=' + user_id;
  $.ajax({ url: my_url }).done(
  function(data)
  {
    window.location = base_link;
  });
}

function delete_pic(user_id)
{
  var my_url = server_link +'&ajax=delete_pic&user_id=' + user_id;
  $.ajax(my_url).done(
  function(data)
  {
    $('#right_col').load(server_link + '&ajax=show_infos&user_id='+user_id);
    $('#left_col').load(server_link + '&ajax=show_left_col');
  });
}


function change_group_by(id) {
  let sort_by = id.currentTarget.id;
  //$('#left_content').html("<img src='../inc/imgs/query/loading.gif' />");
  $('#left_content').load(server_link+'&ajax=get_all_users&order_by='+sort_by, function() {
    setEvents();
  });
}