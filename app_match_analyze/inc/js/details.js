const tree = {};
let chart;
let mypoints = 0;
let opponentpoints = 0;
let set;
let ma_id;
let mode='load';

const reasons_data = [0,0,0];
const reasons_data_opponent = [0,0,0];
const strokes_data = [0,0,0,0,0,0,0];
const labels = [0];
const stats = {
  Clear: { OutHinten: 0, OutRechts: 0, OutLinks: 0, InsNetz: 0 },
  Drop: { OutHinten: 0, OutRechts: 0, OutLinks: 0, InsNetz: 0 },
  Smash: { OutHinten: 0, OutRechts: 0, OutLinks: 0, InsNetz: 0 },
  Drive: { OutHinten: 0, OutRechts: 0, OutLinks: 0, InsNetz: 0 },
  Kill: { OutHinten: 0, OutRechts: 0, OutLinks: 0, InsNetz: 0 },
  Netzdrop: { OutHinten: 0, OutRechts: 0, OutLinks: 0, InsNetz: 0 },
  Lift: { OutHinten: 0, OutRechts: 0, OutLinks: 0, InsNetz: 0 },
  Anspiel: { OutHinten: 0, OutRechts: 0, OutLinks: 0, InsNetz: 0 }
};

const colors = [
  "#E63946", // warmes Rot
  "#F1A208", // kräftiges Goldgelb
  "#52B788", // weiches Grün
  "#277DA1", // kühles Blau
  "#9C89B8", // sanftes Violett
  "#F3722C", // warmes Orange
  "#43AA8B", // Türkisgrün
  "#4D908E", // Graublau
  "#F9C74F", // helles Gelb
  "#577590"  // gedecktes Marineblau
];

function countErrors(path) {
  const [main_reason, stroke, errorType, direction] = path;

  if (main_reason !== 'Fehler') return; // nur Fehler zählen

  // Prüfen, ob Schlag im stats-Objekt existiert
  if (!stats[stroke]) {
    return;
  }

  // Fehlerarten unterscheiden
  if (errorType === 'Out') {
    if (direction === 'Hinten') stats[stroke].OutHinten++;
    else if (direction === 'Rechts') stats[stroke].OutRechts++;
    else if (direction === 'Links') stats[stroke].OutLinks++;
  } else if (errorType === 'Netz') {
    stats[stroke].InsNetz++;
  }
}

function prepareChartData(stats) {
  const strokes = Object.keys(stats); // z. B. ["Clear", "Drop", "Smash", ...]
  const categories = ['OutHinten', 'OutRechts', 'OutLinks', 'InsNetz'];

  const datasets = categories.map((cat, i) => ({
    label: cat,
    data: strokes.map(stroke => stats[stroke][cat] || 0),
    backgroundColor: colors[i % colors.length],
    borderColor: colors[i % colors.length].replace('0.6', '1'),
    borderWidth: 1,
    fill: false,  // 🔹 wichtig für Line-Charts (keine Fläche unter der Linie)
    tension: 0.3  // 🔹 sanfte Kurve, falls du Line-Chart verwendest
  }));

  return { labels: strokes, datasets };
}


function delete_entry(id) {
  let my_url = 'index.php?ajax=delete_point&point_id=' + id
      + '&ma_id=' + ma_id
      + '&set=' + set;

  $.ajax({ url: my_url }).done(
    function(data)
    {
      if(data!='') { alert(data); }
      location.reload();
  });
}

