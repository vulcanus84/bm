var tournamentId;
var base_link = String(window.location.origin + window.location.pathname);
var server_link = String(window.location);
if(!server_link.includes('?')) { server_link = server_link + '?x=y'; }

const queryString = window.location.search;
const params = new URLSearchParams(queryString);

const urlParams = {};
for (const [key, value] of params) {
  urlParams[key] = value;
}

function resizeImage(file, maxSize) {
  return new Promise((resolve, reject) => {
    const img = new Image();
    const reader = new FileReader();

    reader.onload = e => img.src = e.target.result;
    reader.onerror = reject;

    img.onload = () => {
      let { width, height } = img;

      if (width > height && width > maxSize) {
        height = height * (maxSize / width);
        width = maxSize;
      } else if (height > maxSize) {
        width = width * (maxSize / height);
        height = maxSize;
      }

      const canvas = document.createElement('canvas');
      canvas.width = width;
      canvas.height = height;

      const ctx = canvas.getContext('2d');
      ctx.imageSmoothingQuality = 'high';
      ctx.drawImage(img, 0, 0, width, height);

      canvas.toBlob(
        blob => blob ? resolve(blob) : reject(),
        'image/jpeg',
        0.8
      );
    };

    reader.readAsDataURL(file);
  });
}

function canResizeImages() {
  return (
    typeof FileReader !== 'undefined' &&
    typeof HTMLCanvasElement !== 'undefined' &&
    !!document.createElement('canvas').getContext &&
    (
      typeof HTMLCanvasElement.prototype.toBlob === 'function' ||
      typeof HTMLCanvasElement.prototype.toDataURL === 'function'
    )
  );
}



$(document).ready(function() {
  playerId = $('#content').data('user-id');
  if(!playerId) $('#left_col').addClass('open'); 

  if(playerId>0) { $('#right_col').load(server_link+'&ajax=show_infos'); }
  setEvents();
});

