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
      $("div[id^='div_location_']").hide();
      $('#myModal').show();
      $('.activated, .deactivated').on('click', (e) => {
          // e.currentTarget.id = "img_316_7"
          const fullId = e.currentTarget.id;         // "img_316_7"
          const mainId = fullId.split('_')[1];       // "316"
          toggle_activation(mainId);
      });
      $('.save_players').on('click', (e) => save_players(e.currentTarget.id.replace('save_players_','')));
      $('.location_select').on('click', (e) => change_location(e.currentTarget.id.replace('btn_location_','')));
      $('.location_select').first().click();
    });
}

function change_location(location_id)
{
  $('.location_select').removeClass('orange');
  $('#btn_location_' + location_id).addClass('orange');
  
  $("div[id^='div_location_']").hide();
  $('#div_location_' + location_id).show();
}

function save_players(journal_id)
{
  // IDs aus allen aktivierten Elementen sammeln
  let players = $('.activated')
      .map(function() {
          return $(this).attr('id').split('_')[1]; // nur die mittlere Zahl
      })
      .get(); // jQuery-Objekt in normales Array umwandeln

  // Duplikate entfernen und wieder zu String zusammenfügen
  let uniqueStr = [...new Set(players)].join(';');

  let my_url = 'index.php?ajax=save_players&players=' + uniqueStr + '&journal_id=' + journal_id
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
  $('[id^="img_' + img_id + '"]').each(function() {
    if ($(this).hasClass('activated')) {
      $(this).removeClass('activated'); 
      $(this).addClass('deactivated');
    } else {
      $(this).removeClass('deactivated'); 
      $(this).addClass('activated');
    }
  });
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
