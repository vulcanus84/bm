const tree = {};
let chart;
let mypoints = 0;
let opponentpoints = 0;
let set;
let ma_id;
let mode='load';
const match = new BadmintonMatch();

const errorStrokes = ["Anspiel","Clear", "Drop", "Drive", "Smash", "Lift", "Netzdrop", "Kill"];
const errorAllowedCombinations = [
    { detail: "Out", extra: "Hinten" },
    { detail: "Out", extra: "Seite" },
    { detail: "Netz", extra: "" },
    { detail: "Nicht getroffen", extra: "" },
    { detail: "Zu flach", extra: "" },
    { detail: "Zu kurz", extra: "" },
    { detail: "Zu hoch", extra: "" },
    { detail: "---", extra: "" }
];

const winStrokes = ["Angriffsclear", "Drop", "Drive", "Smash", "Defense", "Netzdrop", "Kill", "Täuschung"];
const winAllowedCombinations = [
    { detail: "---", extra: "" },
    { detail: "Cross", extra: "" },
    { detail: "Longline", extra: "" },
];


const labels = [0];

const colors = [
  "#E63946", // warmes Rot
  "#F1A208", // kräftiges Goldgelb
  "#52B788", // weiches Grün
  "#277DA1", // kühles Blau
  "#9C89B8", // sanftes Violett
  "#F3722C", // warmes Orange
  "#666666", // Dunkelgrau
  "#4D908E", // Graublau
  "#F9C74F", // helles Gelb
  "#577590"  // gedecktes Marineblau
];

const arr_charts = [
  'chartMainReasons',
  'chartMainReasonsOpponent',
  'chartStrokes',
  'chartOuts',
  'chartStrokesOpponent',
  'chartOutsOpponent',
  'chartPointIncreases'
];

let autoScrollInterval = null;
let currentPercent = 0;

// Auto-Scroll starten
function startAutoScroll() {
    if (autoScrollInterval) return; // läuft bereits

    autoScrollInterval = setInterval(() => {
        currentPercent += 1;

        if (currentPercent > 100) {
            // Stoppe Interval
            clearInterval(autoScrollInterval);
            autoScrollInterval = null;

            // 5 Sekunden Pause bei 100%
            setTimeout(() => {
                currentPercent = 0;
                match.scrollToPercent(currentPercent);
                $('#point_slider').val(currentPercent);
                change_slider(currentPercent);

                // 2 Sekunden Pause bei 0%
                setTimeout(() => {
                    startAutoScroll(); // Auto-Scroll erneut starten
                }, 2000);

            }, 5000);

            return;
        }

        match.scrollToPercent(currentPercent);
        $('#point_slider').val(currentPercent);
        change_slider(currentPercent);

    }, 200);
}

