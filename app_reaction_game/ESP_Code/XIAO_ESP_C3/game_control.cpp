#include "game_control.h"
#include <ArduinoJson.h>  // Bibliothek für JSON
#include "wifi_connection.h"
#include "session_id.h"
#include "led_control.h"

GameStatus gameStatus = IDLE;
int userId = 0;
int exerciseId = 0;
int repetitions = 0;
String configParams = "?dummy=1"; // Initialer Wert, damit die URL nicht leer ist
unsigned long lastConfigSend = 0;
unsigned long lastPatternChange = 0;
int lastPatternIndex = 0;
String lastZoneGC = "";

LedCommand gc_ledCmd;
WifiCommand gc_wifiCmd;
GcCommand gc_gcCmd;

const uint8_t MAX_SEQ = 15;
enum RangeType { VL, VM, VR, ML, MM, MR, HL, HM, HR, X }; // X = undefiniert
RangeType sequence[MAX_SEQ];
uint8_t sequenceIds[MAX_SEQ];
int SEQ_LENGTH = 0;
int seqIndex = 0;
RangeType lastRange = X;
String sessionId;
int runsCount = 0;
int maxRuns = 0;

int64_t lastEventTime = 0;
QueueHandle_t gcQueue;

const LedCommandType rangeToLedCommand(RangeType r) {
  switch(r) {
    case VL: return SHOW_VL;
    case VM: return SHOW_VM;
    case VR: return SHOW_VR;
    case ML: return SHOW_ML;
    case MM: return SHOW_MM;
    case MR: return SHOW_MR;
    case HL: return SHOW_HL;
    case HM: return SHOW_HM;
    case HR: return SHOW_HR;
    default: return CLEAR; // oder eine andere Standardaktion
  }
}

GameStatus getGameStatus() {
  return gameStatus;
}

const char* rangeToChar(RangeType r) {
  switch(r) {
    case VL: return "VL";
    case VM: return "VM";
    case VR: return "VR";
    case ML: return "ML";
    case MM: return "MM";
    case MR: return "MR";
    case HL: return "HL";
    case HM: return "HM";
    case HR: return "HR";
    default: return "X";
  }
}

String getGameStatusForConfig() {
  switch(gameStatus) {
    case IDLE: return "idle";
    case WAIT_START_POSITION: return "running";
    case WAIT_COUNTDOWN: return "running";
    case RUNNING: return "running";
    case STOPPING: return "idle";
    default: return "unknown";
  }
}


// ---- Hilfsfunktion: Sequenz als String ----
String getSequenceAsString() {
  String result = "";
  for (int i = 0; i < SEQ_LENGTH; i++) {
    result += rangeToChar(sequence[i]);
    if (i < SEQ_LENGTH - 1) result += ",";
  }
  return result;
}

void setSequenceIDs(String seqIdStr) {
  // sequenceIds parsen
  int start = 0;
  uint8_t idIndex = 0;
  while (start < seqIdStr.length() && idIndex < MAX_SEQ) {
    int idx = seqIdStr.indexOf(',', start);
    String token = (idx == -1) ? seqIdStr.substring(start) : seqIdStr.substring(start, idx);
    start = (idx == -1) ? seqIdStr.length() : idx + 1;

    token.trim();
    if (token.length() > 0) sequenceIds[idIndex++] = token.toInt();
  }
}

void setSequenceStrings(String seqStr) {
  // Sequenz-Buchstaben parsen
  SEQ_LENGTH = 0;
  int start = 0;
  while (start < seqStr.length() && SEQ_LENGTH < MAX_SEQ) {
    int idx = seqStr.indexOf(',', start);
    String token = (idx == -1) ? seqStr.substring(start) : seqStr.substring(start, idx);
    start = (idx == -1) ? seqStr.length() : idx + 1;
    token.trim();
    if (token == "VL") sequence[SEQ_LENGTH++] = VL;
    else if (token == "VM") sequence[SEQ_LENGTH++] = VM;
    else if (token == "VR") sequence[SEQ_LENGTH++] = VR;
    else if (token == "ML") sequence[SEQ_LENGTH++] = ML;
    else if (token == "MM") sequence[SEQ_LENGTH++] = MM;
    else if (token == "MR") sequence[SEQ_LENGTH++] = MR;
    else if (token == "HL") sequence[SEQ_LENGTH++] = HL;
    else if (token == "HM") sequence[SEQ_LENGTH++] = HM;
    else if (token == "HR") sequence[SEQ_LENGTH++] = HR;
    else sequence[SEQ_LENGTH++] = X;
  }
}

