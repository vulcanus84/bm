#include <WiFi.h>
#include <esp_now.h>
#include "esp_timer.h"
#include <ArduinoJson.h>  // Bibliothek für JSON
#include "../common/esp_now_structs.h"

// ==================== UART DEFINITIONS ====================
#define UART_RX 6
#define UART_TX 7

// ==================== SEQUENCE DEFINITIONS ====================
const uint8_t MAX_SEQ = 10;
enum RangeType { V, M, H, X }; // X = undefiniert
RangeType sequence[MAX_SEQ];
uint8_t sequenceIds[MAX_SEQ];
int SEQ_LENGTH = 3; // Beispielsequenz
int seqIndex = 0;
RangeType lastRange = X;

String espStatus = "idle"; // idle oder running
int userId = 0;        // wie vorher
int exerciseId = 0;    // neu, genau wie userId

// ==================== ESP-NOW DEFINITIONS ====================
uint8_t sensorMac[6] = {0x38,0x18,0x2B,0x69,0xE2,0xA8}; // Sensor MAC
int64_t lastEventTime = 0;

// ==================== FUNCTION DECLARATIONS ====================
RangeType getRange(int dist);
const char* rangeToChar(RangeType r);
void sendLedCmd(bool hitLedOn);
void onDataRecv(const uint8_t *mac, const uint8_t *incomingData, int len);

// ==================== SETUP ====================
void setup() {
  Serial.begin(115200);              // USB Debug
  Serial1.begin(9600, SERIAL_8N1, UART_RX, UART_TX);  // UART zum Gateway

  // ESP-NOW Setup
  WiFi.mode(WIFI_STA);
  if (esp_now_init() != ESP_OK) Serial.println("ESP-NOW Init fehlgeschlagen");

  // Peer Sensor registrieren
  esp_now_peer_info_t peer{};
  memcpy(peer.peer_addr, sensorMac, 6);
  peer.channel = 0;
  peer.encrypt = false;
  if (esp_now_add_peer(&peer) != ESP_OK) Serial.println("Peer hinzufügen fehlgeschlagen");

  esp_now_register_recv_cb(onDataRecv);
  Serial.println("Master bereit");
}

// ==================== LOOP ====================
void loop() {
  // UART Kommunikation mit Gateway
  if (Serial1.available()) {
    String msg = Serial1.readStringUntil('\n');
    Serial.print("Gateway: "); Serial.println(msg);
    readConfig(msg);
  }

  delay(10);
}

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
  if(espStatus=="running") {
    Serial.printf("Sensor Distanz: %d cm | Range: %s\n", distance, rangeToChar(currentRange));
  }

  // Sequenzprüfung
  if (SEQ_LENGTH > 0 &&
      currentRange != X &&
      currentRange == sequence[seqIndex] &&
      currentRange != lastRange &&
      espStatus == "running") {

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
      "&userId=" + String(userId) +
      "&exerciseId=" + String(exerciseId) +
      "&lastDistance=" + String(distance) +
    "\n";
    Serial1.print(uartMsg);

    // LED-Kommando zurück an Sensor
    sendToSensor(PKT_GAMEMSG_TO_SENSOR, true);
    
    lastEventTime = now;
    lastRange = currentRange;
    seqIndex++;
    if (seqIndex >= SEQ_LENGTH) {
      seqIndex = 0;
      Serial.println("=== SEQUENZ BEENDET ===");
    }

    Serial1.println("CONFIG:?status=" + espStatus +
            "&user_id=" + String(userId) +
            "&sequence=" + getSequenceAsString() +
            "&nextPos=" + sequenceIds[seqIndex]);
  }
}


// ==================== ESP-NOW CALLBACK ====================
void onDataRecv(const esp_now_recv_info_t *recv_info,
                const uint8_t *incomingData,
                int len)
{
  if (len < sizeof(PacketHeader)) return;

  const PacketHeader *hdr =
    (const PacketHeader *)incomingData;

  switch (hdr->type) {

    case PKT_HEARTBEAT:
      if (len == sizeof(HeartbeatPacket)) {
        const HeartbeatPacket *pkt =
          (const HeartbeatPacket *)incomingData;
        sendToSensor(PKT_HEARTBEAT, true);
      }
      break;

    case PKT_DISTANCE_TO_MASTER:
      if (len == sizeof(DistancePacket)) {
        const DistancePacket *pkt =
          (const DistancePacket *)incomingData;
        evaluateDistance(pkt->distance);
      }
      break;
  }
}

void sendToSensor(PacketType type, bool hitLedOn) {
  switch (type) {
    case PKT_HEARTBEAT: {
      HeartbeatPacket pkt;
      pkt.header.type = type;
      pkt.header.sensorId = 0; // Master ID
      pkt.ok = 1;
      esp_now_send(sensorMac, (uint8_t*)&pkt, sizeof(pkt));
      break;
    }
    case PKT_GAMEMSG_TO_SENSOR: {
      GameMsg msg;
      msg.header.type = PKT_GAMEMSG_TO_SENSOR;
      msg.header.sensorId = 0; // Master ID
      msg.state = (espStatus == "running") ? RUNNING : IDLE;
      msg.hit = hitLedOn;
      esp_now_send(sensorMac, (uint8_t*)&msg, sizeof(msg));
      break;
    }
  }
}

// ==================== CONFIG READING ====================
void readConfig(String payload) {
  StaticJsonDocument<256> doc;
  DeserializationError err = deserializeJson(doc, payload);
  if (!err) {
    espStatus = doc["status"].as<String>();
    if (espStatus == "idle") {
      seqIndex = 0;
      lastRange = X;

      String seqStr = doc["sequence"].as<String>();
      String seqIdStr = doc["sequenceIds"].as<String>();
      userId = doc["userId"].as<int>();
      exerciseId = doc["exerciseId"].as<int>();

      Serial.print("Status: "); Serial.println(espStatus);
      Serial.print("UserId: "); Serial.println(userId);
      Serial.print("ExerciseId: "); Serial.println(exerciseId);
      Serial.print("Sequenz: "); Serial.println(seqStr);

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

      // sequenceIds parsen
      start = 0;
      uint8_t idIndex = 0;
      while (start < seqIdStr.length() && idIndex < MAX_SEQ) {
        int idx = seqIdStr.indexOf(',', start);
        String token = (idx == -1) ? seqIdStr.substring(start) : seqIdStr.substring(start, idx);
        start = (idx == -1) ? seqIdStr.length() : idx + 1;

        token.trim();
        if (token.length() > 0) sequenceIds[idIndex++] = token.toInt();
      }
      
      Serial1.println("CONFIG:?status=" + espStatus +
              "&user_id=" + String(userId) +
              "&sequence=" + getSequenceAsString() +
              "&nextPos=" + sequenceIds[seqIndex]);

    } else {
      int64_t now = esp_timer_get_time(); // µs
      lastEventTime = now;
      
      Serial.println("Status not idle, no config set");
    }
    sendToSensor(PKT_GAMEMSG_TO_SENSOR, false);
  } else {
    Serial.print("JSON Fehler: "); Serial.println(err.c_str());
  }
}