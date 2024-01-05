var curr_text_func = null;
var curr_text_no = 1;

function startTextBox(e)
{
  var pos = getMousePos(document.getElementById('canvas'),e);
  $('#containment-wrapper').append("<div id='text_"+ curr_text_no + "' style='font-size:16pt;border-radius:10px;padding:5px;background-color:rgba(0, 0, 0, 0.05);width:100px;height:100px;position:absolute;left:" + pos.x + "px;top:" + pos.y + "px;' class='draggable' /><span>Lorem ipsum</span></div>");
  $('#text_' + curr_text_no).resizable();
  curr_text_no++;
  init();
  set_as_changed();
}

function save_text(textbox_id)
{
  $('#'+textbox_id).find('span').text($('#edited_text').val());
  canvas.addEventListener("mousedown", startTextBox, false);
  hide_modal();
}

function del_text(textbox_id)
{
  $('#'+textbox_id).remove();
  canvas.addEventListener("mousedown", startTextBox, false);
  hide_modal();
}

function get_modal_txt_for_textfield(textfield_id)
{
  var txt = "";
  txt = "<table style='width:100%;'>";
  txt = txt + "<tr>";
  txt = txt + "<td><textarea id='edited_text' style='width:100%;'>" + $('#'+textfield_id).text() + "</textarea></td>";
  txt = txt + "</tr>";
  txt = txt + "<tr>";
  txt = txt + "<td style='width:5vw;vertical-align:top;'><button onclick='save_text(\"" + textfield_id + "\");'>Speichern</button>";
  txt = txt + "<button style='background-color:red;' onclick='del_text(\"" + textfield_id + "\");'>Löschen</button></td>";
  txt = txt + "</table>";
  return txt;
}