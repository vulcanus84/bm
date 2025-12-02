const errorStrokes = ["Anspiel","Clear", "Drop", "Drive", "Smash", "Lift", "Netzdrop", "Kill","Defense","Fehleinschätzung"];
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
  "#52B788", // weiches Grün
  "#F1A208", // kräftiges Goldgelb
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
  'chartWinners',
  'chartWinnersOpponent',
  'chartErrors',
  'chartErrorsOpponent',
  'chartPointIncreases'
];

let font = { size: 24, weight: 'bold' }
let chart;

//Event handlers
$(document).ready(function() {
  $('#btnNext').on('click', () => change_chart('next'));
  $('#btnPrev').on('click', () => change_chart('previous'));

  getAllCharts();
});

function getAllCharts() {
  const wrapper = document.getElementById('chartWrapper');
  // Dynamische Canvas-Erzeugung
  arr_charts.forEach(id => {
    const canvas = document.createElement('canvas');
    canvas.id = id;
    canvas.style.display = "none";
    wrapper.appendChild(canvas);
  });

  let savedChart = (sessionStorage.getItem("currentChart") || "chartMainReasons").replace('#', '');
  sessionStorage.setItem("currentChart",savedChart);

  // Gespeichertes Chart anzeigen oder Standard (erstes)
  if (savedChart && arr_charts.includes(savedChart)) {
    $('#' + savedChart).show();
  } else {
    $('#' + arr_charts[0]).show();
  }

  getChartMainReasons('trainee');
  getChartMainReasons('opponent');
  getchartWinners('trainee');
  getchartWinners('opponent');
  getchartErrors('trainee');
  getchartErrors('opponent');
  getChartPointIncreases();
}

function getChartMainReasons(side) {
  // Titel dynamisch
  const titles = {
    trainee: match.mode === 'single' ? "Punktgewinne " + match.traineeNameTxt + " durch" : "Punktgewinne " + match.traineeNameTxt + "/" + match.traineePartnerNameTxt + " durch",
    opponent: match.mode === 'single' ? "Punktgewinne " + match.opponentNameTxt + " durch" : "Punktgewinne " + match.opponentNameTxt + "/" + match.opponentPartnerNameTxt + " durch"
  };

  // Element-ID und Chart-Variable sind gleich
  const chartName = side === 'trainee' ? 'chartMainReasons' : 'chartMainReasonsOpponent';

  let config = {
    type: 'pie',
    data: {
      labels: [],
      datasets: [{
        data: [],
        borderWidth: 1,
        backgroundColor: colors
      }]
    },
    options: {
      plugins: {
        title: { text: titles[side] }  // nur Titeltext gesetzt
      }
    }
  };

  config = applyStandardChartOptions(config);  // Standardwerte ergänzen
  window[chartName] = new Chart(document.getElementById(chartName), config);
}

function getchartWinners(side) {
  // Titel dynamisch
  const titles = {
    trainee: match.mode === 'single' ? "Gewinnschläge von " + match.traineeNameTxt : "Gewinnschläge " + match.traineeNameTxt + "/" + match.traineePartnerNameTxt,
    opponent: match.mode === 'single' ? "Gewinnschläge von " + match.opponentNameTxt : "Gewinnschläge " + match.opponentNameTxt + "/" + match.opponentPartnerNameTxt
  };

  // Element-ID und Chart-Variable sind gleich
  const chartName = side === 'trainee' ? 'chartWinners' : 'chartWinnersOpponent';

  // Config erstellen
  let config = {
    type: 'bar',
    data: {
      labels: winStrokes,
      datasets: winAllowedCombinations.map((c, i) => ({
        label: c.extra ? `${c.detail} ${c.extra}` : c.detail,
        data: winStrokes.map(() => 0),
        backgroundColor: colors[i % colors.length],
        extra: c.extra
      }))
    },
    options: {
      plugins: {
        title: { text: titles[side] },
        tooltip: {
          callbacks: {
            label: ctx => `${ctx.dataset.label}: ${ctx.raw}`
          }
        }
      }
    }
  };

  config = applyStandardChartOptions(config);

  // Chart erstellen und global speichern
  window[chartName] = new Chart(document.getElementById(chartName), config);
}


function getchartErrors(side) {

  // Titel dynamisch
  const titles = {
    trainee: match.mode === 'single' ? "Fehler von " + match.traineeNameTxt : "Fehler " + match.traineeNameTxt + "/" + match.traineePartnerNameTxt,
    opponent: match.mode === 'single' ? "Fehler von " + match.opponentNameTxt : "Fehler " + match.opponentNameTxt + "/" + match.opponentPartnerNameTxt
  };

  // Element-ID und Chart-Variable sind gleich
  const chartName = side === 'trainee' ? 'chartErrors' : 'chartErrorsOpponent';

  let config = {
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
        plugins: {
            title: { text: titles[side] },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        const ds = context.dataset;
                        const label = ds.label;
                        return `${label}: ${context.raw}`;
                    }
                }
            }
        }
    }
  }
  config = applyStandardChartOptions(config);
  window[chartName] = new Chart(document.getElementById(chartName), config);
}

