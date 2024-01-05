function startDrawing(e) {
  var pos = getMousePos(document.getElementById('canvas'),e);
  if(curr_edit_mode=='freehand')
  {
    isDrawing = true; isErasing = false;
  }
  if(curr_edit_mode=='erase')
  {
    isErasing = true; isDrawing = false;
  }
  context.beginPath();
  context.moveTo(pos.x, pos.y);
}

function draw(e) {
  var pos = getMousePos(document.getElementById('canvas'),e);
  if (isDrawing == true) {
    context.globalCompositeOperation='source-over';
    context.strokeStyle = curr_color;
    context.lineTo(pos.x, pos.y);
    context.stroke();
  }
  if (isErasing == true) 
  {
    context.globalCompositeOperation='destination-out';
    context.arc(pos.x,pos.y,30,0,Math.PI*2,false);
    context.fill();
  }
}

function stopDrawing() {
   isDrawing = false;
   isErasing = false;
   set_as_changed();
 }