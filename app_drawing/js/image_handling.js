var curr_img_no = 1;

function get_image_library()
{
  var my_url = 'index.php?ajax=get_image_library';
  $.ajax({ url: my_url }).done(
    function(data)
    {
      console.log(data);
      $('#myModalText').html(data);
      show_modal();
    });
}

function add_from_img_library(path)
{
  $('#containment-wrapper').append("<div id='img_"+ curr_img_no + "' style='width:100px;height:100px;position:absolute;left:100px;top:100px;' class='draggable images'><img src='" + path + "' id='imgtag_"+ curr_img_no + "' style='height:100%;' /></div>");
  $('#img_' + curr_img_no).resizable({aspectRatio: true });
  curr_img_no++;
  init();
  set_as_changed();
  $('#img_' + curr_img_no).width($('#imgtag_' + curr_img_no).width());
  hide_modal();
}

function readFile(file) {
    var reader = new FileReader();
    reader.onload = readSuccess;
    function readSuccess(evt) {
      $('#containment-wrapper').append("<div id='cam_"+ curr_img_no + "' style='width:100px;height:100px;position:absolute;left:100px;top:100px;' class='draggable images'><img id='camtag_"+ curr_img_no + "' style='height:100%;' /></div>");
      $('#cam_' + curr_img_no).find('img')[0].src = evt.target.result;
      $('#cam_' + curr_img_no).resizable({aspectRatio: true });
      curr_img_no++;
      init();
      set_as_changed();
      $('#cam_' + curr_img_no).width($('#cam_' + curr_img_no).find('img').width());
    };
    reader.readAsDataURL(file);
} 

function insert_img(img_id)
{
  drawing = new Image();
  myObj = $('#'+img_id).find('img');
  drawing.src = myObj.attr('src');
  var x = parseInt($('#'+img_id).css('left').replace('px',''));
  var y = parseInt($('#'+img_id).css('top').replace('px',''));
  var w = parseInt($('#'+img_id).find('img').css('width').replace('px',''));
  var h = parseInt($('#'+img_id).find('img').css('height').replace('px',''));
  drawing.onload = function() 
  {
    canvas = document.getElementById('canvas');
    context = canvas.getContext('2d');
    context.drawImage(drawing,x,y,w,h);
    $('#'+img_id).remove();
    hide_modal();
  };
}

function del_img(img_id)
{
  $('#'+img_id).remove();
  hide_modal();
}

function get_modal_txt_for_img(img_id)
{
  var txt = "";
  txt = "<table style='width:100%;'>";
  txt = txt + "<tr>";
  txt = txt + "<td style='width:5vw;vertical-align:top;'><button onclick='insert_img(\"" + img_id + "\");'>In das Bild einfügen</button>";
  txt = txt + "<button style='background-color:red;' onclick='del_img(\"" + img_id + "\");'>Löschen</button></td>";
  txt = txt + "</tr>";
  txt = txt + "</table>";
  return txt;
}