// Auto-Scroll stoppen
function stopAutoScroll() {
    if (autoScrollInterval) {
        clearInterval(autoScrollInterval);
        autoScrollInterval = null;
    }
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

function change_slider(value) {
  match.scrollToPercent(parseInt(value));
  update_stats();
}

function change_slider(value) {
  value = parseInt(value, 10);
  currentPercent = value;          // aktuellen Wert merken
  match.scrollToPercent(value);    // Match-Cursor setzen
  update_stats();                 // Stats aktualisieren
}

//Event handlers
$(document).ready(function() {
  $('.close').on('click', () => $('#myModal').hide());
  $('#point_for_trainee').on('click', (e) => new_point(null,'trainee'));
  $('#point_for_opponent').on('click', (e) => new_point(null,'opponent'));
  $('#btnNext').on('click', () => change_chart('next'));
  $('#btnPrev').on('click', () => change_chart('previous'));
  $('.header_points').on('click', () => toggleSlider());
  $('.div_slider').hide();
  $('#point_slider').on('input', (e) => { change_slider(e.currentTarget.value); });
  $('#point_slider').on('pointermove', (e) => { change_slider(e.currentTarget.value); });

  function toggleSlider() {
      $('.div_slider').toggle();
      $('.header_players').toggle();
      if('none' === $('.div_slider').css('display')) {
          stopAutoScroll();
          match.scrollToPercent(100);
          currentPercent = 0;
          update_stats();
          $('.slider').val(100);
          $('#btnAutoScroll').text("▶️");
      } else {
          startAutoScroll();
          $('#btnAutoScroll').text("⏸️");
      }
    }
  
    // Button Event
    $('#btnAutoScroll').on('click', function() {
        const $btn = $(this);
        if (autoScrollInterval) {
            stopAutoScroll();
            $btn.text("▶️");
        } else {
            startAutoScroll();
            $btn.text("⏸️");
        }
  });

  //Load players
  match.setPlayerNames($('#player').prop('outerHTML'),$('#playerPartner').prop('outerHTML'),$('#opponent').prop('outerHTML'),$('#opponentPartner').prop('outerHTML'));

  let savedChart = sessionStorage.getItem("currentChart");

  // Alle ausblenden
  arr_charts.forEach(id => $('#' + id).hide());

  // Gespeichertes Chart anzeigen oder Standard (erstes)
  if (savedChart && arr_charts.includes(savedChart)) {
    $('#' + savedChart).show();
  } else {
    $('#' + arr_charts[0]).show();
  }

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
        // Array mit den Pflichtparametern
        const args = [
          row.ma_point_id,
          row.ma_point_winner,
          row.ma_point_caused_by,
          row.ma_reason_level1,
          row.ma_reason_level2
        ];

        // Optional: nur hinzufügen, wenn sie nicht leer sind
        if (row.ma_reason_level3!=null) args.push(row.ma_reason_level3);
        if (row.ma_reason_level4!=null) args.push(row.ma_reason_level4);
        // Übergabe mit Spread-Syntax
        new_point(...args);

      });
      mode = 'save';
      $('img.delete').on('click', (e) => delete_entry(e.currentTarget.id));
  });

  chartMainReasons = new Chart(document.getElementById('chartMainReasons'), {
    type: 'pie',
    data: {
      labels: match.getPointStatisticsLabels('trainee'),
      datasets: [{
        data: match.getPointStatistics('trainee'),
        borderWidth: 1,
        backgroundColor: colors
      }]
    },
    options: {
      plugins: {
        title: {
          display: true,          // 👈 Titel aktivieren
          text: 'Meine Punktgewinne durch', 
          font: {
            size: 24,             // Schriftgröße
            weight: 'bold'
          },
          color: '#000',          // Schriftfarbe
          padding: {
            top: 10,
            bottom: 0
          },
          align: 'center'         // oder 'start' | 'end'
        }
      },
      maintainAspectRatio: false
    }
  });

  chartMainReasonsOpponent = new Chart(document.getElementById('chartMainReasonsOpponent'), {
    type: 'pie',
    data: {
      labels: match.getPointStatisticsLabels('opponent'),
      datasets: [{
        data: match.getPointStatistics('opponent'),
        borderWidth: 1,
        backgroundColor: colors
      }]
    },
    options: {
      plugins: {
        title: {
          display: true,          // 👈 Titel aktivieren
          text: 'Punkte des Gegners durch', 
          font: {
            size: 24,             // Schriftgröße
            weight: 'bold'
          },
          color: '#000',          // Schriftfarbe
          padding: {
            top: 10,
            bottom: 0
          },
          align: 'center'         // oder 'start' | 'end'
        }
      },
      maintainAspectRatio: false
    }
  });

  chartStrokes = new Chart(document.getElementById('chartStrokes'), {
    type: 'bar',
    data: {
        labels: winStrokes,
        datasets: winAllowedCombinations.map((c, i) => {
            // Label richtig setzen: extra nur, wenn nicht leer
            const label = c.extra ? `${c.detail} ${c.extra}` : c.detail;
            return {
                label: label,
                data: winStrokes.map(() => 0),          // initial 0
                backgroundColor: colors[i % colors.length],
                extra: c.extra                       // optional für Tooltip
            };
        })
    },
    options: {
        scales: { 
            x: { stacked: true },
            y: { 
                stacked: true,
                beginAtZero: true,
                ticks: { stepSize: 1 },
                suggestedMax: 5
            }
        },
        plugins: {
            title: {
                display: true,
                text: 'Meine Gewinnschläge', 
                font: { size: 24, weight: 'bold' },
                color: '#000',
                padding: { top: 10, bottom: 0 },
                align: 'center'
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        const ds = context.dataset;
                        const label = ds.label;
                        return `${label}: ${context.raw}`;
                    }
                }
            }
        },
        animation: {
            duration: 500,
            easing: 'linear',
            loop: false
        },
        maintainAspectRatio: false
    }
    
});