//Event handlers
$(document).ready(function() {
  $('.close').on('click', () => $('#myModal').hide());
  $('#point_for_trainee').on('click', (e) => new_point(null,'trainee'));
  $('#point_for_opponent').on('click', (e) => new_point(null,'opponent'));

  $('#chartMainReasons').show();
  $('#chartStrokes').hide();
  $('#chartPointIncreases').hide();
  $('#chartMainReasonsOpponent').hide();
  $('#chartOuts').hide();

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
      labels: ['Fehler des Gegners','Gewinner', 'Glück'],
      datasets: [{
        data: reasons_data,
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
      maintainAspectRatio: false,
      onClick: (e) => {
            change_chart();
        }
    }
  });

  chartMainReasonsOpponent = new Chart(document.getElementById('chartMainReasonsOpponent'), {
    type: 'pie',
    data: {
      labels: ['Meine Fehler','Gewinner', 'Glück'],
      datasets: [{
        data: reasons_data_opponent,
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
      maintainAspectRatio: false,
      onClick: (e) => {
            change_chart();
        }
    }
  });

  chartStrokes = new Chart(document.getElementById('chartStrokes'), {
    type: 'pie',
    data: {
      labels: ['Angriffsclear', 'Drop', 'Smash', 'Drive', 'Netz', 'Täuschung', 'Kill'],
      datasets: [{
        data: strokes_data,
        borderWidth: 1,
        backgroundColor: colors
      }]
    },
    options: {
      plugins: {
        title: {
          display: true,          // 👈 Titel aktivieren
          text: 'Meine Gewinnschläge nach Schlagarten', 
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
      maintainAspectRatio: false,
      onClick: (e) => {
            change_chart();
        }
    }
  });

  chartOuts = new Chart(document.getElementById('chartOuts'), {
    type: 'bar',
    data: prepareChartData(stats),
    options: {
      plugins: {
        title: {
          display: true,          // 👈 Titel aktivieren
          text: 'Meine Fehler nach Schlag und Ort', 
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
        legend: {
          display: true
        }
      },
      scales: {
        y: {
          min: 0,   // Minimumwert fixieren
          max: 7,  // Maximumwert fixieren
          ticks: {
            stepSize: 1 // optional: Schritte auf der Y-Achse
          }
        }
      },

      maintainAspectRatio: false,
      onClick: (e) => {
            change_chart();
        }
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
      maintainAspectRatio: false,
      onClick: (e) => {
            change_chart();
        }
    }
  });
});

function change_chart()
{
  if($('#chartMainReasons').is(':visible')) {
    $('#chartMainReasonsOpponent').show();
    $('#chartMainReasons').hide();
  } else if($('#chartMainReasonsOpponent').is(':visible')) {
    $('#chartStrokes').show();
    $('#chartMainReasonsOpponent').hide();
  } else if($('#chartStrokes').is(':visible')) {
    $('#chartOuts').show();
    $('#chartStrokes').hide();
  } else if($('#chartOuts').is(':visible')) {
    $('#chartPointIncreases').show();
    $('#chartOuts').hide();
  } else {
    $('#chartMainReasons').show();
    $('#chartPointIncreases').hide();
  }
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


function new_point(id,winner, ...path)
{
  if(id===null) {
    // Dummy-ID für neue Punkte
    id = 'dummy_' + Date.now() + '_' + Math.floor(Math.random() * 1000);
  }
  let txt = "<h1>";
  let reason_path = "";
  if(path[0]) { reason_path += path[0]; }
  if(path[1]) { reason_path += ' beim ' + path[1]; }
  if(winner=='trainee') {
    txt += "Unser Punkt durch " + reason_path + "...";
    var htmlCode = $('#point_for_trainee').prop('outerHTML');
  } else {
    txt += "Punkt für den Gegner durch " + reason_path + "...";
    var htmlCode = $('#point_for_opponent').prop('outerHTML');
  }
  txt += "</h1>";
  let arr_options = getLevelOptions(...path);
  if(arr_options.length===0) {
    // Ende des Pfades erreicht, Punkt speichern
    $('#myModal').hide();
    let main_reason = path[0];
    let reason_path = path.slice(1)
      .filter(p => p && p.trim() !== '') // nur nicht-leere Werte behalten
      .join(' / ');
    if ($('#points_table tbody').length === 0) { $('#points_table').append('<tbody></tbody>'); }

    if(winner=='trainee') {
      if(main_reason=='Fehler') { reasons_data[0]++; }
      mypoints++;
      if(main_reason=='Gewinnschlag') { 
        reasons_data[1]++; 
        if(path[1]=='Angriffsclear') { strokes_data[0]++; }
        if(path[1]=='Drop') { strokes_data[1]++; }
        if(path[1]=='Smash') { strokes_data[2]++; }
        if(path[1]=='Drive') { strokes_data[3]++; }
        if(path[1]=='Netzdrop') { strokes_data[4]++; }
        if(path[1]=='Täuschung') { strokes_data[5]++; }
        if(path[1]=='Kill') { strokes_data[6]++; }
      }
      
      if(main_reason=='Glück') { reasons_data[2]++; }

      $('#points_table tbody').prepend(`<tr><td class='left'>${htmlCode}</td><td class='middle'>${main_reason}<br/><img  class='delete' id='${id}' src='inc/imgs/delete.png' alt='Delete last entry' /></td><td class='right'>${reason_path}</td></tr>`);
      $('#points_player').text(parseInt($('#points_player').text()) + 1);
    } else {
      opponentpoints++;
      if(main_reason=='Fehler') { 
        reasons_data_opponent[0]++; 
        countErrors(path);
      }
      if(main_reason=='Gewinnschlag') { reasons_data_opponent[1]++; }
      if(main_reason=='Glück') { reasons_data_opponent[2]++; }
      $('#points_table tbody').prepend(`<tr><td class='left'>${reason_path}</td><td class='middle'>${main_reason}<br/><img class='delete' id='${id}' src='inc/imgs/delete.png' alt='Delete last entry' /></td><td class='right'>${htmlCode}</td></tr>`);
      $('#points_opponent').text(parseInt($('#points_opponent').text()) + 1);
    }

    const nextLabel = chartPointIncreases.data.labels.length;
    chartPointIncreases.data.labels.push(nextLabel);
    chartPointIncreases.data.datasets[0].data.push(mypoints);
    chartPointIncreases.data.datasets[1].data.push(opponentpoints);
    $('img.delete').off('click');
    $('img.delete').on('click', (e) => delete_entry(e.currentTarget.id));
      
    chartMainReasons.update();
    chartStrokes.update();
    chartPointIncreases.update();
    chartMainReasonsOpponent.update();
    chartOuts.data = prepareChartData(stats);
    chartOuts.update();
    if(mode=='save') {
      let my_url = 'index.php?ajax=save_point&winner=' + winner 
      + '&level1=' + encodeURIComponent(main_reason) 
      + '&level2=' + encodeURIComponent(path[1] || '')
      + '&level3=' + encodeURIComponent(path[2] || '')
      + '&level4=' + encodeURIComponent(path[3] || '')
      + '&ma_id=' + ma_id
      + '&set=' + set
      + '&point_id=' + id;

      $.ajax({ url: my_url }).done(function(data) {
          if (data.startsWith('OK>')) {
              // Zahl hinter "OK>"
              let newId = data.split('>')[1];

              // img mit class "delete" finden, das aktuell die alte point_id als id hat
              $('img.delete#' + id).attr('id', newId);

          } else if (data != '') {
              alert(data); // Andere Meldungen anzeigen
          }
      });    
    }
  }
  else {
    let i = 0;
    let btn_height = 50 / arr_options.length;
    for(const option of arr_options) {
      let color = colors[i % colors.length];
      txt += `<button class='level1_option' style='font-size:${btn_height*1.5}pt;width:75vw;height:${btn_height}vh;margin:1vw;background-color:${color}' data-level1='${option}'>${option}</button>`;
      i++;
    }
    $('#myModalText').html(txt); 
    $('#myModal').show();
    $('.level1_option').on('click', (e) => {
      const newPath = [...path, e.currentTarget.getAttribute('data-level1')];
      new_point(null,winner, ...newPath);
    });
  }

}