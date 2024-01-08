function save_copy()
{
  curr_drawing_id = null;
  save_pic();
}

function save_pic()
{
  var save_ok = true;
  //Get all movable information on drawing with coordinates
  const players = [];
  const textfields = [];
  const imgs = [];
  $( "div.draggable").each(function( index ) {
    if($(this).attr('id').substring(0,5)=='text_')
    {
      let textfield = { posX : $(this).css('left') , posY : $( this ).css('top'), width : $( this ).css('width'), height : $( this ).css('height'), mytext : $(this).text() };
      textfields.unshift(textfield);
    }
    else
    {
      if($(this).attr('id').substring(0,4)=='img_')
      {
        let myimg = { posX : $(this).css('left') , posY : $( this ).css('top'), width : $( this ).css('width'), height : $( this ).css('height'), img_path : $(this).find('img').attr('src') };
        imgs.unshift(myimg);
      }
      else
      {
        if($(this).attr('id').substring(0,4)=='cam_')
        {
          alert('Kamerabilder müssen vor dem Speichern definitiv eingefügt werden');
          save_ok = false;
        }
        else
        {
          let player = { posX : $(this).css('left') , posY : $( this ).css('top'), id : $(this).attr('id') };
          players.unshift(player);
        }
      }
    }
  });
  const json_players = JSON.stringify(players);
  const json_textfields = JSON.stringify(textfields);
  const json_imgs = JSON.stringify(imgs);
  if(save_ok)
  {
    //Send pure draw as base64 encoded string
    var dataURL = document.getElementById('canvas').toDataURL();
    //Get temporary canvas to generate preview image
    var destCanvas = document.getElementById('canvas2');
    ctx = destCanvas.getContext('2d');

    //Get Background and fill it
    backgrounds.forEach(function(item) { if(item.name==$('#select_bg').val()) { path = item.path; }} );
    var img1 = new Image();
    img1.onload = function () 
    {
      //draw background image
      ctx.globalCompositeOperation='source-over';
      ctx.drawImage(img1, 0, 0);

      //Get current canvas with drawing and copy to temporary canvas
      sourceCanvas = document.getElementById('canvas') ;
      ctx.drawImage(sourceCanvas, 0, 0);

      //Write all movable elements to pixels in canvas
      write_div_to_canvas(function() 
      {
        console.log('Write to Canvas');
        var dataURL_preview = document.getElementById('canvas2').toDataURL();
        $.ajax({
          type: 'POST',
          url: 'save_image.php',
          data: 
          { 
            dataURL: dataURL,
            dataURL_preview: dataURL_preview,
            drawing_id: curr_drawing_id,
            bg_image : $('#select_bg').val(),
            players: json_players,
            textfields: json_textfields,
            imgs: json_imgs
          }
        }).done(function(data) 
        {
          var jsonData = JSON.parse(data);
          curr_drawing_id = jsonData.drawing_id;
          path = jsonData.path; 
          curr_drawing_preview_path = path.replace('.png','_preview.png');
          isChanged = false;
          update_file_infos();
          init();
        });
      });
    };
    img1.src = path;
  }
}

function write_div_to_canvas(_callback)
{
  const drawing = [];
  var load_pics = false;
  var i = 0; var j = 0;
  canvas = document.getElementById('canvas2');
  context = canvas.getContext('2d');
  $( "div.draggable" ).each(function( index ) 
  {
    if($(this).attr('id').substring(0,5)=='text_')
    {
      //Textfield
      txt = $(this).find('span').text();
      var x = $(this).css('left').replace('px','');
      var y = $( this ).css('top').replace('px','');
      var width = $( this ).css('width').replace('px','');
      y = parseInt(y);
      y = y + 20;
      canvas = document.getElementById('canvas2');
      context = canvas.getContext('2d');
      context.font = "16pt Arial";
      let wrappedText = wrapText(context, txt, x, y, width, 23);
      wrappedText.forEach(function(item) {
          ctx.fillText(item[0], item[1], item[2]); 
      })
    }
    else
    {
      load_pics = true;
      if($(this).attr('id').substring(0,4)=='img_')
      {
        //Image
        drawing[index] = new Image();
        i++;
        drawing[index].src = $(this).find('img').attr('src');
        var x = $(this).css('left').replace('px','');
        var y = $( this ).css('top').replace('px','');
        var w = $( this ).css('width').replace('px','');
        var h = $( this ).css('height').replace('px','');
        drawing[index].onload = function() 
        {
          j++
          canvas = document.getElementById('canvas2');
          context = canvas.getContext('2d');
          context.drawImage(drawing[index],x,y,w,h);
          //After all images are loaded, call callback-function
          if(j==i) { _callback(); } 
        };
      }
      else
      {
        //Player
        drawing[index] = new Image();
        i++;
        drawing[index].src = $(this).find('img').attr('src');
        var x = $(this).css('left').replace('px','');
        var y = $( this ).css('top').replace('px','');
        drawing[index].onload = function() 
        {
          j++
          canvas = document.getElementById('canvas2');
          context = canvas.getContext('2d');
          context.drawImage(drawing[index],x,y);
          //After all images are loaded, call callback-function
          if(j==i) { _callback(); } 
        };
      }
    }
  });
  //if no users have to be loaded, call callback function, otherwise it will be called after all images are loaded
  if(!load_pics) { _callback(); } 
}