// Initialisierung
chartOuts = new Chart(document.getElementById('chartOuts'), {
    type: 'bar',
    data: {
        labels: errorStrokes,
        datasets: errorAllowedCombinations.map((c, i) => {
            // Label richtig setzen: extra nur, wenn nicht leer
            const label = c.extra ? `${c.detail} ${c.extra}` : c.detail;
            return {
                label: label,
                data: errorStrokes.map(() => 0),          // initial 0
                backgroundColor: colors[i % colors.length],
                extra: c.extra                       // optional für Tooltip
            };
        })
    },
    options: {
        scales: { 
            x: { stacked: true },
            y: { 
                stacked: true,
                beginAtZero: true,
                ticks: { stepSize: 1 },
                suggestedMax: 5
            }
        },
        plugins: {
            title: {
                display: true,
                text: 'Meine Fehler', 
                font: { size: 24, weight: 'bold' },
                color: '#000',
                padding: { top: 10, bottom: 0 },
                align: 'center'
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        const ds = context.dataset;
                        const label = ds.label;
                        return `${label}: ${context.raw}`;
                    }
                }
            }
        },
        animation: {
            duration: 500,
            easing: 'linear',
            loop: false
        },
        maintainAspectRatio: false
    }
});

  chartStrokesOpponent = new Chart(document.getElementById('chartStrokesOpponent'), {
    type: 'bar',
    data: {
        labels: winStrokes,
        datasets: winAllowedCombinations.map((c, i) => {
            // Label richtig setzen: extra nur, wenn nicht leer
            const label = c.extra ? `${c.detail} ${c.extra}` : c.detail;
            return {
                label: label,
                data: winStrokes.map(() => 0),          // initial 0
                backgroundColor: colors[i % colors.length],
                extra: c.extra                       // optional für Tooltip
            };
        })
    },
    options: {
        scales: { 
            x: { stacked: true },
            y: { 
                stacked: true,
                beginAtZero: true,
                ticks: { stepSize: 1 },
                suggestedMax: 5
            }
        },
        plugins: {
            title: {
                display: true,
                text: 'Gewinnschläge des Gegners', 
                font: { size: 24, weight: 'bold' },
                color: '#000',
                padding: { top: 10, bottom: 0 },
                align: 'center'
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        const ds = context.dataset;
                        const label = ds.label;
                        return `${label}: ${context.raw}`;
                    }
                }
            }
        },
        animation: {
            duration: 500,
            easing: 'linear',
            loop: false
        },
        maintainAspectRatio: false
    }
});


