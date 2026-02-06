#include "calc_position.h"
#include "gateway_connection.h"
#include "session_id.h"
#include "led_control.h"

const uint8_t MAX_SEQ = 10;
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

// ---- Hilfsfunktion: Sequenz als String ----
String getSequenceAsString() {
  String result = "";
  for (int i = 0; i < SEQ_LENGTH; i++) {
    result += rangeToChar(sequence[i]);
    if (i < SEQ_LENGTH - 1) result += ",";
  }
  return result;
}

void evaluateZone(String zone) {

  if(getGameStatus() != "running") return;
  
  // Sequenzprüfung
  if (SEQ_LENGTH > 0 && zone == rangeToChar(sequence[seqIndex])) {

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
      "&nextPos=" + sequenceIds[nextSeqIndex] +
      "&gameStatus=" + getGameStatus() +
    "\n";

    Serial.print(uartMsg);
    sendEventToGateway(uartMsg);

    // LED-Kommando zurück an Sensor
    if(runsCount>=maxRuns) { 
      stopExercise();
    } else {
      //OK blinken
      setLedState(hit, LED_ONEBLINK);
      lastEventTime = now;
      seqIndex = nextSeqIndex;
    }
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
  int64_t now = esp_timer_get_time(); // µs
  lastEventTime = now;
  seqIndex = 0;
  lastRange = X;
  runsCount = 0;
  sessionId = generateSessionId();
  setLedState(hit, LED_FIVEBLINKS);
  Serial.println("Übung gestartet");
}

void stopExercise() {
  seqIndex = 0;
  setLedState(hit, LED_FIVEBLINKS);
  Serial.println("Übung beenden");
}