#include <WiFi.h>
#include <esp_now.h>
#include "esp_timer.h"

// ===================== HARDWARE =====================
#define RXD2 16
#define TXD2 17
#define okLed 25
#define hitLed 26

#define MAX_SEQ 10
#define FILTER_SIZE 5

// ===================== MASTER MAC =====================
uint8_t masterMac[6] = {0x94,0xA9,0x90,0x6D,0xF6,0xB8}; // MAC des Master-Gateways

// ===================== SEQUENZ =====================
enum RangeType { V, M, H, X };
RangeType sequence[MAX_SEQ];
uint8_t sequenceIds[MAX_SEQ];
uint8_t SEQ_LENGTH = 0;
uint8_t seqIndex = 0;
RangeType lastRange = X;

// ===================== GLÄTTUNG =====================
int filterValues[FILTER_SIZE];
uint8_t filterIndex = 0;
bool filterFilled = false;
int lastSmoothed = -1;
int lastDistance = -1;

// ===================== TIMING =====================
int64_t lastEventTime = 0;

// ===================== STATUS =====================
String espStatus = "idle";

// ===================== FRAME =====================
const uint8_t FRAME_HEADER_1 = 0xF4;
const uint8_t FRAME_END_1 = 0xF8;
const uint8_t FRAME_END_2 = 0xF7;
const uint8_t FRAME_END_3 = 0xF6;
const uint8_t FRAME_END_4 = 0xF5;
const uint8_t DATA_TYPE_BASIC = 0x02;

const int BUFFER_SIZE = 128;
uint8_t buffer[BUFFER_SIZE];
bool inFrame = false;
uint16_t bufIndex = 0;

// ===================== FUNKTIONEN =====================

// Glättung
int smoothDistance(int newValue) {
  if (newValue <= 0 || newValue > 3000) return lastSmoothed;

  filterValues[filterIndex] = newValue;
  filterIndex = (filterIndex + 1) % FILTER_SIZE;
  if (filterIndex == 0) filterFilled = true;

  int count = filterFilled ? FILTER_SIZE : filterIndex;
  long sum = 0;
  for (int i = 0; i < count; i++) sum += filterValues[i];

  lastSmoothed = sum / count;
  return lastSmoothed;
}

// Range
RangeType getRange(int dist) {
  if (dist > 0 && dist < 150) return V;
  if (dist >= 300 && dist <= 350) return M;
  if (dist > 550) return H;
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

// Event senden
typedef struct {
  uint8_t sensorId;
  uint8_t seqPos;
  char range;
  float duration;
  int lastDistance;
} event_msg_t;

void sendEventToMaster(float durationSec, RangeType r) {
  if (espStatus != "running") return;
  event_msg_t ev;
  ev.sensorId = 0; // optional, Sensor-ID setzen
  ev.seqPos = sequenceIds[seqIndex];
  ev.range = rangeToChar(r)[0];
  ev.duration = durationSec;
  ev.lastDistance = lastDistance;

  esp_now_send(masterMac, (uint8_t*)&ev, sizeof(ev));
}

// Frame Parsing
void parseFrame(uint8_t* frame, int length) {
  if (length < 12) return;
  int dataLen = frame[4] | (frame[5]<<8);
  if (dataLen+10>length) return;
  if(frame[6]!=DATA_TYPE_BASIC) return;

  uint8_t* data = &frame[7];
  int movementDist = data[2] | (data[3]<<8);
  int stationaryDist = data[5] | (data[6]<<8);
  int closest = movementDist>0?movementDist:stationaryDist;
  int smoothed = smoothDistance(closest);
  lastDistance = smoothed;

  RangeType currentRange = getRange(smoothed);

  if(SEQ_LENGTH>0 && currentRange!=X && currentRange==sequence[seqIndex] && currentRange!=lastRange && espStatus=="running") {
    int64_t now = esp_timer_get_time();
    int64_t delta_us = lastEventTime==0?0:(now-lastEventTime);

    sendEventToMaster(delta_us/1e6, currentRange);

    lastRange = currentRange;
    lastEventTime = now;

    seqIndex++;
    if(seqIndex>=SEQ_LENGTH) {
      seqIndex=0;
    }
  }
}

// ===================== ESP-NOW =====================
typedef struct {
  uint8_t sensorId;
  char status;
  uint8_t seqLength;
  char sequence[MAX_SEQ];
  uint8_t sequenceIds[MAX_SEQ];
  int userId;
  int exerciseId;
} config_msg_t;

void onConfigRecv(const esp_now_recv_info_t *info, const uint8_t *data, int len) {
  if(len != sizeof(config_msg_t)) return;
  config_msg_t cfg;
  memcpy(&cfg, data, sizeof(cfg));

  espStatus = (cfg.status=='r')?"running":"idle";
  SEQ_LENGTH = cfg.seqLength;
  memcpy(sequence, cfg.sequence, MAX_SEQ);
  memcpy(sequenceIds, cfg.sequenceIds, MAX_SEQ);
}

// ===================== SETUP =====================
void setup() {
  Serial.begin(115200);
  Serial1.begin(9600, SERIAL_8N1, RXD2, TXD2);

  pinMode(okLed, OUTPUT);
  pinMode(hitLed, OUTPUT);

  WiFi.mode(WIFI_STA);
  esp_now_init();
  esp_now_register_recv_cb(onConfigRecv);

  esp_now_peer_info_t peer{};
  memcpy(peer.peer_addr, masterMac, 6);
  peer.channel = 0;
  peer.encrypt = false;
  esp_now_add_peer(&peer);
}

// ===================== LOOP =====================
void loop() {
  while(Serial1.available()) {
    uint8_t b = Serial1.read();

    if(!inFrame) {
      if(b==FRAME_HEADER_1) { buffer[0]=b; bufIndex=1; inFrame=true; }
    } else {
      buffer[bufIndex++] = b;
      if(bufIndex>=BUFFER_SIZE) { bufIndex=0; inFrame=false; }

      if(bufIndex>=4 &&
         buffer[bufIndex-4]==FRAME_END_1 &&
         buffer[bufIndex-3]==FRAME_END_2 &&
         buffer[bufIndex-2]==FRAME_END_3 &&
         buffer[bufIndex-1]==FRAME_END_4) {
           parseFrame(buffer, bufIndex);
           bufIndex=0; inFrame=false;
      }
    }
  }
}
