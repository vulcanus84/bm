const tree = {};
let set;
let ma_id;
let mode='load';
const match = new BadmintonMatch();

//Event handlers
$(document).ready(function() {
  $('.close').on('click', () => $('#myModal').hide());
  $('#point_for_trainee').on('click', (e) => new_point(null,'trainee'));
  $('#point_for_opponent').on('click', (e) => new_point(null,'opponent'));

  //Load players
  match.setPlayerNames($('#player').prop('outerHTML'),$('#playerPartner').prop('outerHTML'),$('#opponent').prop('outerHTML'),$('#opponentPartner').prop('outerHTML'));

  const params = new URLSearchParams(window.location.search);
  set = params.get('set');
  ma_id = params.get('ma_id');

  $('#btn_set' + set).addClass('orange');

  let my_url = 'index.php?ajax=get_reasons_as_json'
  $.ajax({ url: my_url }).done(
    function(data)
    {
      data.forEach(row => {
        const { ma_reason_level1: l1, ma_reason_level2: l2, ma_reason_level3: l3, ma_reason_level4: l4 } = row;

        if (!tree[l1]) tree[l1] = {};
        if (l2) {
          if (!tree[l1][l2]) tree[l1][l2] = {};
          if (l3) {
            if (!tree[l1][l2][l3]) tree[l1][l2][l3] = {};
            if (l4) tree[l1][l2][l3][l4] = true;
          }
        }
      });
  });

  my_url = 'index.php?ajax=get_points_as_json&set=' + set + '&ma_id=' + ma_id;
  $.ajax({ url: my_url }).done(
    function(data)
    {
      mode = 'load';
      data.forEach(row => {
        const args = [
          row.ma_point_id,
          row.ma_point_winner,
          row.ma_point_caused_by,
          row.ma_reason_level1,
          row.ma_reason_level2
        ];

        if (row.ma_reason_level3!=null) args.push(row.ma_reason_level3);
        if (row.ma_reason_level4!=null) args.push(row.ma_reason_level4);
        new_point(...args);

      });
      mode = 'save';
      $('img.delete').on('click', (e) => delete_entry(e.currentTarget.id));
    }
  );
});

function getLevelOptions(...path) {
  let current = tree;

  for (let key of path) {
    if (current && current[key]) {
      current = current[key];
    } else {
      return [];
    }
  }

  if (current && typeof current === 'object') {
    return Object.keys(current);
  } else {
    return [];
  }
}


