function filter_user(user_id)
{
  var my_url = 'index.php?ajax=get_excercises&user_id=' + user_id;
  $.ajax({ url: my_url }).done(
    function(data)
    {
      $('#excersises').html(data); 
    });  
}