void setMaxRuns(int repetitions) {
  maxRuns = repetitions;
}

String getNextSequenceId() {
   return String(sequenceIds[seqIndex]);
}

void startExercise() {
  seqIndex = 0;
  lastRange = X;
  runsCount = 0;
  sessionId = generateSessionId();
  Serial.println("Übung gestartet");

  if(lastZoneGC == rangeToChar(sequence[SEQ_LENGTH-1])) {
    // Wenn der Spieler bereits in der richtigen Zone steht, sofort starten
    gc_ledCmd.type = SHOW_COUNTDOWN;
    xQueueSend(ledQueue, &gc_ledCmd, 0);
    gameStatus = WAIT_COUNTDOWN;
  } else {
    // Ansonsten Startposition anzeigen
    Serial.println("Bitte in Startposition begeben: " + String(rangeToChar(sequence[SEQ_LENGTH-1])));
    gc_ledCmd.type = rangeToLedCommand(sequence[SEQ_LENGTH-1]);
    gc_ledCmd.fillColor = CRGB::Red;
    xQueueSend(ledQueue, &gc_ledCmd, 0);
    gameStatus = WAIT_START_POSITION;
  }
}

// Wird von LED-Task aufgerufen, um den Timer zu starten, wenn der Countdown vorbei ist und die erste Position angezeigt wird
void startTime() {
  if(gameStatus != WAIT_COUNTDOWN) return; // Nur starten, wenn wir tatsächlich im Countdown waren
  lastEventTime = esp_timer_get_time(); // µs
  gc_ledCmd.type = rangeToLedCommand(sequence[seqIndex]);
  gc_ledCmd.fillColor = CRGB::Red;
  xQueueSend(ledQueue, &gc_ledCmd, 0);
  gameStatus = RUNNING;
}

void stopExercise() {
  if(runsCount>=maxRuns) { 
    gc_ledCmd.type = FINISHED;
    xQueueSend(ledQueue, &gc_ledCmd, 0);
  } 
  seqIndex = 0;
  Serial.println("Übung beenden");
  gameStatus = STOPPING;
}


void evaluateZone(String zone) {
  
  lastZoneGC = zone;

  // Spieler hat sich von Startposition wegbewegt, bevor die Übung richtig gestartet ist
  if(gameStatus == WAIT_COUNTDOWN) {
    gc_ledCmd.type = ABORTED;
    xQueueSend(ledQueue, &gc_ledCmd, 0);
    gameStatus = WAIT_START_POSITION;

    gc_ledCmd.type = rangeToLedCommand(sequence[SEQ_LENGTH-1]);
    gc_ledCmd.fillColor = CRGB::Red;
    xQueueSend(ledQueue, &gc_ledCmd, 0);
    return;
  }

  // Prüfen, ob Spieler in der Startposition steht, um den Countdown zu starten
  if(gameStatus == WAIT_START_POSITION) {
    if(zone == rangeToChar(sequence[SEQ_LENGTH-1])) {
      gc_ledCmd.type = SHOW_COUNTDOWN;
      xQueueSend(ledQueue, &gc_ledCmd, 0);
      gameStatus = WAIT_COUNTDOWN;
    }
    return;
  }
    
  // Sequenzprüfung
  if (SEQ_LENGTH > 0 && zone == rangeToChar(sequence[seqIndex]) && gameStatus == RUNNING) {

    gc_ledCmd.type = rangeToLedCommand(sequence[seqIndex]);
    gc_ledCmd.fillColor = CRGB::Green;
    xQueueSend(ledQueue, &gc_ledCmd, 0);

    int64_t now = esp_timer_get_time(); // µs
    int64_t delta_us = (lastEventTime == 0) ? 0 : (now - lastEventTime);

    float delta_s = delta_us/1e6;

    int nextSeqIndex;

    if (seqIndex >= SEQ_LENGTH-1) {
      nextSeqIndex = 0;
      runsCount++;
      if(runsCount>=maxRuns) { 
        gameStatus = STOPPING;
      }
      Serial.print("Sequenz ");
      Serial.print(runsCount);
      Serial.print(" von ");
      Serial.print(maxRuns);
      Serial.println(" abgeschlossen");
    } else {
      nextSeqIndex = seqIndex + 1;
    }
    
    // Event an Gateway senden
    String uartMsg = String("?pos=") + sequenceIds[seqIndex] + 
      "&duration=" + String(delta_s) +
      "&userId=" + String(userId) +
      "&exerciseId=" + String(exerciseId) +
      "&sessionId=" + sessionId +
      "&nextPos=" + sequenceIds[nextSeqIndex] +
      "&gameStatus=" + getGameStatusForConfig();

    // WLAN-Kommando an den WLAN-Task senden
    gc_wifiCmd.type = WIFI_SEND_EVENT;
    gc_wifiCmd.payload = uartMsg;
    xQueueSend(wifiQueue, &gc_wifiCmd, 0);

    // LED-Kommando zurück an Sensor
    if(runsCount>=maxRuns) { 
      stopExercise();
    } else {
      lastEventTime = now;
      seqIndex = nextSeqIndex;
      gc_ledCmd.type = rangeToLedCommand(sequence[seqIndex]);
      gc_ledCmd.fillColor = CRGB::Red;
      xQueueSend(ledQueue, &gc_ledCmd, 0);
    }
  }
}