function load_pic(path,id)
{
  set_edit_mode('freehand')
  hide_modal();
  remove_draggables();
  curr_drawing_id = id;
  curr_drawing_preview_path = path.replace('.png','_preview.png');
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
  load_players(id);
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
      $('#select_bg').val(jsonData.bg_image);
      change_background(true);
    });
}


function load_players(excercise_id)
{
  var my_url = 'index.php?ajax=get_players&excercise_id='+ excercise_id;
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
              $('#containment-wrapper').append("<div id='" + obj[3] + "' style='position:absolute;left:" + obj[1] + "px;top:" + obj[2] + "px;' class='draggable player'/><img style='width:120px;' src='" + obj[0] + "' /></div>");
              init();
            });
      }
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
          if(obj.typ=='textfield')
          {
            $('#containment-wrapper').append("<div id='text_" + i + "' style='font-size:16pt;border-radius:10px;padding:5px;background-color:rgba(0, 0, 0, 0.05);width:" + obj.width + "px;height:" + obj.height + "px;position:absolute;left:" + obj.posx + "px;top:" + obj.posy + "px;' class='draggable texts'/><span>" + obj.text + "</span></div>");
            $('#text_' + i).resizable();
          }
          if(obj.typ=='img')
          {
            $('#containment-wrapper').append("<div id='img_"+ i + "' style='width:" + obj.width +"px;height:" + obj.height +"px;position:absolute;left:" + obj.posx +"px;top:" + obj.posy +"px;' class='draggable images'><img src='" + obj.pic_path + "' id='imgtag_"+ i + "' style='width:100%;' /></div>");
            $('#img_' + i).resizable();
          }
          init();
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
}

function close_pic()
{
  hide_modal();
  curr_drawing_id = null;
  isChanged = false;
  del_pic();
  update_file_infos();
  remove_draggables();
  curr_arrow_func = null;
}
function show_pics()
{
  if(isChanged)
  {
    var my_url = 'index.php?ajax=del_changes_warning';
    $.ajax({ url: my_url }).done(
      function(data)
      {
        $('#myModalText').html(data); 
        show_modal();
      });
  }
  else
  {
    if(curr_drawing_id>0)
    {
      close_pic();
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
  if(isChanged)
  {
    $('#save_pic').show();
    $('#save_pic').css('background-color','orange');
    $('#save_pic').text('Speichern');
    $('#load_pic').text('Laden');
  }
  else
  {
    $('#save_pic').hide();
    $('#save_copy').hide();
  }

  if(curr_drawing_id > 0)
  {
    if(isChanged)
    {
      $('#save_copy').show();
    }
    $('#load_pic').text('Schliessen');
    $('#del_pic').show();
    $('#preview_link').attr("href", curr_drawing_preview_path);
    $('#preview_link_container').show();
  }
  else
  {
    if(isChanged)
    {
      $('#load_pic').hide();
      $('#erase_pic').show();
    }
    else
    {
      $('#erase_pic').hide();
      $('#load_pic').text('Laden');
      $('#load_pic').show();
    }
    $('#del_pic').hide();
    $('#preview_link_container').hide();
  }
}

function remove_draggables()
{
  $('div').remove('.draggable');
}



