var startX = null;
var startY = null;
var curr_arrow_func = null;
var endX = null;
var endY = null;
var curr_arrow_no = 1;
var curr_color = 'black';
var isDrawing = false;
var isErasing = false;
var curr_drawing_id = null;
var isChanged = false;
var curr_edit_mode = null;
var drag_allowed = true;

const backgrounds = [
  {
    'name' : 'Badmintonfeld',
    'path' : 'imgs/bg_badminton_court.jpg'
  },
  {
    'name' : '3D Badmintonfeld',
    'path' : 'imgs/bg_court_3d.jpg'
  },
  {
    'name' : 'Liniert',
    'path' : 'imgs/bg_line_paper.png'
  },
  {
    'name' : 'Weiss',
    'path' : 'imgs/bg_white_paper.png'
  }
];

$(function() { 
  init();
  update_file_infos();
  $('#color_' + curr_color).addClass('active');
  set_edit_mode('freehand');
  document.getElementById('cameraInput').onchange = function(e) {
    readFile(e.srcElement.files[0]);
  };
  backgrounds.forEach(fill_bg_select);  
  change_background(backgrounds[0].name);
});

function fill_bg_select(item)
{
    var opt = document.createElement("option");
    opt.value= item.name;
    opt.innerHTML = item.name; // whatever property it has

    // then append it to the select element
    $('#select_bg').append(opt);
}

function set_edit_mode(mode = 'player')
{
  //remove active class from all functions
  $('#player').removeClass('active');
  $('#freehand').removeClass('active');
  $('#erase').removeClass('active');
  $('#text').removeClass('active');
  $('#arrow').removeClass('active');
  curr_arrow_func = null;
  curr_edit_mode = mode;
  $('#arrow_no_picker').hide();
  $('#player_picker').hide();
  $('#color_picker').hide();

  canvas.removeEventListener("mousedown", startDrawing, false);
  canvas.removeEventListener("mouseup", stopDrawing, false);
  canvas.removeEventListener("mousemove", draw, false);
  canvas.removeEventListener("mousedown", add_arrow, false);
  canvas.removeEventListener("mousedown", startTextBox, false);

  //add active class to current function
  switch(mode) {
    case 'player':
      $('#player').addClass('active');
      $('#player_picker').show();
      break;
    case 'freehand':
      canvas.addEventListener("mousedown", startDrawing, false);
      canvas.addEventListener("mouseup", stopDrawing, false);
      canvas.addEventListener("mousemove", draw, false);
      $('#freehand').addClass('active');
      $('#color_picker').show();
      break;
    case 'erase':
      canvas.addEventListener("mousedown", startDrawing, false);
      canvas.addEventListener("mouseup", stopDrawing, false);
      canvas.addEventListener("mousemove", draw, false);
      $('#erase').addClass('active');
      $('#color_picker').show();
      break;
    case 'text':
      canvas.addEventListener("mousedown", startTextBox, false);
      $('#text').addClass('active');
      $('#color_picker').show();
      break;
    case 'arrow':
      canvas.addEventListener("mousedown", add_arrow, false);
      $('#arrow').addClass('active');
      $('#color_picker').show();
      $('#arrow_no_picker').show();
      start_arrow();
      break;
  }
}

function show_modal()
{
  deactivate_touch_events();
  $('#myModal').show();
  drag_allowed =false;
}

function hide_modal()
{
  activate_touch_events();
  $('#myModal').hide();
  drag_allowed =true;
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
  isChanged = true;
  update_file_infos();
}

function change_arrow_no()
{
  curr_arrow_no = $('#arrow_no').val();
  start_arrow();
}

function change_background(without_change=false)
{
  backgrounds.forEach(
    function(item)
    { 
      if(item.name==$('#select_bg').val()) 
      { 
        $('#containment-wrapper')[0].style.backgroundImage="url('" + item.path + "')";  
      }
    });
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
  if(curr_edit_mode=='erase') { set_edit_mode('freehand'); }
}

function init()
{
  $('.draggable').draggable({ containment: '#containment-wrapper', scroll: false, drag: function() { if(!drag_allowed) { return false; } } });
  $('.draggable').on('mouseup', function(e) { set_as_changed(); if(curr_arrow_func=='end') { curr_arrow_func='start'; }; });
  $('.draggable.player').on('dblclick', function(e) {  $(this).remove(); });

  $('.draggable.texts').on('dblclick', function(e)
  {
    $('#myModalText').html(get_modal_txt_for_textfield(e.target.id)); 
    show_modal();
  });

  $('.draggable.texts').find('span').on('dblclick', function(e)
  {
    $('#myModalText').html(get_modal_txt_for_textfield(e.target.parentNode.id)); 
    show_modal();
  });
  $('.draggable.images').find('img').on('click', function(e)
  {
    $('#'+e.target.parentNode.id).height($('#'+e.target.id).height());
  });

  $('.draggable.images').find('img').on('dblclick', function(e)
  {
    $('#'+e.target.parentNode.id).width($('#'+e.target.id).width());
    $('#myModalText').html(get_modal_txt_for_img(e.target.parentNode.id)); 
    show_modal();
  });

  canvas = document.getElementById('canvas');
  context = canvas.getContext('2d');
  context.lineWidth = 2;
}

function add_player()
{
    var id = $('#user1').val();
    if(id>0)
    {
      var my_url = 'index.php?ajax=get_pic_path&user_id=' + id;
      $.ajax({ url: my_url }).done(
        function(pic_path)
        {
          $('#containment-wrapper').append("<div id='" + id + "' style='position:absolute;left:100px;top:100px;' class='draggable player' /><img style='width:120px;'  src='" + pic_path + "' /></div>");
          init();
          set_as_changed();
        });
    }
}
