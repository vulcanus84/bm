//Event handlers
$(document).ready(function() {
  $('.add_entry').on('click', () => add_entry());
  $('.close').on('click', () => $('#myModal').hide());
  $('.col_trainer').on('click', (e) => edit_entry(e.currentTarget.id.replace('trainer_', '')));
  $('.col_player').on('click', (e) => edit_entry(e.currentTarget.id.replace('players_', '')));
  $('.col_opponent').on('click', (e) => edit_entry(e.currentTarget.id.replace('opponent_', '')));
  $('.col_text').on('click', (e) => edit_entry(e.currentTarget.id.replace('text_', '')));
  $('.col_delete').on('click', (e) => confirm_delete(e.currentTarget.id.replace('delete_', '')));
});


function change_location(location_id)
{
  $('.location_select').removeClass('orange');
  $('#btn_location_' + location_id).addClass('orange');
  
  $("div[id^='div_location_']").hide();
  $('#div_location_' + location_id).show();
}

function save_entry(ma_id)
{
  let trainer_id = null;
  let journal_date = $('#journal_date').val();
  let encoded_description = encodeURIComponent($('#training_description').val());

  $('.activated_trainer').each(function() {
    trainer_id = $(this).attr('id').replace('img_trainer_', '') + ';';
  });

  //Check Trainee
  var trainee_id = '';
  $('.activated_pl_loc').each(function() {
      var id = $(this).attr('id'); // z.B. "p_loc_42_1"
      var match = id.match(/^pl_loc_(\d+)_/); // fängt nur die Zahl nach "p_loc_"
      if (match) {
          trainee_id = match[1]; // nur "42"
          return false; // stoppt nach dem ersten Treffer
      }
  });
  var trainee_name =
      (trainee_id === '') 
          ? ($('#custom_name_pl_loc').length ? $('#custom_name_pl_loc').val() : '') 
          : '';

  //Check Trainee Partner
  var trainee_partner_id = '';
  $('.activated_plpa_loc').each(function() {
      var id = $(this).attr('id'); // z.B. "p_p_loc_42_1"
      var match = id.match(/^plpa_loc_(\d+)_/); // fängt nur die Zahl nach "p_p_loc_"
      if (match) {
          trainee_partner_id = match[1]; // nur "42"
          return false; // stoppt nach dem ersten Treffer
      }
  });
  var trainee_partner_name =
      (trainee_partner_id === '') 
          ? ($('#custom_name_plpa_loc').length ? $('#custom_name_plpa_loc').val() : '') 
          : '';

  //Check Opponent
  var opponent_id = '';
  $('.activated_op_loc').each(function() {
      var id = $(this).attr('id'); // z.B. "o_loc_42_1"
      var match = id.match(/^op_loc_(\d+)_/); // fängt nur die Zahl nach "p_loc_"
      if (match) {
          opponent_id = match[1]; // nur "42"
          return false; // stoppt nach dem ersten Treffer
      }
  });
  var opponent_name =
      (opponent_id === '') 
          ? ($('#custom_name_op_loc').length ? $('#custom_name_op_loc').val() : '') 
          : '';

  // Check Opponent Partner
  var opponent_partner_id = '';
  $('.activated_oppa_loc').each(function() {
      var id = $(this).attr('id'); // z.B. "o_p_loc_42_1"
      var match = id.match(/^oppa_loc_(\d+)_/); // fängt nur die Zahl nach "o_p_loc_"
      if (match) {
          opponent_partner_id = match[1]; // nur "42"
          return false; // stoppt nach dem ersten Treffer
      }
  });
  var opponent_partner_name =
      (opponent_partner_id === '') 
          ? ($('#custom_name_oppa_loc').length ? $('#custom_name_oppa_loc').val() : '') 
          : '';

  if(trainer_id== null) { alert('Bitte einen Trainer auswählen'); return; }
  if(trainee_id== '' && trainee_name == '') { alert('Bitte einen Spieler auswählen oder eingeben'); return; } 
  if(opponent_id== '' && opponent_name == '') { alert('Bitte einen Gegner auswählen oder eingeben'); return; }

  let my_url = 'index.php?ajax=save_entry&trainer_id=' + trainer_id + 
                    '&ma_id=' + ma_id + 
                    '&journal_date=' + journal_date + 
                    '&description=' + encoded_description + 
                    '&trainee_id=' + trainee_id + 
                    '&trainee_partner_id=' + trainee_partner_id + 
                    '&opponent_id=' + opponent_id + 
                    '&opponent_partner_id=' + opponent_partner_id + 
                    '&trainee_partner_name=' + trainee_partner_name + 
                    '&opponent_partner_name=' + opponent_partner_name +
                    '&trainee_name=' + trainee_name + 
                    '&opponent_name=' + opponent_name;
  $.ajax({ url: my_url }).done(
    function(data)
    {
      if(data!='') { alert(data); } else { location.reload(); }
    });
}

