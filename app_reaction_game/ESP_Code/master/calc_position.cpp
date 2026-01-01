#include "calc_position.h"
#include "esp_now_handler.h"
#include "../common/esp_now_structs.h"
#include "gateway_connection.h"
#include "session_id.h"

const uint8_t MAX_SEQ = 10;
enum RangeType { V, M, H, X }; // X = undefiniert
RangeType sequence[MAX_SEQ];
uint8_t sequenceIds[MAX_SEQ];
int SEQ_LENGTH = 0;
int seqIndex = 0;
RangeType lastRange = X;
String sessionId;
int runsCount = 0;
int maxRuns = 0;

int64_t lastEventTime = 0;

// ==================== RANGE LOGIC ====================
RangeType getRange(int dist) {
  if (dist > 0 && dist < 180) return V;
  if (dist >= 320 && dist <= 380) return M;
  if (dist > 530) return H;
  return X;
}

const char* rangeToChar(RangeType r) {
  switch(r) {
    case V: return "V";
    case M: return "M";
    case H: return "H";
    default: return "X";
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

void evaluateDistance(int distance) {
  RangeType currentRange = getRange(distance);

  // Sequenzprüfung
  if (SEQ_LENGTH > 0 &&
      currentRange != X &&
      currentRange == sequence[seqIndex] &&
      currentRange != lastRange) {

    Serial.println("=== EVENT erkannt ===");

    int64_t now = esp_timer_get_time(); // µs
    int64_t delta_us = (lastEventTime == 0) ? 0 : (now - lastEventTime);

    float delta_s = delta_us/1e6;

    Serial.print("Dauer seit letztem: ");
    Serial.print(delta_s);
    Serial.println(" s");
    
    int nextSeqIndex;

    if (seqIndex >= SEQ_LENGTH-1) {
      nextSeqIndex = 0;
      runsCount++;
      if(runsCount>=maxRuns) { 
        setGameStatus("idle");
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
    String uartMsg = String("EVENT:?pos=") + sequenceIds[seqIndex] + 
      "&duration=" + String(delta_s) +
      "&userId=" + String(getUserId()) +
      "&exerciseId=" + String(getExerciseId()) +
      "&sessionId=" + sessionId +
      "&distance=" + String(distance) +
      "&nextPos=" + sequenceIds[nextSeqIndex] +
      "&gameStatus=" + getGameStatus() +
    "\n";
    Serial.print(uartMsg);
    Serial1.print(uartMsg);

    // LED-Kommando zurück an Sensor
    if(runsCount>=maxRuns) { 
      sendToSensor(PKT_GAMEMSG_TO_SENSOR, 2); // Signal für Abschluss
    } else {
      sendToSensor(PKT_GAMEMSG_TO_SENSOR, 1); // Signal für Position erreicht
    }
    
    lastEventTime = now;
    lastRange = currentRange;
    seqIndex = nextSeqIndex;
  }
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
    if (token == "V") sequence[SEQ_LENGTH++] = V;
    else if (token == "M") sequence[SEQ_LENGTH++] = M;
    else if (token == "H") sequence[SEQ_LENGTH++] = H;
  }
}

void setMaxRuns(int repetitions) {
  maxRuns = repetitions;
}

String getNextSequenceId() {
   return String(sequenceIds[seqIndex]);
}

void startExercise() {
  int64_t now = esp_timer_get_time(); // µs
  lastEventTime = now;
  seqIndex = 0;
  lastRange = X;
  runsCount = 0;
  sessionId = generateSessionId();
  Serial.println("Übung gestartet");
  sendToSensor(PKT_GAMEMSG_TO_SENSOR, 0);
}

void stopExercise() {
  seqIndex = 0;
  sendToSensor(PKT_GAMEMSG_TO_SENSOR, 0);
  Serial.println("Übung beenden");
}