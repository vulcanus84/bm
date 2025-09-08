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
  playerId = $('#content').data('user-id');
  if(playerId>0) {
    $('#right_col').load(server_link+'&ajax=show_infos');
  }
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


  $('#left_col').off('click').on('click', function(e) {
    if (!$(e.target).closest('.user_pic, button, select, a, .dropdown,img').length) {
      $(this).toggleClass('open');
    }
  });
  $('#content').on('click','div.user_pic', (e) => show_infos(e.currentTarget.id));

  $('img.img_sort').off('click').on('click', (e) => change_group_by(e));
  
  $('#content').on('submit','form#new_user', function(e) {
    e.preventDefault(); // Formular nicht normal absenden
  
    let formData = new FormData(this); // FormData aus Formular

    //Check Formular-Data
    const checkboxes = document.querySelectorAll('#new_user input[type="checkbox"][name^="loc_"]');
    let checked = false;

    checkboxes.forEach(box => {
        if (box.checked) {
            checked = true;
        }
    });

    var oldVal = $('select[name="location"]').val();

    if (!checked) {
        alert("Bitte mindestens einen Trainingsort auswählen!");
    } else {
      $.ajax({
        url: server_link+'&ajax=save_user',
        type: 'POST',
        data: formData,
        processData: false, // wichtig bei FormData
        contentType: false, // ebenfalls wichtig
        success: function(response) {
          if (!isNaN(response) && response.trim() !== '') 
          {
            $('#left_content').load(server_link+'&ajax=get_left_col_users', function() {
              $('select[name="location"]').val(oldVal);
              $('select[name="location"]').trigger('change');
              show_infos('user_' + response,true);
            });
          } 
          else 
          {
            $('#right_content').html(response);
          }
        },
        error: function(xhr, status, error) {
          $('#right_content').html(error);
        }
      });
    }
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

function new_user() {
  $('#right_content').load(server_link+'&ajax=new_user');
}

function trigger_upload_pic_selection(id)
{
  $('#inpPicture').trigger('click');
}

function show_infos(user_tag_id,pic_replace=false) {
  var user_id = user_tag_id.replace(/user_/g, "");
  $('#right_content').load(server_link+'&ajax=show_infos&user_id='+user_id, function() {
    if(pic_replace) {
      let neuesSrc = $('#user_pic_large').attr('src');
      $('#'+user_tag_id + ' img').attr('src', neuesSrc);
    }
  });
  $('#left_col').toggleClass('open');
}

function show_history(user_id) {
  $('#right_content').load(server_link+'&ajax=show_history&user_id='+user_id);
}

function delete_permission(user_id) {
  $('#right_content').load(server_link+'&ajax=delete_permission_user&user_id='+user_id);
}

function delete_user(user_id)
{
  var my_url = server_link +'&ajax=delete_user&user_id=' + user_id;
  var oldVal = $('select[name="location"]').val();
  $.ajax({ url: my_url }).done(
  function(data)
  {
    $('#right_content').html("");
    $('#left_content').load(server_link+'&ajax=get_left_col_users', function() {
      $('select[name="location"]').val(oldVal);
      $('select[name="location"]').trigger('change');
    });
});
}

function delete_pic(user_id)
{
  var my_url = server_link +'&ajax=delete_pic&user_id=' + user_id;
  var oldVal = $('select[name="location"]').val();
  $.ajax(my_url).done(
  function(data)
  {
    $('#right_content').load(server_link + '&ajax=show_infos&user_id='+user_id);
    $('#left_content').load(server_link+'&ajax=get_left_col_users', function() {
      $('select[name="location"]').val(oldVal);
      $('select[name="location"]').trigger('change');
    });
  });
}

function change_group_by(id) {
  let sort_by = id.currentTarget.id;
  //$('#left_content').html("<img src='../inc/imgs/query/loading.gif' />");
  $('#left_content').load(server_link+'&ajax=get_all_users&order_by='+sort_by, function() {
    setEvents();
  });
}