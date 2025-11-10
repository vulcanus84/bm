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
  let trainee_id = null;
  $('.activated_trainer').each(function() {
    trainer_id = $(this).attr('id').replace('img_trainer_', '') + ';';
  });
  $('.activated').each(function() {
    trainee_id = $(this).attr('id').replace(/^img_([^_]+).*$/, '$1') + ';';
  });
  let journal_date = $('#journal_date').val();
  let encoded_description = encodeURIComponent($('#training_description').val());
  let encoded_opponent = encodeURIComponent($('#opponent_name').val());

  if(trainer_id== null) { alert('Bitte einen Trainer auswählen'); return; }
  if(trainee_id== null) { alert('Bitte einen Spieler auswählen'); return; } 
  if(encoded_opponent=='' ) { alert('Bitte den Gegnernamen eingeben'); return; }

  let my_url = 'index.php?ajax=save_entry&trainer_id=' + trainer_id + '&ma_id=' + ma_id + '&journal_date=' + journal_date + '&description=' + encoded_description + '&trainee_id=' + trainee_id + '&opponent_name=' + encoded_opponent
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
      $('.activated, .deactivated').on('click', (e) => toggle_activation_trainee(e.currentTarget.id.replace('img_','')));

      $('.save_entry').on('click', (e) => save_entry(e.currentTarget.id.replace('save_entry_','')));
      $('.location_select').on('click', (e) => change_location(e.currentTarget.id.replace('btn_location_','')));
      $('.location_select').first().click();

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

function toggle_activation_trainee(img_id)
{
  //Remove activated from all items and add deactivated
  $("div[id^='img_']").removeClass('activated');
  $("div[id^='img_']").addClass('deactivated');
  
  //Remove deactivated from selected item and add activated
  $('#img_' + img_id).removeClass('deactivated');
  $('#img_' + img_id).addClass('activated');
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
