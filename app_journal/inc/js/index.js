//Event handlers
$(document).ready(function() {
  $('.add_entry').on('click', () => add_entry());
  $('.close').on('click', () => $('#myModal').hide());
  $('.col_trainer').on('click', (e) => edit_trainer(e.currentTarget.id.replace('trainer_', '')));
  $('.col_player').on('click', (e) => edit_players(e.currentTarget.id.replace('players_', '')));
  $('.col_text').on('click', (e) => edit_text(e.currentTarget.id.replace('text_', '')));
  $('.col_delete').on('click', (e) => confirm_delete(e.currentTarget.id.replace('delete_', '')));
});

function edit_text(id)
{
  let my_url = 'index.php?ajax=show_text&journal_id=' + id
  $.ajax({ url: my_url }).done(
    function(data)
    {
      $('#myModalText').html(data); 
      $('#myModal').show();
      $('.save_text').on('click', (e) => save_text(e.currentTarget.id.replace('save_text_','')));
    });
}

function save_text(id)
{
  let encoded_text = encodeURIComponent($('#training_description').val());
  let my_url = 'index.php?ajax=save_text&journal_id=' + id + '&text=' + encoded_text
  $.ajax({ url: my_url }).done(
    function()
    {
      location.reload();
    });
}

function edit_players(id)
{
  let my_url = 'index.php?ajax=show_players&journal_id=' + id
  $.ajax({ url: my_url }).done(
    function(data)
    {
      $('#myModalText').html(data); 
      $('#myModal').show();
      $('.activated').on('click', (e) => toggle_activation(e.currentTarget.id.replace('img_','')));
      $('.deactivated').on('click', (e) => toggle_activation(e.currentTarget.id.replace('img_','')));
      $('.save_players').on('click', (e) => save_players(e.currentTarget.id.replace('save_players_','')));
    });
}
function save_players(journal_id)
{
  let players = '';
  $('.activated').each(function() {
      players = players + $(this).attr('id').replace('img_','') + ';';
  });
  let my_url = 'index.php?ajax=save_players&players=' + players + '&journal_id=' + journal_id
  $.ajax({ url: my_url }).done(
    function(data)
    {
      location.reload();
    });
}

function edit_trainer(id)
{
  let my_url = 'index.php?ajax=show_trainer&journal_id=' + id
  $.ajax({ url: my_url }).done(
    function(data)
    {
      $('#myModalText').html(data); 
      $('#myModal').show();
      $('.activated').on('click', (e) => change_activation(e.currentTarget.id.replace('img_','')));
      $('.deactivated').on('click', (e) => change_activation(e.currentTarget.id.replace('img_','')));
      $('.save_trainer').on('click', (e) => save_trainer(e.currentTarget.id.replace('save_trainer_','')));
    });
}

function save_trainer(journal_id)
{
  let trainer_id = null;
  $('.activated').each(function() {
    trainer_id = $(this).attr('id').replace('img_', '') + ';';
  });
  let journal_date = $('#journal_date').val();
  let my_url = 'index.php?ajax=save_trainer&trainer_id=' + trainer_id + '&journal_id=' + journal_id + '&journal_date=' + journal_date
  $.ajax({ url: my_url }).done(
    function(data)
    {
      location.reload();
    });
}

function add_entry()
{
  let my_url = 'index.php?ajax=add_entry';
  $.ajax({ url: my_url }).done(
    function(data)
    {
      location.reload();
    });
}

function delete_entry(id)
{
  let my_url = 'index.php?ajax=delete_entry&journal_id=' + id
  $.ajax({ url: my_url }).done(
    function(data)
    {
      location.reload();
    });
}

function confirm_delete(id)
{
  let my_url = 'index.php?ajax=confirm_delete&journal_id=' + id
  $.ajax({ url: my_url }).done(
    function(data)
    {
      $('#myModalText').html(data); 
      $('#myModal').show();
      $('.delete').on('click', (e) => delete_entry(e.currentTarget.id.replace('delete_','')));
      $('.abort_delete').on('click', (e) => $('#myModal').hide());
    });
}

function toggle_activation(img_id)
{
  if($('#img_' + img_id).hasClass('activated')) { $('#img_' + img_id).removeClass('activated'); $('#img_' + img_id).addClass('deactivated'); }
  else
  {
    if($('#img_' + img_id).hasClass('deactivated')) { $('#img_' + img_id).removeClass('deactivated'); $('#img_' + img_id).addClass('activated'); }
  }
}

function change_activation(img_id)
{
  //Remove activated from all items and add deactivated
  $("div[id^='img_']").removeClass('activated');
  $("div[id^='img_']").addClass('deactivated');
  
  //Remove deactivated from selected item and add activated
  $('#img_' + img_id).removeClass('deactivated');
  $('#img_' + img_id).addClass('activated');
}