function getChartPointIncreases() {
  let title = "Punkteverlauf";
  let label1 = match.mode === 'single' ? "Punkte von " + match.traineeNameTxt : "Punkte von " + match.traineeNameTxt + "/" + match.traineePartnerNameTxt;
  let label2 = match.mode === 'single' ? "Punkte von " + match.opponentNameTxt : "Punkte von " + match.opponentNameTxt + "/" + match.opponentPartnerNameTxt;

  let config = {
    type: 'line',
    data: {
      labels: [...labels],
      datasets: [
        {
          label: label1,
          data: [0],
          borderWidth: 1,
          borderColor: colors[0],
          backgroundColor: colors[0]
        },
        {
          label: label2,
          data: [0],
          borderWidth: 1,
          borderColor: colors[1],
          backgroundColor: colors[1]
        }

    ]
    },
    options: {
      plugins: {
        title: { text: title },
      },
      scales: {
        y: {
          min: 0,   // Minimumwert fixieren
          max: 30,  // Maximumwert fixieren
          ticks: {
            stepSize: 5 // optional: Schritte auf der Y-Achse
          }
        }
      }
    }
  }
  config = applyStandardChartOptions(config);
  chartPointIncreases = new Chart(document.getElementById('chartPointIncreases'), config);
}

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

function update_stats() {
  chartMainReasons.data.labels = match.getPointStatisticsLabels('trainee');
  chartMainReasons.data.datasets[0].data = match.getPointStatistics('trainee');
  chartMainReasons.update();
  
  chartMainReasonsOpponent.data.labels = match.getPointStatisticsLabels('opponent');
  chartMainReasonsOpponent.data.datasets[0].data = match.getPointStatistics('opponent');
  chartMainReasonsOpponent.update();

  const newDataWin = match.getWinnerChartData("trainee", winStrokes, winAllowedCombinations);
  chartWinners.data.datasets.forEach((ds, i) => {
      for (let j = 0; j < ds.data.length; j++) {
          ds.data[j] = newDataWin.datasets[i].data[j];
      }
  });
  chartWinners.update();

  const newDataError = match.getErrorChartData("trainee", errorStrokes, errorAllowedCombinations);
  chartErrors.data.datasets.forEach((ds, i) => {
      for (let j = 0; j < ds.data.length; j++) {
          ds.data[j] = newDataError.datasets[i].data[j];
      }
  });
  chartErrors.update();
  
  const newDataWinOpponent = match.getWinnerChartData("opponent", winStrokes, winAllowedCombinations);
  chartWinnersOpponent.data.datasets.forEach((ds, i) => {
      for (let j = 0; j < ds.data.length; j++) {
          ds.data[j] = newDataWinOpponent.datasets[i].data[j];
      }
  });
  chartWinnersOpponent.update();

  const newDataErrorOpponent = match.getErrorChartData("opponent", errorStrokes, errorAllowedCombinations);
  chartErrorsOpponent.data.datasets.forEach((ds, i) => {
      for (let j = 0; j < ds.data.length; j++) {
          ds.data[j] = newDataErrorOpponent.datasets[i].data[j];
      }
  });
  chartErrorsOpponent.update();


  const prog = match.getPointProgress();
  chartPointIncreases.data.datasets[0].data = prog.trainee;
  chartPointIncreases.data.datasets[1].data = prog.opponent;
  chartPointIncreases.data.labels = prog.trainee.map((_, i) => i + 1); 
  chartPointIncreases.update();

  $('#points').text(match.getScore().text);
}


/**
 * Fügt einem Chart-Konfigurationsobjekt Standardwerte hinzu:
 * - globale Defaults für alle Chart-Typen
 * - zusätzlich Typ-spezifische Defaults
 * 
 * @param {Object} config - Das Chart-Konfigurationsobjekt
 * @returns {Object} config - Mit Defaults ergänzt, ohne Überschreiben bestehender Werte
 */
function applyStandardChartOptions(config) {
  // Globale Defaults für alle Charts
  const globalDefaults = {
    options: {
      plugins: {
        title: {
          display: true,
          font: { size: 24, weight: 'bold' },
          color: '#000',
          padding: { top: 10, bottom: 0 },
          align: 'center'
        }
      },
      maintainAspectRatio: false
    }
  };

  // Typ-spezifische Defaults
  const typeDefaults = {
    pie: {
      options: {
        plugins: {
          tooltip: {
            callbacks: {
              label: ctx => `${ctx.label}: ${ctx.raw}`
            }
          }
        }
      }
    },
    bar: {
      options: {
        scales: {
          x: { stacked: true },
          y: { stacked: true, beginAtZero: true, ticks: { stepSize: 1 }, suggestedMax: 5 }
        },
        plugins: {
          tooltip: {
            callbacks: {
              label: ctx => `${ctx.dataset.label}: ${ctx.raw}`
            }
          }
        },
        animation: { duration: 500, easing: 'linear', loop: false }
      }
    },
    line: {
      options: {
        scales: {
          y: { min: 0, max: 30, ticks: { stepSize: 5 } }
        }
      }
    }
  };

  // Rekursive Merge-Funktion, die nur fehlende Werte ergänzt
  function merge(target, source) {
    for (const key in source) {
      if (source.hasOwnProperty(key)) {
        if (typeof source[key] === 'object' && source[key] !== null && !Array.isArray(source[key])) {
          if (!target[key]) target[key] = {};
          merge(target[key], source[key]);
        } else {
          if (target[key] === undefined) target[key] = source[key];
        }
      }
    }
  }

  // Zuerst globale Defaults, dann Typ-spezifische Defaults anwenden
  merge(config, globalDefaults);
  if (typeDefaults[config.type]) merge(config, typeDefaults[config.type]);

  return config;
}

