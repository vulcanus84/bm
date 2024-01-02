function save_pic()
{
  const players = [];
  $( "div.draggable" ).each(function( index ) {
    let player = { posX : $(this).css('left') , posY : $( this ).css('top'), id : $(this).attr('id') };
    players.unshift(player);
  });
  const json = JSON.stringify(players);
  var dataURL = document.getElementById('canvas').toDataURL();
  $.ajax({
    type: 'POST',
    url: 'save_image.php',
    data: { 
      dataURL: dataURL,
      drawing_id: curr_drawing_id,
      bg_image : $('#bg_image').val(),
      players: json
    }
  }).done(function(o) {
    curr_drawing_id = o;
    update_file_infos();
  });
}

function load_pic(path,id)
{
  hide_modal();
  curr_drawing_id = id;
  var canvas = document.getElementById('canvas');
  if (canvas.getContext) {

      ctx = canvas.getContext('2d');

      //Loading of the home test image - img1
      var img1 = new Image();

      //drawing of the test image - img1
      img1.onload = function () {
          //draw background image
          ctx.clearRect(0, 0, canvas.width, canvas.height);
          curr_arrow_no = 1;
          ctx.globalCompositeOperation='source-over';
          ctx.drawImage(img1, 0, 0);
      };
      img1.src = path;
  }

  load_excercise_details(id)
  load_draggables(id);
  update_file_infos();
  curr_arrow_no = 1;
  $('#arrow_no').val(curr_arrow_no);
}

function load_excercise_details(excercise_id)
{
  var my_url = 'index.php?ajax=get_excercise_details&excercise_id='+ excercise_id;
  $.ajax({ url: my_url }).done(
    function(data)
    {
      var jsonData = JSON.parse(data);
      $('#bg_image').val(jsonData.bg_image);
      change_background(true);
    });
}


function load_draggables(excercise_id)
{
  var my_url = 'index.php?ajax=get_draggables&excercise_id='+ excercise_id;
  $.ajax({ url: my_url }).done(
    function(data)
    {
      var jsonData = JSON.parse(data);
      for (var i = 0; i < jsonData.length; i++) {
          var obj = jsonData[i];
          var my_url = 'index.php?ajax=get_pic_path&user_id=' + obj.user_id + '&x=' + obj.posx + '&y=' + obj.posy;
          $.ajax({ url: my_url }).done(
            function(data)
            {
              var obj = $.parseJSON(data)
              $('#containment-wrapper').append("<div id='" + obj[3] + "' style='position:absolute;left:" + obj[1] + "px;top:" + obj[2] + "px;' class='draggable'/><img style='width:120px;' src='" + obj[0] + "' /></div>");
              init();
            });
      }
    });
}


function del_pic()
{
  var canvas = document.getElementById('canvas');
  var ctx = canvas.getContext('2d');
  ctx.clearRect(0, 0, canvas.width, canvas.height);
  curr_arrow_no = 1;
  $('#arrow_no').val(curr_arrow_no);
  $('#add_arrow_btn').removeClass('active');
  $('#erase').removeClass('active');
  $('#freehand').addClass('active');
}

function show_pics()
{
  if(curr_drawing_id>0)
  {
    //Close drawing
    curr_drawing_id = null;
    del_pic();
    update_file_infos();
    remove_draggables();
    curr_func = null;
  }
  else
  {
    var my_url = 'index.php?ajax=load_pictures';
    $.ajax({ url: my_url }).done(
      function(data)
      {
        $('#myModalText').html(data); 
        show_modal();
      });
    }
}
function del_from_db()
{
  $.ajax({
    type: 'POST',
    url: 'index.php?ajax=del_from_db',
    data: { 
       id: curr_drawing_id
    }
  }).done(function() {
    hide_modal();
    curr_drawing_id = null;
    del_pic();
    remove_draggables();
    update_file_infos();
  });
}

function show_del_warning()
{
  var my_url = 'index.php?ajax=del_warning';
  $.ajax({ url: my_url }).done(
    function(data)
    {
      $('#myModalText').html(data); 
      show_modal();
    });
}

function update_file_infos()
{
  if(curr_drawing_id > 0)
  {
    $('#save_pic').text('Gespeichert');
    $('#load_pic').text('Schliessen');
    $('#save_pic').css('background-color','green');
    $('#del_pic').show();
  }
  else
  {
    $('#del_pic').hide();
    $('#save_pic').text('Speichern');
    $('#load_pic').text('Laden');
    $('#save_pic').css('background-color','orange');
  }
}

function remove_draggables()
{
  $('div').remove('.draggable');
}



