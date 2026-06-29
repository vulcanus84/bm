let charts = {};
let timer;
let runningChartId = null;
let pendingStart = null;

function onChartClick(zgbCode, planSeconds) {
    const prefix = zgbCode.split("_")[0];
    const chartId = zgbCode;

    pendingStart = { chartId, zgbCode, planSeconds };
    
    if (runningChartId !== null && runningChartId !== chartId) {
        alert("Es läuft bereits ein Timer für eine andere Aufgabe. Bitte stoppen Sie diesen zuerst.");
        return;
    }

    switch (prefix) {

        case "load":
            openConfirmationOverlay();
            break;
        case "sort":
            openConfirmationOverlay();
            break;
        case "drive":
            openKmOverlay();
            break;
        case "delivery":
            openKmOverlay();
            break;
    }
}
function makeChart(kw_id, zgb_id, id, plan, real, title) {

    const isOver = real > plan;
    const ratio = Math.ceil(real / plan);
    zgb_id = zgb_id;
    
    const chart = new Chart(document.getElementById(id), {
        type: 'doughnut',
        data: {
            labels: isOver
                ? ['Überstunden', '']
                : ['Arbeitszeit', 'Restzeit'],

            datasets: isOver
                ? [{
                    // 100% Plan + extra Overtime
                    data: [real-plan, plan*ratio-real],
                    backgroundColor: ['#ef4444','#22c55e'],
                    borderWidth: 0
                }]
                : [{
                    // Normaler Fortschritt
                    data: [real, plan-real],
                    backgroundColor: ['#22c55e', '#e5e7eb'],
                    borderWidth: 0
                }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            resizeDelay: 0,
            cutout: '40%',

            plugins: {
                legend: { display: false },
                title: {
                    display: true,
                    text: title
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            context.raw = context.raw / 60; // Convert hours to seconds
                            return " " + context.raw.toFixed(0) + ' min';
                        }
                    }
                }
            }
        }
    });

    chart.meta = {
      kw_id: kw_id,
      zgb_id: zgb_id
    };
    charts[id] = chart;
}

function startLiveProgress(chartId, zgbCode, planSeconds, km = null) {

    const chart = charts[chartId];
    const timerEl = document.getElementById("timer_" + zgbCode);
    const startTime = Date.now();
    chart.meta.startTime = Date.now();

    if (!chart || !timerEl) return;

    if (timer) {
        if (runningChartId !== chartId) {
            alert("Es läuft bereits ein Timer für eine andere Aufgabe. Bitte stoppen Sie diesen zuerst.");
            return;
        }
        clearInterval(timer);

        // ⛔ STOP → Endzeit speichern
        fetch("log_time.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                action: "stop",
                kw_id: chart.meta.kw_id,
                zgb_id: chart.meta.zgb_id,
                km: km,
                type: zgbCode.split("_")[0],
            })
        });

        timer = null;
        runningChartId = null;
        return;

    } else {
        // ▶ START → Startzeit speichern
        fetch("log_time.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                action: "start",
                kw_id: chart.meta.kw_id,
                zgb_id: chart.meta.zgb_id,
                chart_id: chartId,
                km: km,
                type: zgbCode.split("_")[0]
            })
        });
        runningChartId = chartId;
    }
    let elapsed = chart.data.datasets[0].data[0]; // Startwert aus dem Chart

        timer = setInterval(() => {

        const now = Date.now();
        const elapsed = Math.floor((now - chart.meta.startTime) / 1000);

        // ⏱️ Timer Anzeige mm:ss
        const mm = String(Math.floor(elapsed / 60)).padStart(1, '0');
        const ss = String(elapsed % 60).padStart(2, '0');
        timerEl.innerText = `${mm}:${ss}`;

        // 🎯 Ratio gegen Plan
        let ratio = elapsed / planSeconds;

        if (ratio <= 1) {

            // 🟢 innerhalb Plan
            chart.data.datasets[0].data = [
                elapsed,
                planSeconds - elapsed
            ];

            chart.data.datasets[0].backgroundColor = [
                '#22c55e',
                '#e5e7eb'
            ];

        } else { 
          if (ratio <= 2) {

            // 🔴 Overload
            chart.data.datasets[0].data = [
                elapsed-planSeconds,
                planSeconds*Math.ceil(ratio)-elapsed
            ];

            chart.data.datasets[0].backgroundColor = [
                '#ef4444',
                '#22c55e'
            ];
        } else {
            // ⚠️ Extreme Overload
            chart.data.datasets[0].data = [
              1,0
            ];
        }
      }

        chart.update('none');

    }, 1000);
}