function setEvents() {

  //Remove all events from main part with the delegations
  $('#content').off();

  //Location filter
  $('#content').on('change', 'select[name="location"]', function () {
      const selected = $(this).val();
      const allSections = $('#left_content section');

      // Reset visibility
      allSections.show();
      allSections.find('.user_pic').show();

      // Show everything if no selection
      if (!selected) return;

      // Special case: show only section_<id>
      const targetSectionId = 'section_' + selected;
      const targetSection = $('#' + targetSectionId);

      if (targetSection.length) {
          allSections.not(targetSection).hide();

          // Filter user_pics in target section
          targetSection.find('.user_pic').each(function () {
              const ids = String($(this).data('location-id'))
                          .split(',')
                          .map(i => i.trim());
              $(this).toggle(ids.includes(String(selected)));
          });

          // Update count in h1
          const visiblePics = targetSection.find('.user_pic:visible').length;
          const h1 = targetSection.find('h1');
          const baseTitle = h1.text().replace(/\(\s*\d+\s*\)\s*$/, '').trim();
          h1.text(`${baseTitle} (${visiblePics})`);

          return;
      }

      // Default filtering when no matching section_<id>
      allSections.each(function () {
          const section = $(this);

          // Filter user_pics
          section.find('.user_pic').each(function () {
              const ids = String($(this).data('location-id'))
                          .split(',')
                          .map(i => i.trim());
              $(this).toggle(ids.includes(String(selected)));
          });

          // Toggle section based on visible user_pics
          const visiblePics = section.find('.user_pic:visible').length;
          section.toggle(visiblePics > 0);

          // Update count in h1
          const h1 = section.find('h1');
          const baseTitle = h1.text().replace(/\(\s*\d+\s*\)\s*$/, '').trim();
          h1.text(`${baseTitle} (${visiblePics})`);
      });
  });

  $('#left_col').off('click').on('click', function(e) {
    if (!$(e.target).closest('.user_pic, button, select, a, .dropdown,img').length) {
      $(this).toggleClass('open');
    }
  });
  $('#content').on('click','div.user_pic', (e) => show_infos(e.currentTarget.id));

  $('img.img_sort').off('click').on('click', (e) => change_group_by(e));
  
  $('#content').on('submit', 'form#new_user', async function (e) {
    e.preventDefault(); // Formular nicht normal absenden

    let formData = new FormData(this); // FormData aus Formular

    // ---------- Checkbox-Check ----------
    const checkboxes = document.querySelectorAll('#new_user input[type="checkbox"][name^="loc_"]');
    let checked = false;

    checkboxes.forEach(box => {
      if (box.checked) checked = true;
    });

    var oldVal = $('select[name="location"]').val();

    if (!checked) {
      alert("Bitte mindestens einen Trainingsort auswählen!");
      return;
    }

    // ---------- BILD VERKLEINERN ----------
    var input = document.querySelector('input[name="pictures[]"]');
    var file = input && input.files.length ? input.files[0] : null;

    if (
      canResizeImages() &&
      file &&
      file.size > 0 &&
      file.type.match(/^image\//)
    ) {
      resizeImage(file, 800).then(function (resizedBlob) {
        formData.set('pictures[]', resizedBlob, file.name);
      }).catch(function () {
        console.error('Bildverkleinerung fehlgeschlagen', err);
      });
    }

    // ---------- AJAX ----------
    $.ajax({
      url: server_link + '&ajax=save_user',
      type: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      success: function (response) {
        if (!isNaN(response) && response.trim() !== '') {
          $('#left_content').load(server_link + '&ajax=get_left_col_users', function () {
            $('select[name="location"]').val(oldVal).trigger('change');
            show_infos('user_' + response, true);
          });
        } else {
          $('#right_content').html(response);
        }
      },
      error: function (xhr, status, error) {
        $('#right_content').html(error);
      }
    });
    
  });

}

function perform_ajax(function_name,param_url,target_id=null) {
  var my_url = server_link+'&ajax='+function_name+'&tournament_id='+tournamentId+'&'+param_url;
  if(target_id!=null)   
  { 
    $('#'+target_id).load(my_url); 
  }
  else
  {
    $.ajax({ url: my_url }).done(
      function(data)
      {
        if(data=='OK') { window.location = server_link; } else { alert(data); }
      });  
  
  }
}

function new_user() {
  $('#right_content').load(server_link+'&ajax=new_user');
}

function trigger_upload_pic_selection(id)
{
  $('#inpPicture').trigger('click');
}

function show_infos(user_tag_id,pic_replace=false) {
  var user_id = user_tag_id.replace(/user_/g, "");
  $('#right_content').load(server_link+'&ajax=show_infos&user_id='+user_id, function() {
    if(pic_replace) {
      let neuesSrc = $('#user_pic_large').attr('src');
      $('#'+user_tag_id + ' img').attr('src', neuesSrc);
    }
  });
  $('#left_col').toggleClass('open');
}

function show_history(user_id) {
  $('#right_content').load(server_link+'&ajax=show_history&user_id='+user_id);
}

function delete_permission(user_id) {
  $('#right_content').load(server_link+'&ajax=delete_permission_user&user_id='+user_id);
}

function delete_user(user_id)
{
  var my_url = server_link +'&ajax=delete_user&user_id=' + user_id;
  var oldVal = $('select[name="location"]').val();
  $.ajax({ url: my_url }).done(
  function(data)
  {
    $('#right_content').html("");
    $('#left_content').load(server_link+'&ajax=get_left_col_users', function() {
      $('select[name="location"]').val(oldVal);
      $('select[name="location"]').trigger('change');
    });
});
}

function delete_pic(user_id)
{
  var my_url = server_link +'&ajax=delete_pic&user_id=' + user_id;
  var oldVal = $('select[name="location"]').val();
  $.ajax(my_url).done(
  function(data)
  {
    $('#right_content').load(server_link + '&ajax=show_infos&user_id='+user_id);
    $('#left_content').load(server_link+'&ajax=get_left_col_users', function() {
      $('select[name="location"]').val(oldVal);
      $('select[name="location"]').trigger('change');
    });
  });
}

function change_group_by(id) {
  var oldVal = $('select[name="location"]').val();

  $('#left_content').load(server_link+'&ajax=get_all_users&order_by='+id.currentTarget.id, function() {
    setEvents();
    $('select[name="location"]').val(oldVal);
    $('select[name="location"]').trigger('change');
  });
}