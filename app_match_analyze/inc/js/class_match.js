class BadmintonMatch {
    constructor(traineeName = "Trainee", opponentName = "Gegner") {
        this.traineeName = traineeName;
        this.opponentName = opponentName;

        this.events = [];
        this.score = { trainee: 0, opponent: 0 };

        this.cursor = null; 
    }

    addPoint({ id, winner, type, shot = null, detail = null, extra = null }) {
        if (!["trainee", "opponent"].includes(winner)) {
            throw new Error("winner muss 'trainee' oder 'opponent' sein.");
        }

        if (!["Fehler", "Glück", "Gewinnschlag", "Weiss nicht"].includes(type)) {
            throw new Error("Ungültiger Typ: 'Fehler', 'Glück', 'Gewinnschlag', 'Weiss nicht'.");
        }

        this.score[winner]++;

        this.events.push({
            timestamp: new Date(),
            index: this.events.length + 1,
            id,
            winner,
            type,
            shot,
            detail,
            extra
        });
    }

    scrollToPercent(percent) {
        if (percent < 0 || percent > 100) {
            throw new Error("Wert muss zwischen 0 und 100 liegen.");
        }

        if(percent === 100) {
            this.cursor = null;
            return;
        }

        const index = Math.round((percent / 100) * this.events.length);
        this.cursor = index;
    }

    _activeEvents() {
        if (this.cursor === null) return this.events;
        return this.events.slice(0, this.cursor);
    }

    getPointStatistics(player = "trainee") {
        const ev = this._activeEvents();

        // Zähler initialisieren
        let fehler = 0, gewinn = 0, glueck = 0;

        ev.forEach(e => {
            if (player === "trainee") {
                if (e.winner === player) {
                    if (e.type === "Fehler") fehler++;
                    else if (e.type === "Gewinnschlag") gewinn++;
                    else if (e.type === "Glück") glueck++;
                }
            } else {
                // Gegner-Statistik
                if (e.winner === player) {
                    if (e.type === "Fehler") fehler++;
                    else if (e.type === "Gewinnschlag") gewinn++;
                    else if (e.type === "Glück") glueck++;
                }
            }
        });

        // Array zurückgeben für Chart.js
        return [fehler, gewinn, glueck];
    }

    getWinnersByShot(player = "trainee") {
        const ev = this._activeEvents();

        // Definierte Schlagarten in der Reihenfolge, wie sie im Chart erscheinen sollen
        const strokeTypes = ["Angriffsclear", "Drop", "Smash", "Defense", "Netzdrop", "Täuschung", "Kill", "Drive"];

        // Zähler initialisieren
        const counts = {};
        strokeTypes.forEach(s => counts[s] = 0);

        ev.forEach(e => {
            if (e.winner === player && e.type === "Gewinnschlag") {
                if (counts.hasOwnProperty(e.shot)) counts[e.shot]++;
            }
        });

        // Array in der definierten Reihenfolge zurückgeben
        return strokeTypes.map(s => counts[s]);
    }

    getScore() {
      const ev = this._activeEvents();

      let traineePoints = 0;
      let opponentPoints = 0;

      ev.forEach(e => {
          if (e.winner === "trainee") traineePoints++;
          else opponentPoints++;
      });

      return {
          trainee: traineePoints,
          opponent: opponentPoints,
          text: `${traineePoints} : ${opponentPoints}`
      };
   }

getErrorChartData(player = "trainee", strokes = [], allowedCombinations = []) {
    const ev = this._activeEvents();

    // Default-Kategorie suchen (z. B. detail: "Unbekannt")
    const defaultCombo = allowedCombinations.find(c => c.detail === "---");
    const defaultKey = defaultCombo
        ? (defaultCombo.extra ? `${defaultCombo.detail}_${defaultCombo.extra}` : defaultCombo.detail)
        : null;

    // Map initialisieren
    const dataMap = {};
    allowedCombinations.forEach(c => {
        const key = c.extra ? `${c.detail}_${c.extra}` : c.detail;
        dataMap[key] = {};
        strokes.forEach(s => dataMap[key][s] = 0);
    });

    // Events durchgehen
    ev.forEach(e => {
        if (e.type !== "Fehler") return;
        if (e.winner == player) return;

        const shot = e.shot || "";
        const detail = e.detail || "";
        const extra = e.extra || "";

        // Key bilden
        let key = extra ? `${detail}_${extra}` : detail;

        // Wenn Kombination nicht erlaubt → Default verwenden
        if (!dataMap[key]) {
            key = defaultKey;
        }

        if (key && strokes.includes(shot)) {
            dataMap[key][shot]++;
        }
    });

    // Chart.js-Datasets
    const datasets = allowedCombinations.map((c, i) => {
        const key = c.extra ? `${c.detail}_${c.extra}` : c.detail;
        return {
            label: key,
            data: strokes.map(s => dataMap[key][s]),
            backgroundColor: colors[i % colors.length]
        };
    });

    return {
        labels: strokes,
        datasets
    };
}


getWinnerChartData(player = "trainee", strokes = [], allowedCombinations = []) {
    const ev = this._activeEvents();

    // Default-Detail automatisch erkennen:
    // Wir suchen nach detail=="Unbekannt" und extra=="" (oder was auch immer du willst)
    const defaultCombo = allowedCombinations.find(c => c.detail === "---");
    const defaultKey = defaultCombo
        ? (defaultCombo.extra ? `${defaultCombo.detail}_${defaultCombo.extra}` : defaultCombo.detail)
        : null;

    // Map initialisieren
    const dataMap = {};
    allowedCombinations.forEach(c => {
        const key = c.extra ? `${c.detail}_${c.extra}` : c.detail;
        dataMap[key] = {};
        strokes.forEach(s => dataMap[key][s] = 0);
    });

    // Events verarbeiten
    ev.forEach(e => {
        if (e.type !== "Gewinnschlag") return;
        if (e.winner !== player) return;

        const shot = e.shot || "";
        const detail = e.detail || "";
        const extra = e.extra || "";

        // Key bilden
        let key = extra ? `${detail}_${extra}` : detail;

        // Wenn nicht erlaubt → Default verwenden
        if (!dataMap[key]) {
            key = defaultKey; 
        }

        if (key && strokes.includes(shot)) {
            dataMap[key][shot]++;
        }
    });

    // Chart.js Dataset bauen
    const datasets = allowedCombinations.map((c, i) => {
        const key = c.extra ? `${c.detail}_${c.extra}` : c.detail;
        return {
            label: key,
            data: strokes.map(s => dataMap[key][s]),
            backgroundColor: colors[i % colors.length]
        };
    });

    return {
        labels: strokes,
        datasets: datasets
    };
}


getPointProgress() {
    const ev = this._activeEvents();

    let traineeScore = 0;
    let opponentScore = 0;

    const traineeProgress = [];
    const opponentProgress = [];

    ev.forEach(e => {
        if (e.winner === "trainee") {
            traineeScore++;
        } else {
            opponentScore++;
        }

        traineeProgress.push(traineeScore);
        opponentProgress.push(opponentScore);
    });

    return {
        trainee: traineeProgress,
        opponent: opponentProgress
    };
}

removePointById(id) {
    // Event finden
    id = parseInt(id, 10);
    const index = this.events.findIndex(e => e.id === id);
    if (index === -1) return false; // nichts zu entfernen

    const event = this.events[index];

    // Score korrigieren
    if (event.winner === "trainee") {
        this.score.trainee = Math.max(0, this.score.trainee - 1);
    } else {
        this.score.opponent = Math.max(0, this.score.opponent - 1);
    }

    // Event entfernen
    this.events.splice(index, 1);

    // index-Nummern neu aufbauen
    this.events.forEach((e, i) => {
        e.index = i + 1;
    });

    // Cursor anpassen (falls du scrollTo nutzt)
    if (this.cursor > this.events.length) {
        this.cursor = this.events.length;
    }

    return true;
}

replacePointId(oldId, newId) {
    newId = parseInt(newId, 10);

    if (isNaN(newId)) return false;

    const existsNew = this.events.some(e => e.id === newId);
    if (existsNew) {
        throw new Error(`Neue ID '${newId}' existiert bereits.`);
    }

    const event = this.events.find(e => e.id === oldId);
    if (!event) return false;

    event.id = newId;
    return true;
}



}