void readConfig(String payload) {
  StaticJsonDocument<256> doc;
  DeserializationError err = deserializeJson(doc, payload);
  if (!err) {
    if(gameStatus == IDLE) {
      if(doc["status"] == "running") {
        gameStatus = RUNNING;
        startExercise();
      }
    } 
    if(gameStatus == RUNNING) {
      if(doc["status"] == "idle") {
        gameStatus = IDLE;
        stopExercise();
      }
    }
    if(gameStatus == STOPPING) gameStatus = IDLE;

    String seqStr = doc["sequence"].as<String>();
    String seqIdStr = doc["sequenceIds"].as<String>();
    userId = doc["userId"].as<int>();
    exerciseId = doc["exerciseId"].as<int>();
    repetitions = doc["repetitions"].as<int>();

    setSequenceIDs(seqIdStr);
    setSequenceStrings(seqStr);
    setMaxRuns(repetitions);

    configParams = "?user_id=" + String(userId) +
            "&sequence=" + getSequenceAsString() +
            "&nextPos=" + getNextSequenceId();

  } else {
    Serial.print("JSON Fehler: "); Serial.println(err.c_str());
  }
}

void taskGameControl(void *pvParameters) {
  for (;;) {
    
    if (millis() - lastConfigSend > 2000) {
      gc_wifiCmd.type = WIFI_GET_CONFIG;
      gc_wifiCmd.payload = configParams;
      xQueueSend(wifiQueue, &gc_wifiCmd, 0);
      lastConfigSend = millis();
    }

    if (xQueueReceive(gcQueue, &gc_gcCmd, 0)) {
      GcCommand gc_config_read = gc_gcCmd; // Lokale Kopie, um mögliche Datenänderungen zu vermeiden
      switch (gc_config_read.type) {
        case GC_READ_CONFIG:
           readConfig(gc_config_read.payload);
           break;
      }
    }
    
    if (millis() - lastPatternChange > 1000) {
      // Hier könnte eine Funktion aufgerufen werden, um das LED-Muster zu ändern
      lastPatternChange = millis();
      if(gameStatus == IDLE && wifiState == SERVER_OK && SEQ_LENGTH > 0) {
        RangeType currentRange = sequence[lastPatternIndex];
        lastPatternIndex = (lastPatternIndex + 1) % SEQ_LENGTH; // Nächster Index in der Sequenz
        gc_ledCmd.type = rangeToLedCommand(currentRange);
        gc_ledCmd.fillColor = CRGB::Red;
        xQueueSend(ledQueue, &gc_ledCmd, 0);
      }
    }

    vTaskDelay(pdMS_TO_TICKS(10)); 
  }
}