// Initialisierung
chartOutsOpponent = new Chart(document.getElementById('chartOutsOpponent'), {
    type: 'bar',
    data: {
        labels: errorStrokes,
        datasets: errorAllowedCombinations.map((c, i) => {
            // Label richtig setzen: extra nur, wenn nicht leer
            const label = c.extra ? `${c.detail} ${c.extra}` : c.detail;
            return {
                label: label,
                data: errorStrokes.map(() => 0),          // initial 0
                backgroundColor: colors[i % colors.length],
                extra: c.extra                       // optional für Tooltip
            };
        })
    },
    options: {
        scales: { 
            x: { stacked: true },
            y: { 
                stacked: true,
                beginAtZero: true,
                ticks: { stepSize: 1 },
                suggestedMax: 5
            }
        },
        plugins: {
            title: {
                display: true,
                text: 'Fehler des Gegners', 
                font: { size: 24, weight: 'bold' },
                color: '#000',
                padding: { top: 10, bottom: 0 },
                align: 'center'
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        const ds = context.dataset;
                        const label = ds.label;
                        return `${label}: ${context.raw}`;
                    }
                }
            }
        },
        animation: {
            duration: 500,
            easing: 'linear',
            loop: false
        },
        maintainAspectRatio: false
    }
});


  chartPointIncreases = new Chart(document.getElementById('chartPointIncreases'), {
    type: 'line',
    data: {
      labels: [...labels],
      datasets: [
        {
          label: 'Meine Punkte',
          data: [0],
          borderWidth: 1,
          borderColor: colors[0],
          backgroundColor: colors[0]
        },
        {
          label: 'Gegnerische Punkte',
          data: [0],
          borderWidth: 1,
          borderColor: colors[1],
          backgroundColor: colors[1]
        }

    ]
    },
    options: {
      plugins: {
        title: {
          display: true,          // 👈 Titel aktivieren
          text: 'Punkteverlauf im Satz', 
          font: {
            size: 24,             // Schriftgröße
            weight: 'bold'
          },
          color: '#000',          // Schriftfarbe
          padding: {
            top: 10,
            bottom: 0
          },
          align: 'center'         // oder 'start' | 'end'
        },
      },
      scales: {
        y: {
          min: 0,   // Minimumwert fixieren
          max: 30,  // Maximumwert fixieren
          ticks: {
            stepSize: 5 // optional: Schritte auf der Y-Achse
          }
        }
      },
      maintainAspectRatio: false
    }
  });
});

function change_chart(direction = "next") {
  // Aktuell sichtbares Chart finden
  let currentChart = arr_charts.find(id => $('#' + id).is(':visible'));
  let currentIndex = arr_charts.indexOf(currentChart);

  // Falls kein Chart sichtbar ist (z. B. beim ersten Aufruf)
  if (currentIndex === -1) currentIndex = 0;

  // Nächstes oder vorheriges Chart bestimmen
  let nextIndex;
  if (direction === "previous") {
    nextIndex = (currentIndex - 1 + arr_charts.length) % arr_charts.length;
  } else {
    nextIndex = (currentIndex + 1) % arr_charts.length;
  }

  // Alle Charts ausblenden
  arr_charts.forEach(id => $('#' + id).hide());

  // Nächstes Chart anzeigen (mit kleiner Fade-Animation)
  $('#' + arr_charts[nextIndex]).fadeIn(300);

  // Aktuelle Chart-ID in der Session speichern
  sessionStorage.setItem("currentChart", arr_charts[nextIndex]);
}


