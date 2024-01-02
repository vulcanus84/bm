function start_arrow()
{
  curr_func = 'start';
  $('#freehand').removeClass('active');
  $('#erase').removeClass('active');
  $('#add_arrow_btn').addClass('active');
}

function getMousePos(canvas, evt) {
  var rect = canvas.getBoundingClientRect();
  return {
    x: evt.clientX - rect.left,
    y: evt.clientY - rect.top
  };
}

function add_arrow(e)
{
  var pos = getMousePos(document.getElementById('canvas'),e);
  if(curr_func=='end') { curr_func = 'draw'; endX = pos.x; endY = pos.y; }
  if(curr_func=='start') { curr_func = 'end'; startX = pos.x; startY = pos.y; }
  
  if(curr_func=='draw')
  {
    var canvas = document.getElementById('canvas');
    var ctx = canvas.getContext('2d');
    context.globalCompositeOperation='source-over';
    context.strokeStyle = curr_color;
    canvas_arrow(ctx, startX, startY, endX, endY,curr_color,curr_arrow_no);
    ctx.stroke();
    
    curr_func = 'start';
    startX = null;
    startY = null;
    endX = null;
    endY = null;    
    curr_arrow_no++;
    $('#arrow_no').val(curr_arrow_no);
  }
}

function canvas_arrow(context, fromx, fromy, tox, toy, color, arrow_no) 
{
  var headlen = 30; // Länge des Pfeilkopfes in Pixeln
  var shaftWidth = 5; // Breite des Pfeilschafts in Pixeln
  var dx = tox - fromx;
  var dy = toy - fromy;
  var angle = Math.atan2(dy, dx);
  var arrowLength = Math.sqrt(dx*dx + dy*dy);

  var old_lineWidth = context.lineWidth;
  context.lineWidth = shaftWidth;
  context.beginPath();

  context.moveTo(fromx, fromy);

  if (arrowLength > 100) {
    context.lineTo(fromx + dx / 2.3, fromy + dy / 2.3);
    context.moveTo(fromx + dx / 1.7, fromy + dy / 1.7);
  } else {
    context.lineTo(fromx + dx / 2.5, fromy + dy / 2.5);
    context.moveTo(fromx + dx / 1.5, fromy + dy / 1.5);
  }

  context.lineTo(tox, toy);

  // Berechnung der Pfeilspitze für einen dickeren Pfeil
  var arrowHeadX = tox - shaftWidth * Math.cos(angle);
  var arrowHeadY = toy - shaftWidth * Math.sin(angle);

  context.lineTo(arrowHeadX, arrowHeadY);
  context.moveTo(tox, toy);

  // Berechnung der Pfeilspitze für einen dickeren Pfeil mit verändertem Winkel
  var angle1 = angle - Math.PI / 6;
  var angle2 = angle + Math.PI / 6;

  var arrowHeadX1 = tox - headlen * Math.cos(angle1) - shaftWidth * Math.cos(angle);
  var arrowHeadY1 = toy - headlen * Math.sin(angle1) - shaftWidth * Math.sin(angle);

  var arrowHeadX2 = tox - headlen * Math.cos(angle2) - shaftWidth * Math.cos(angle);
  var arrowHeadY2 = toy - headlen * Math.sin(angle2) - shaftWidth * Math.sin(angle);

  context.lineTo(arrowHeadX1, arrowHeadY1);
  context.moveTo(tox, toy);
  context.lineTo(arrowHeadX2, arrowHeadY2);

  context.font = '30px Arial';
  context.fillStyle = color;
  context.fillText(arrow_no, fromx + dx / 2 - 5, fromy + dy / 2 + 5);

  context.stroke();
  context.lineWidth = old_lineWidth;
}
