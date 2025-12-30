#include "calc_position.h"
#include "esp_now_handler.h"
#include "../common/esp_now_structs.h"
#include "get_config.h"

const uint8_t MAX_SEQ = 10;
enum RangeType { V, M, H, X }; // X = undefiniert
RangeType sequence[MAX_SEQ];
uint8_t sequenceIds[MAX_SEQ];
int SEQ_LENGTH = 0;
int seqIndex = 0;
RangeType lastRange = X;

int64_t lastEventTime = 0;

// ==================== RANGE LOGIC ====================
RangeType getRange(int dist) {
  if (dist > 0 && dist < 150) return V;
  if (dist >= 300 && dist <= 350) return M;
  if (dist > 500) return H;
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
  Serial.printf("Sensor Distanz: %d cm | Range: %s\n", distance, rangeToChar(currentRange));

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

    // Event an Gateway senden
    String uartMsg = String("EVENT:?pos=") + sequenceIds[seqIndex] + 
      "&duration=" + String(delta_s) +
      "&userId=" + String(getUserId()) +
      "&exerciseId=" + String(getExerciseId()) +
      "&distance=" + String(distance) +
    "\n";
    Serial.print(uartMsg);
    Serial1.print(uartMsg);

    // LED-Kommando zurück an Sensor
    sendToSensor(PKT_GAMEMSG_TO_SENSOR, 1);
    
    lastEventTime = now;
    lastRange = currentRange;
    seqIndex++;
    if (seqIndex >= SEQ_LENGTH) {
      seqIndex = 0;
      Serial.println("=== SEQUENZ BEENDET ===");
    }
    String configString = "CONFIG:?user_id=" + String(getUserId()) +
            "&sequence=" + getSequenceAsString() +
            "&distance=" + distance +
            "&nextPos=" + sequenceIds[seqIndex];
    Serial.println(configString);
    Serial1.println(configString);
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

String getNextSequenceId() {
   return String(sequenceIds[seqIndex]);
}

void startExercise() {
  int64_t now = esp_timer_get_time(); // µs
  lastEventTime = now;
  seqIndex = 0;
  lastRange = X;
  Serial.println("Übung gestartet");
  sendToSensor(PKT_GAMEMSG_TO_SENSOR, 0);
}

void stopExercise() {
  seqIndex = 0;
  sendToSensor(PKT_GAMEMSG_TO_SENSOR, 0);
  Serial.println("Übung beenden");
}