function openConfirmationOverlay() {
    const overlay = document.getElementById("confirmationOverlay");
    overlay.style.display = "flex";
}

function closeConfirmationOverlay() {
    document.getElementById("confirmationOverlay").style.display = "none";
}

function confirmed() {
    const { chartId, zgbCode, planSeconds } = pendingStart;
    pendingStart = null;
    closeConfirmationOverlay();

    // 👉 jetzt direkt starten, OHNE Rekursion
    startLiveProgress(chartId, zgbCode, planSeconds, null);
}

function openKmOverlay() {
    const overlay = document.getElementById("kmOverlay");
    const input = document.getElementById("kmInput");

    overlay.style.display = "flex";

    requestAnimationFrame(() => {
        setTimeout(() => {
            input.focus();
            input.click(); // wichtig für iOS
        }, 50);
    });
}

function closeKmOverlay() {
    document.getElementById("kmOverlay").style.display = "none";
    pendingStart = null;
}

function confirmKm() {
    const km = document.getElementById("kmInput").value;

    if (!km || km <= 0) {
        alert("Bitte gültige KM eingeben");
        return;
    }

    document.getElementById("kmOverlay").style.display = "none";

    const { chartId, zgbCode, planSeconds } = pendingStart;

    const kmValue = km;
    pendingStart = null;

    // 👉 jetzt direkt starten, OHNE Rekursion
    startLiveProgress(chartId, zgbCode, planSeconds, kmValue);
}

window.addEventListener("load", async () => {
  const kwId = document.body.dataset.kwId;
  const res = await fetch("get_active_timer.php?kw_id="+kwId);
  const active = await res.json();
  if(!active) return;

  runningChartId = active['log_chart_id'];
  const chart = charts[runningChartId];
  chart.meta.startTime = new Date(active.log_start_time).getTime();
  const timerEl = document.getElementById("timer_" + runningChartId);
  planSeconds = timerEl.dataset.planSeconds;


        timer = setInterval(() => {

        const now = Date.now();
        const elapsed = Math.floor((now - chart.meta.startTime) / 1000);

        // ⏱️ Timer Anzeige mm:ss
        const mm = String(Math.floor(elapsed / 60)).padStart(1, '0');
        const ss = String(elapsed % 60).padStart(2, '0');
        timerEl.innerText = `${mm}:${ss}`;

        // 🎯 Ratio gegen Plan
        let ratio = elapsed / planSeconds;

        if (ratio <= 1) {

            // 🟢 innerhalb Plan
            chart.data.datasets[0].data = [
                elapsed,
                planSeconds - elapsed
            ];

            chart.data.datasets[0].backgroundColor = [
                '#22c55e',
                '#e5e7eb'
            ];

        } else { 
          if (ratio <= 2) {

            // 🔴 Overload
            chart.data.datasets[0].data = [
                elapsed-planSeconds,
                planSeconds*Math.ceil(ratio)-elapsed
            ];

            chart.data.datasets[0].backgroundColor = [
                '#ef4444',
                '#22c55e'
            ];
        } else {
            // ⚠️ Extreme Overload
            chart.data.datasets[0].data = [
              1,0
            ];
        }
      }

        chart.update('none');

    }, 1000);


});