function edit_entry(ma_id) {
  let my_url = 'index.php?ajax=show_edit&ma_id=' + ma_id;
  $.ajax({ url: my_url }).done(
    function(data)
    {
      $('#myModalText').html(data); 
      $('#myModal').show();

      $('.activated_trainer, .deactivated_trainer').on('click', (e) => toggle_activation_trainer(e.currentTarget.id.replace('img_trainer_','')));

      $('.save_entry').on('click', (e) => save_entry(e.currentTarget.id.replace('save_entry_','')));
      $('.location_select').on('click', (e) => change_location(e.currentTarget.id.replace('btn_location_','')));
      $('.location_select').first().click();
      
      $('#pl_loc').on('change', (e) => show_details(ma_id,'pl_loc',e.target.value)); 
      $('#plpa_loc').on('change', (e) => show_details(ma_id,'plpa_loc',e.target.value)); 
      $('#op_loc').on('change', (e) => show_details(ma_id,'op_loc',e.target.value)); 
      $('#oppa_loc').on('change', (e) => show_details(ma_id,'oppa_loc',e.target.value)); 
      $('#pl_loc, #plpa_loc, #op_loc, #oppa_loc').trigger('change');
    });
}

function show_details(ma_id, section, location_id) {
  $.ajax({ url: 'index.php?ajax=show_location_details&ma_id=' + ma_id + '&section=' + section + '&location_id=' + location_id }).done(
    function(data)
    {
      const target = "#" + section.replace("_loc", "_div");
      $(target).html(data);
      $('.activated_' + section +', .deactivated_' + section).off('click');
      $('.activated_' + section +', .deactivated_' + section).on('click', (e) => toggle_activation(section,e.currentTarget.id.replace(section + '_','')));
    });
}

function add_entry()
{
  edit_entry(0);
}
function delete_entry(id)
{
  let my_url = 'index.php?ajax=delete_entry&ma_id=' + id
  $.ajax({ url: my_url }).done(
    function(data)
    {
      location.reload();
    });
}

function confirm_delete(id)
{
  let my_url = 'index.php?ajax=confirm_delete&ma_id=' + id
  $.ajax({ url: my_url }).done(
    function(data)
    {
      $('#myModalText').html(data); 
      $('#myModal').show();
      $('.delete').on('click', (e) => delete_entry(e.currentTarget.id.replace('delete_','')));
      $('.abort_delete').on('click', (e) => $('#myModal').hide());
    });
}

function toggle_activation(prefix, img_id)
{
  //Remove activated from all items and add deactivated
  $("div[id^='" + prefix + "_']").removeClass('activated_' + prefix);
  $("div[id^='" + prefix + "_']").addClass('deactivated_' + prefix);
  
  //Remove deactivated from selected item and add activated
  $('#' + prefix + '_' + img_id).removeClass('deactivated_' + prefix);
  $('#' + prefix + '_' + img_id).addClass('activated_' + prefix);
}

function toggle_activation_trainer(img_id)
{
  //Remove activated from all items and add deactivated
  $("div[id^='img_trainer_']").removeClass('activated_trainer');
  $("div[id^='img_trainer_']").addClass('deactivated_trainer');
  
  //Remove deactivated from selected item and add activated
  $('#img_trainer_' + img_id).removeClass('deactivated_trainer');
  $('#img_trainer_' + img_id).addClass('activated_trainer');
}
