function startDrawing(e) {
  var pos = getMousePos(document.getElementById('canvas'),e);
  if($('#freehand').hasClass('active'))
  {
    isDrawing = true; isErasing = false;
  }
  if($('#erase').hasClass('active'))
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
   set_as_changed();
 }

 function freehand()
 {
   $('#add_arrow_btn').removeClass('active');
   $('#erase').removeClass('active');
   $('#freehand').addClass('active');
   curr_func = null;
 }

 function erase()
 {
   $('#add_arrow_btn').removeClass('active');
   $('#freehand').removeClass('active');
   $('#erase').addClass('active');
   curr_func = null;
 }