function getLevelOptions(...path) {
  let current = tree;

  // Gehe durch den Pfad, um das gewünschte Level zu erreichen
  for (let key of path) {
    if (current && current[key]) {
      current = current[key];
    } else {
      return []; // Pfad existiert nicht
    }
  }

  // Wenn Level stimmt, gib die Schlüssel zurück
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

  if(winner=='trainee') {
    txt += "Unser Punkt durch ";
    if(path[0]) {
      txt += path[0]; 
      if(player) {
        if (path[0] === 'Fehler') {
            var pointPlayer = (player === 'player' ? match.opponentName : match.opponentPartnerName);
        } else {
            var pointPlayer = (player === 'player' ? match.traineeName : match.traineePartnerName);
        }
        let temp = document.createElement('div');
        temp.innerHTML = pointPlayer;
        pointPlayer = temp.textContent;
        txt+= " von " + pointPlayer;
        if(path[1]) txt+= " beim " + path[1]; else txt+= " beim..."
      } else txt+= " von..."
    } else txt += "..."
    tempDiv.innerHTML = $('#point_for_trainee').prop('outerHTML');
  } else {
    txt += "Punkt für den Gegner durch ";
    if(path[0]) {
      txt += path[0]; 
      if(player) {
        if (path[0] === 'Fehler') {
            var pointPlayer = (player === 'player' ? match.traineeName : match.traineePartnerName);
        } else {
            var pointPlayer = (player === 'player' ? match.opponentName : match.opponentPartnerName);
        }
        let temp = document.createElement('div');
        temp.innerHTML = pointPlayer;
        pointPlayer = temp.textContent;
        txt+= " von " + pointPlayer;
        if(path[1]) txt+= " beim " + path[1]; else txt+= " beim..."
      } else txt+= " von..."
    } else txt += "..."
    tempDiv.innerHTML = $('#point_for_opponent').prop('outerHTML');
  }
  tempDiv.firstChild.removeAttribute('id');
  htmlCode = tempDiv.firstChild.outerHTML;
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
      player: player
    });

    let main_reason = path[0];
    let reason_path_text = path.slice(1)
      .filter(p => p && p.trim() !== '')
      .join(' / ');

    if ($('#points_table tbody').length === 0) $('#points_table').append('<tbody></tbody>');

    if(winner=='trainee') {
      if (main_reason === 'Fehler') {
          var pointPlayer = (player === 'player' ? match.opponentName : match.opponentPartnerName);
          var errorStyle = 'border:5px solid red;';
      } else {
          var pointPlayer = (player === 'player' ? match.traineeName : match.traineePartnerName);
          var errorStyle = 'border:5px solid green;';
      }
      $('#points_table tbody').prepend(`
        <tr id='row_${id}'>
          <td class='left'>${htmlCode}</td>
          <td class='middle'>
            <div style="display: flex; flex-direction: column; gap: 5px; padding: 5px;border-radius:5vw; ${errorStyle}">
              <div style="display: flex; justify-content: center; align-items: center;">${pointPlayer || ''}</div>
              <div><img class='delete' id='${id}' src='inc/imgs/delete.png' alt='Delete last entry' /></div>
            </div>
          </td>
          <td class='right'>${reason_path_text}</td>
        </tr>
      `);
    } else {
      if (main_reason === 'Fehler') {
          var pointPlayer = (player === 'player' ? match.traineeName : match.traineePartnerName);
          var errorStyle = 'border:5px solid red;';
      } else {
          var pointPlayer = (player === 'player' ? match.opponentName : match.opponentPartnerName);
          var errorStyle = 'border:5px solid green;';
      }
      $('#points_table tbody').prepend(`
        <tr id='row_${id}'>
          <td class='left'>${reason_path_text}</td>
          <td class='middle'>
            <div style="display: flex; flex-direction: column; gap: 5px; padding: 5px;border-radius:5vw; ${errorStyle}">
              <div style="display: flex; justify-content: center; align-items: center;">${pointPlayer || ''}</div>
              <div><img class='delete' id='${id}' src='inc/imgs/delete.png' alt='Delete last entry' /></div>
            </div>
          </td>
          <td class='right'>${htmlCode}</td>
        </tr>
      `);
    }

    $('img.delete').off('click');
    $('img.delete').on('click', (e) => delete_entry(e.currentTarget.id));

    update_stats();

    // ----------------------------
    // Save by AJAX
    // ----------------------------
    if(mode=='save') {
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
          alert(data);
        }
      });
    }

    return;
  }

  // ----------------------------
  // Zwischen Level 1 und 2 → Spieler/Partner Auswahl
  // ----------------------------
  if(winner=='trainee') {
    if(path[0]=='Fehler') {
      var p1 = match.opponentName;
      var p2 = match.opponentPartnerName;
    } else {
      var p1 = match.traineeName;
      var p2 = match.traineePartnerName;
    }
  } else {
    if(path[0]=='Fehler') { 
      var p1 = match.traineeName;
      var p2 = match.traineePartnerName;
    } else {
      var p1 = match.opponentName;
      var p2 = match.opponentPartnerName;
    }
  }

  if(path.length===1 && player===null) {
    if($('#playerPartner').length>0)
    {
      let btn_height = 5;

      // Zurück-Button oben
      txt += `<button class='level_option' data-level='back' style='font-size:${btn_height*0.6}vh;width:75vw;height:${btn_height}vh;margin:${btn_height/10}vh;background-color:gray'>Zurück</button>`;

      txt += `<div class='level_option' data-level='player'>${p1}</div>`;
      txt += `<div class='level_option' data-level='partner'>${p2}</div>`;
      txt += `<div style='clear:both;margin-bottom:1vh;'></div>`;

      $('#myModalText').html(txt);
      $('#myModal').show();

      $('.level_option').on('click', (e) => {
        const level = e.currentTarget.getAttribute('data-level');
        if(level==='back'){
          new_point(null,winner,null,...path.slice(0,-1));
        } else {
          new_point(null,winner,level,...path); // Player setzen, Pfad bleibt
        }
      });
    } else {
      new_point(null,winner,'player',...path);
      return
    }
    return;
  }

  // ----------------------------
  // Normale Level-Auswahl
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

  $('#myModalText').html(txt);
  $('#myModal').show();

  $('.level_option').on('click', (e) => {
    const level = e.currentTarget.getAttribute('data-level');
    let newPath;

    if(level==='back'){
      // Speziell: wenn Player gesetzt und zurück auf Level 1 → Player löschen
      if(path.length===1 && player!==null && $('#playerPartner').length>0) {
        new_point(null,winner,null,...path);
        return;
      }
      newPath = path.slice(0,-1);
    } else {
      newPath = [...path, level];
    }

    new_point(null,winner,player,...newPath);
  });
}

