function show_user_info(id)
{
  var my_url = 'index.php?ajax=show_user_info&user_id=' + id
  $.ajax({ url: my_url }).done(
    function(data)
    {
      $('#myModalText').html(data); 
      $('#myModal').show();
    });
}

function show_filter()
{
  var my_url = 'index.php?ajax=show_filter&location=' + $('#location_select').val();
  $.ajax({ url: my_url }).done(
    function(data)
    {
      $('#myModalText').html(data); 
      $('#myModal').show();
      $('#abort_button').on('click',function(){ $('#myModal').hide(); });
    });
}


function confirmed(exam_id,user_id,star_id,modus)
{
  if(modus=='add') { var my_url = 'index.php?ajax=add_exam&exam_id='+exam_id+'&user_id='+user_id; }
  if(modus=='remove') { var my_url = 'index.php?ajax=remove_exam&exam_id='+exam_id+'&user_id='+user_id; }
  if(my_url!='')
  {
    $.ajax({ url: my_url }).done(
      function(data)
      {
        $('#myModalText').html(data); 
        $('#myModal').hide();
        if(modus=='add')
        {
          $('#star_' + star_id).attr('src','../inc/imgs/star_full.png');
        }
        else
        {
          $('#star_' + star_id).attr('src','../inc/imgs/star_empty.png');
        }
      });

  }
  else
  {
    alert('Fehler');
  }

}

function aborted()
{
  $('#myModal').hide()
}

function show_conf(exam_id, user_id,star_id)
{
  var my_url = 'index.php?ajax=get_text_add_exam&exam_id='+exam_id+'&user_id='+user_id+'&star_id='+star_id;
  $.ajax({ url: my_url }).done(
  function(data)
  {
    $('#myModalText').html(data); 
    $('#myModal').show();
  });

}
