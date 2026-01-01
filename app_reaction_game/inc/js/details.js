let lastTimestamp = 0;
let chart = null;
let chartData = [];
let userId = null;
let excId = null;
let sensorsVisible = true;
let chartVisible = true;
let miscVisible = true;
let buttonsVisible = true;
let globalStatus = "idle";

function toggleButtons() {
    buttonsVisible = !buttonsVisible;
    $('#buttons_list').toggle(buttonsVisible);
    $('#button-toggle-icon').text(buttonsVisible ? '▼' : '▶');
}
function toggleSensors() {
    sensorsVisible = !sensorsVisible;
    $('#sensor_list').toggle(sensorsVisible);
    $('#sensor-toggle-icon').text(sensorsVisible ? '▼' : '▶');
}

function toggleChart() {
    chartVisible = !chartVisible;
    $('#chart_container').toggle(chartVisible);
    $('#chart-toggle-icon').text(chartVisible ? '▼' : '▶');
}

function toggleMisc() {
    miscVisible = !miscVisible;
    $('#misc_container').toggle(miscVisible);
    $('#misc-toggle-icon').text(miscVisible ? '▼' : '▶');
}

$(function() {
    const urlParams = new URLSearchParams(window.location.search);
    excId = urlParams.get('exc_id'); 

    initChart();
    setTimeout(() => poll(), 1000);
    $(document).on('change','#user_selection',function(){
        userId = $(this).val();
        $('#cube_infos').show();
        if(globalStatus=='idle') { $.get('', { ajax: 'set_user', userId: userId, excId: excId}); }
    });

    $(document).on('click', '#start', function() {
        var $btn = $(this);
        var status = $btn.val();

        // Toggle Status, Text und Klasse
        if (status === 'Starten') {
            status = 'Stoppen';
            $btn.removeClass('green').addClass('orange');
        } else {
            status = 'Starten';
            $btn.removeClass('orange').addClass('green');
        }

        // Update Button Text und Value
        $btn.val(status).text(status);

        // AJAX Request
        $.get('', { ajax: 'set_status', status: status });
    });

    $(document).on('click','#delete_data',function(){
        if(userId>0) { 
            $.get('', { ajax: 'delete_data', userId: userId }, function(res){
                if(res === "OK"){
                    // Chart leeren
                    chart.data.labels = [];
                    chart.data.datasets[0].data = [];
                    chart.update();
                    lastTimestamp = 0; // Reset timestamp
                }
            });
        } else {
            alert("Bitte zuerst einen Benutzer auswählen.");
        }
    });
});

function initChart() {
    const ctx = document.getElementById('reaction_chart').getContext('2d');

    chart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: [],
            datasets: []
        },
        options: {
            animation: false,
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                datalabels: {
                    color: '#000',
                    font: { weight: 'bold' },
                    formatter: function(value) {
                        if (!value || value === 0) return '';
                        return Number(value).toFixed(1) + ' s';
                    }
                },
                tooltip: {
                    callbacks: {
                        footer: function(items) {
                            const total = items.reduce((sum, item) => sum + item.parsed.y, 0);
                            return 'Summe: ' + total.toFixed(2) + ' s';
                        }
                    }
                }
            },
            scales: {
                x: {
                    stacked: true,
                    title: { display: true, text: 'Durchlauf' }
                },
                y: {
                    stacked: true,
                    beginAtZero: true,
                    title: { display: true, text: 'Dauer (s)' }
                }
            }
        }
    });
}


