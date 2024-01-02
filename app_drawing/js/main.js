var startX = null;
var startY = null;
var curr_func = null;
var endX = null;
var endY = null;
var curr_arrow_no = 1;
var curr_color = 'black';
var isDrawing = false;
var isErasing = false;
var curr_drawing_id = null;

$(function() { 
  $('#containment-wrapper').on('mousedown', function(e) { add_arrow(e); });
  init();
  update_file_infos();
  canvas = document.getElementById('canvas');
  context = canvas.getContext('2d');
  context.lineWidth = 2;
  $('#freehand').addClass('active');
  $('#color_' + curr_color).addClass('active');

  canvas.onmousedown = startDrawing;
  canvas.onmouseup = stopDrawing;
  canvas.onmousemove = draw;

});

function show_modal()
{
  deactivate_touch_events();
  $('#myModal').show();
}

function hide_modal()
{
  activate_touch_events();
  $('#myModal').hide();
}

function deactivate_touch_events()
{
  document.removeEventListener("touchstart", iPadTouchHandler, false);
  document.removeEventListener("touchmove", iPadTouchHandler, false);
  document.removeEventListener("touchend", iPadTouchHandler, false);
  document.removeEventListener("touchcancel", iPadTouchHandler, false);
}

function activate_touch_events()
{
  document.addEventListener("touchstart", iPadTouchHandler, false);
  document.addEventListener("touchmove", iPadTouchHandler, false);
  document.addEventListener("touchend", iPadTouchHandler, false);
  document.addEventListener("touchcancel", iPadTouchHandler, false);
}

function set_as_changed()
{
  $('#save_pic').text('Speichern');
  $('#save_pic').css('background-color','orange');
}

function change_arrow_no()
{
  curr_arrow_no = $('#arrow_no').val();
  start_arrow();
}

function change_background(without_change=false)
{
  if($('#bg_image').val() == 'Badmintonfeld') { $('#containment-wrapper')[0].style.backgroundImage="url('imgs/badminton_court.jpg')";  }
  if($('#bg_image').val() == 'Skizze') { $('#containment-wrapper')[0].style.backgroundImage="url('imgs/line_paper.png')";  }
  if(!without_change)
  {
    $('#save_pic').text('Speichern');
    $('#save_pic').css('background-color','orange');
  }
}

function change_color(color)
{
  $('#color_' + curr_color).removeClass('active');
  $('#color_' + color).addClass('active');
  curr_color = color;
  freehand()
}

function init()
{
  $('.draggable').draggable({ containment: '#containment-wrapper', scroll: false });
  $('.draggable').on('mouseup', function(e) { set_as_changed(); if(curr_func=='end') { curr_func='start'; }; });
  $('.draggable').on('dblclick', function(e)
  { 
    if(e.target.id != 'canvas') { $(this).remove(); }
  });
}

function add_player()
{
    var id = $('#user1').val();
    var my_url = 'index.php?ajax=get_pic_path&user_id=' + id;
    $.ajax({ url: my_url }).done(
      function(pic_path)
      {
        $('#containment-wrapper').append("<div id='" + id + "' style='position:absolute;left:100px;top:100px;' class='draggable' /><img style='width:120px;'  src='" + pic_path + "' /></div>");
        init();
        $('#save_pic').text('Speichern');
        $('#save_pic').css('background-color','orange');
      });
}