function update_stats() {
  chartMainReasons.data.datasets[0].data = match.getPointStatistics('trainee');
  chartMainReasons.update();
  
  chartMainReasonsOpponent.data.datasets[0].data = match.getPointStatistics('opponent');
  chartMainReasonsOpponent.update();

  const newDataWin = match.getWinnerChartData("trainee", winStrokes, winAllowedCombinations);
  chartStrokes.data.datasets.forEach((ds, i) => {
      for (let j = 0; j < ds.data.length; j++) {
          ds.data[j] = newDataWin.datasets[i].data[j];
      }
  });
  chartStrokes.update();

  const newDataError = match.getErrorChartData("trainee", errorStrokes, errorAllowedCombinations);
  chartOuts.data.datasets.forEach((ds, i) => {
      for (let j = 0; j < ds.data.length; j++) {
          ds.data[j] = newDataError.datasets[i].data[j];
      }
  });
  chartOuts.update();
  
  const newDataWinOpponent = match.getWinnerChartData("opponent", winStrokes, winAllowedCombinations);
  chartStrokesOpponent.data.datasets.forEach((ds, i) => {
      for (let j = 0; j < ds.data.length; j++) {
          ds.data[j] = newDataWinOpponent.datasets[i].data[j];
      }
  });
  chartStrokesOpponent.update();

  const newDataErrorOpponent = match.getErrorChartData("opponent", errorStrokes, errorAllowedCombinations);
  chartOutsOpponent.data.datasets.forEach((ds, i) => {
      for (let j = 0; j < ds.data.length; j++) {
          ds.data[j] = newDataErrorOpponent.datasets[i].data[j];
      }
  });
  chartOutsOpponent.update();


  const prog = match.getPointProgress();
  chartPointIncreases.data.datasets[0].data = prog.trainee;
  chartPointIncreases.data.datasets[1].data = prog.opponent;
  chartPointIncreases.data.labels = prog.trainee.map((_, i) => i + 1); 
  chartPointIncreases.update();

  $('#points').text(match.getScore().text);
}