function new_point(id, winner, player = null, ...path) {
  if(id===null) {
    id = 'dummy_' + Date.now() + '_' + Math.floor(Math.random() * 1000);
  }

  //*******************************************************/
  //Create dynamic header text
  //*******************************************************/
  let txt = "<h1>";
  let tempDiv = document.createElement('div');
  let htmlCode = null;

  if(winner==='trainee') {
    txt += "Punkt für " + (match.mode==='double' ? match.traineeNameTxt + "/" + match.traineePartnerNameTxt : match.traineeNameTxt) + " durch ";
    if(path[0]) {
      txt += path[0]; 
      if(player) {
        if (path[0] === 'Fehler') { 
          txt+= " von " + (player === 'player' ? match.opponentNameTxt : match.opponentPartnerNameTxt); 
        } else {
          txt += (match.mode==='double' ? " von " + (player==='player' ? match.traineeNameTxt : match.traineePartnerNameTxt) : '');
        }
        if(path[1]) txt+= " beim " + path[1]; else txt+= " beim..."
      } else txt+= " von..."
    } else txt += "..."
    tempDiv.innerHTML = $('#point_for_trainee').prop('outerHTML');
  } else {
    txt += "Punkt für " + (match.mode==='double' ? match.opponentNameTxt + "/" + match.opponentPartnerNameTxt : match.opponentNameTxt) + " durch ";
    if(path[0]) {
      txt += path[0]; 
      if(player) {
        if (path[0] === 'Fehler') { 
          txt+= " von " + (player === 'player' ? match.traineeNameTxt : match.traineePartnerNameTxt); 
        } else {
          txt += (match.mode==='double' ? " von " + (player==='player' ? match.opponentNameTxt : match.opponentPartnerNameTxt) : '');
        } 
        if(path[1]) txt+= " beim " + path[1]; else txt+= " beim..."
      } else txt+= " von..."
    } else txt += "..."
    tempDiv.innerHTML = $('#point_for_opponent').prop('outerHTML');
  }
  const el = tempDiv.firstElementChild;
  el.removeAttribute('id');
  htmlCode = el.outerHTML;
  txt += "</h1>";
  //*******************************************************/

  let arr_options = getLevelOptions(...path);

  //*******************************************************/
  // End of path, save point
  //*******************************************************/
  if(arr_options.length===0) {
    $('#myModal').hide();
    match.addPoint({
      id: id,
      winner: winner,
      caused_by: player,
      type: path[0],
      shot: path[1],
      detail: path[2],
      extra: path[3],
    });

    let pointPlayer = "";
    let errorStyle = "";
    let main_reason = path[0];
    let reason_path_text = path.slice(1)
      .filter(p => p && p.trim() !== '')
      .join(' / ');

    if ($('#points_table tbody').length === 0) $('#points_table').append('<tbody></tbody>');

    errorStyle = "display: flex; flex-direction: column; gap: 5px; padding: 5px;border-radius:30px;"
    switch (main_reason) {
      case 'Fehler':
        errorStyle += 'border:5px solid #E63946;';
        break;
      case 'Gewinnschlag':
        errorStyle += 'border:5px solid #52B788;';
        break;
      case 'Glück':
        errorStyle += 'border:5px solid #F1A208;';
        break;
      default:
        errorStyle += 'border:5px solid #DDD;';
    }

    if(winner==='trainee') {
      if (main_reason === 'Fehler') {
          pointPlayer = (player === 'player' ? match.opponentName : match.opponentPartnerName);
      } else {
          pointPlayer = (player === 'player' ? match.traineeName : match.traineePartnerName);
      }
      $('#points_table tbody').prepend(`
        <tr id='row_${id}'>
          <td class='left'>${htmlCode}</td>
          <td class='middle'>
            <div class='point_visualisation' style="${errorStyle}">
              <div class='point_player'>${pointPlayer || ''}</div>
              <div><img class='delete' id='${id}' src='inc/imgs/delete.png' alt='Delete last entry' /></div>
            </div>
          </td>
          <td class='right'>${reason_path_text}</td>
        </tr>
      `);
    } else {
      if (main_reason === 'Fehler') {
          pointPlayer = (player === 'player' ? match.traineeName : match.traineePartnerName);
      } else {
          pointPlayer = (player === 'player' ? match.opponentName : match.opponentPartnerName);
      }
      $('#points_table tbody').prepend(`
        <tr id='row_${id}'>
          <td class='left'>${reason_path_text}</td>
          <td class='middle'>
            <div style="${errorStyle}">
              <div class='point_player'>${pointPlayer || ''}</div>
              <div><img class='delete' id='${id}' src='inc/imgs/delete.png' alt='Delete last entry' /></div>
            </div>
          </td>
          <td class='right'>${htmlCode}</td>
        </tr>
      `);
    }

    $('img.delete').off('click').on('click', (e) => delete_entry(e.currentTarget.id));

    update_stats();

    // ----------------------------
    // Save by AJAX
    // ----------------------------
    if(mode==='save') {
      let my_url = 'index.php?ajax=save_point&winner=' + winner
        + '&level1=' + encodeURIComponent(main_reason)
        + '&level2=' + encodeURIComponent(path[1] || '')
        + '&level3=' + encodeURIComponent(path[2] || '')
        + '&level4=' + encodeURIComponent(path[3] || '')
        + '&player=' + encodeURIComponent(player || '')
        + '&ma_id=' + ma_id
        + '&set=' + set
        + '&point_id=' + id;

      $.ajax({ url: my_url }).done(function(data) {
        if(data.startsWith('OK>')) {
          let newId = data.split('>')[1];
          $('img.delete#' + id).attr('id', newId);
          $('tr#row_' + id).attr('id', 'row_' + newId);
          match.replacePointId(id, newId);
        } else if(data != '') {
          $('tr#row_' + id).remove();
          alert(data);
        }
      });
    }

    return;
  }

  // ----------------------------
  // Between Level 1 and 2 → Player selection
  // ----------------------------
  if(path.length===1 && player===null) {
    if(match.mode === 'double')
    {
      let p1, p2;
      if(winner==='trainee') {
        if(path[0]==='Fehler') {
          p1 = match.opponentName;
          p2 = match.opponentPartnerName;
        } else {
          p1 = match.traineeName;
          p2 = match.traineePartnerName;
        }
      } else {
        if(path[0]==='Fehler') { 
          p1 = match.traineeName;
          p2 = match.traineePartnerName;
        } else {
          p1 = match.opponentName;
          p2 = match.opponentPartnerName;
        }
      }
      let btn_height = 5;

      txt += `<button class='level_option' data-level='back' style='font-size:${btn_height*0.6}vh;width:75vw;height:${btn_height}vh;margin:${btn_height/10}vh;background-color:gray'>Zurück</button>`;

      txt += `<div class='level_option' data-level='player'>${p1}</div>`;
      txt += `<div class='level_option' data-level='partner'>${p2}</div>`;
      txt += `<div style='clear:both;margin-bottom:1vh;'></div>`;

    } else {
      new_point(null,winner,'player',...path);
      return
    }
  } else {
    // ----------------------------
    // Usual Level selection
    // ----------------------------
    let i = 0;
    let btn_height = 50 / (arr_options.length + 1);

    if(path.length>0) {
      txt += `<button class='level_option' style='font-size:${btn_height*2}pt;width:75vw;height:${btn_height}vh;margin:${btn_height/10}vh;background-color:gray' data-level='back'>Zurück</button>`;
    }

    for (const option of arr_options) {
      let color = colors[i % colors.length];
      let arr_options_next = getLevelOptions(...path, option);
      let suffix = arr_options_next.length > 0 ? '＋' : '✅';

      let len = option.length;
      let calc_font_size = btn_height * 0.3;
      if(len>12) calc_font_size = calc_font_size / (Math.sqrt(len)/3);

      txt += `<button class='level_option' style='font-size:${calc_font_size}vh;width:75vw;height:${btn_height}vh;margin:${btn_height/10}vh;background-color:${color}' data-level='${option}'>${option} ${suffix}</button>`;
      i++;
    }
  }

  $('#myModalText').html(txt);
  $('#myModal').show();

  $('#myModalText').off('click', '.level_option')
    .on('click', '.level_option', (e) => {
    
    const level = e.currentTarget.getAttribute('data-level');
    
    const isBack  = (level === 'back');
    const playerSelection = (path.length === 1);
    let newPlayer = player;
    let newPath;

    if (playerSelection) {
      if (match.mode === 'double') {
        if (isBack) {
          newPlayer = null;
          newPath = player ? path : path.slice(0, -1);
        } else {
          if (player) {
            newPath = [...path, level];
          } else {
            newPlayer = level;
            newPath = path;
          }
        }
      } else {
        if (isBack) {
          newPath = path.slice(0, -1);
        } else {
          newPlayer = 'player';
          newPath = [...path, level];
        }
      }
    } else {
      if (isBack) {
        newPath = path.slice(0, -1);
      } else {
        newPath = [...path, level];
      }
    }

    new_point(null, winner, newPlayer, ...newPath);   
  });

}

function delete_entry(id) {
  match.removePointById(id);
  $('#row_' + id).remove();
  update_stats();
  let my_url = 'index.php?ajax=delete_point&point_id=' + id
      + '&ma_id=' + ma_id
      + '&set=' + set;

  $.ajax({ url: my_url }).done(
    function(data)
    {
      if(data!='') { alert(data); }
      //location.reload();
  });
}