// Polling-Funktion
function poll() {
    $.getJSON('', { ajax: 'get_cube_infos' }, function (data) {
        $('#start').prop('disabled', true);
        $('#start').addClass('gray');
        $('#start').text('Start (nicht möglich, kein Sensor)');
        let currStatus = "idle";

        let html = '';
        let activeSensorFound = false;

        data.sensors.forEach(sensor => {
            // Zeit aus dem JSON in Date umwandeln
            const lastUpdate = new Date(sensor.rec_last_update.replace(' ', 'T'));
            const now = new Date();

            // Differenz in Sekunden
            const diffSeconds = Math.round((now - lastUpdate) / 1000);

            // Grün wenn <= 10 Sekunden
            const isFresh = diffSeconds <= 10;

            // Prüfen, ob Sensor bereits zugewiesen
            const isAssigned = sensor.rec_re_id == excId && sensor.rec_user_id == userId;
            if(isAssigned) { 
                if(sensor.rec_user_id == userId) {
                    if(sensor.rec_status !== 'running') {
                        if(isFresh) {
                            $('#start').prop('disabled', false); 
                            $('#start').addClass('green').removeClass('gray orange');
                            $('#start').text('Starten');
                            $('#start').val('Starten');
                            activeSensorFound = true;
                        } else {
                            if(!activeSensorFound) {                                
                                $('#start').prop('disabled', true); 
                                $('#start').addClass('gray').removeClass('green orange');
                                $('#start').text('Kein Start (Sensor inaktiv)'); 
                            }
                        }
                    } else {
                        $('#start').prop('disabled', false); 
                        $('#start').addClass('orange').removeClass('gray');
                        $('#start').text('Stoppen');
                        $('#start').val('Stoppen');
                        currStatus = "running";
                    }
                } else {
                    $('#start').prop('disabled', true); 
                    $('#start').addClass('gray').removeClass('green orange');
                    $('#start').text('Kein Start (anderer Benutzer)');
                    currStatus = "running";
                }
            }

            // Sensor-Card HTML anhängen
            let statusHtml = '';
            let cardClass = '';

            if (sensor.rec_status === 'running') {
                statusHtml = '🟠 Running';
                cardClass = 'running';
            } else if (isFresh) {
                statusHtml = `🟢 Aktiv (${diffSeconds}s)`;
                cardClass = 'active';
            } else {
                // Inaktiv
                statusHtml = diffSeconds > 120
                    ? '🔴 Inaktiv'
                    : `🔴 Inaktiv (${diffSeconds}s)`;
                cardClass = 'inactive';
            }

            html += `
                <div class="sensor-card ${cardClass}">
                    <div style="
                        display: grid;
                        grid-template-columns: auto 1fr auto 1fr;
                        column-gap: 10px;
                        row-gap: 2px;
                        align-items: center;
                        margin-bottom: 8px;
                        font-size: 13px;
                        line-height: 1.2;
                    ">
                        <strong>MAC:</strong><span>${sensor.rec_mac}</span>
                        <strong>Übung:</strong><span>${sensor.re_title}</span>

                        <strong>Nächste Pos.:</strong><span>${sensor.rep_pos_id}</span>
                        <strong>Ablauf:</strong><span>${sensor.rec_sequence}</span>

                        <strong>Distanz:</strong><span>${sensor.rec_distance}</span>
                        <strong>Spieler:</strong><span>${sensor.user_account}</span>

                        <strong>Status:</strong>
                        <span style="grid-column: span 3;">
                            ${statusHtml}
                        </span>
                    </div>

                    <button
                        class="${(!isFresh || isAssigned || sensor.rec_status === 'running') ? 'disabled' : 'active'}"
                        ${(!isFresh || isAssigned || sensor.rec_status === 'running') ? 'disabled' : ''}
                        ${isAssigned ? '' : `onclick="assignSensor('${sensor.rec_mac}', ${excId})"`}
                    >
                        ${isAssigned ? 'Bereits zugewiesen' : 'Sensor zuweisen'}
                    </button>
                </div>
            `;
        });

        // Alle Sensoren gleichzeitig einfügen
        $('#sensor_list').html(html);
        globalStatus = currStatus;
    });

    if(userId>0) {
        $.getJSON("check.php", { last: lastTimestamp, userId: userId, excId: excId })
        .done(function(data) {
            console.log("Daten aktualisiert. Letzter Timestamp: " + lastTimestamp + " von  UserID: " + data.userId);

            // Runs aus Events erstellen
            const runs = [];
            const template = data.template; // [1,3,4,5]
            let currentRun = [];
            let runDuration = 0;
            let maxRunDuration = 0;
            let minDuration = 999;
            data.events.forEach(ev => {
                if (ev.pos_id === template[0] && currentRun.length > 0) {
                    if (currentRun.length === template.length ) {
                        runs.push(currentRun);
                        if (runDuration > maxRunDuration) { maxRunDuration = runDuration;}
                        if (runDuration<minDuration) { minDuration = runDuration; }
                    }
                    currentRun = [];
                    runDuration = 0;
                }
                runDuration += ev.duration;
                if(ev.duration > 0) { currentRun.push(ev); }
            });
            runs.push(currentRun);

            $('#misc_container').html(`
            <div style="
                display: flex; 
                gap: 20px; 
                flex-wrap: wrap; 
                font-family: Arial, sans-serif;
            ">

                <div style="
                    background: #f0f4f8; 
                    border-radius: 12px; 
                    padding: 20px; 
                    flex: 1 1 200px; 
                    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
                    text-align: center;
                ">
                <div style="font-size: 14px; color: #555;">Schnellster</div>
                <div style="font-size: 24px; font-weight: bold; color: #43a047;">${minDuration.toFixed(1)}s</div>
                </div>

                <div style="
                    background: #f0f4f8; 
                    border-radius: 12px; 
                    padding: 20px; 
                    flex: 1 1 200px; 
                    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
                    text-align: center;
                ">
                <div style="font-size: 14px; color: #555;">Langsamster</div>
                <div style="font-size: 24px; font-weight: bold; color: #e53935;">${maxRunDuration.toFixed(1)}s</div>
                </div>

                <div style="
                    background: #f0f4f8; 
                    border-radius: 12px; 
                    padding: 20px; 
                    flex: 1 1 200px; 
                    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
                    text-align: center;
                ">
                <div style="font-size: 14px; color: #555;">Durchläufe</div>
                <div style="font-size: 24px; font-weight: bold; color: #1e88e5;">${runs.length}</div>
                </div>
            </div>
            `);

            // X-Achse = Durchläufe
            const labels = runs.map((_, i) => (i + 1));

            // Farben-Array für automatische Zuweisung
            const colorPalette = [
                'rgba(255, 99, 132, 0.7)',
                'rgba(75, 192, 192, 0.7)',
                'rgba(54, 162, 235, 0.7)',
                'rgba(255, 206, 86, 0.7)',
                'rgba(153, 102, 255, 0.7)',
                'rgba(255, 159, 64, 0.7)',
                'rgba(0, 128, 0, 0.7)',
                'rgba(128, 0, 128, 0.7)'
            ];

            // Mapping pos_id → Farbe
            const posColors = {};
            let colorIndex = 0;
            template.forEach(posId => {
                if (!posColors[posId]) {
                    posColors[posId] = colorPalette[colorIndex % colorPalette.length];
                    colorIndex++;
                }
            });

            // Datasets pro pos_id (gestapelt!)
            const datasets = template.map(posId => {
                return {
                    label: 'P' + posId,
                    stack: 'runs',
                    backgroundColor: posColors[posId],
                    data: runs.map((run, runIdx) => {
                        const runData = run;
                        const ev = runData.find(e => e.pos_id === posId);
                        return ev ? Number(ev.duration.toFixed(3)) : 0;
                    })
                };
            });

            // Chart aktualisieren
            chart.data.labels = labels;
            chart.data.datasets = datasets;
            chart.update();


            lastTimestamp = data.serverTimestamp;
            setTimeout(() => poll(), 1000);
        })
        .fail(function(jqXHR, textStatus, errorThrown) {
            console.error("Server nicht erreichbar. Fehler: ", textStatus, errorThrown);
        });
    } else {
        setTimeout(() => poll(), 1000);
    }
}

function assignSensor(mac, excId) {
    $.get('', { ajax: 'assign_sensor', mac: mac, excId: excId, userId: userId }, function(res) {
    });
}

function updateButtons() {

}
