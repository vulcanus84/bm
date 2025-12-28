#include <WiFi.h>
#include <esp_now.h>
#include "esp_timer.h"

#define RXD2 16
#define TXD2 17
#define okLed 25
#define hitLed 26

#define FILTER_SIZE 5

uint8_t masterMac[6] = {0x94,0xA9,0x90,0x6D,0xF6,0xB8};

int filterValues[FILTER_SIZE];
uint8_t filterIndex = 0;
bool filterFilled = false;
int lastSmoothed = -1;
int lastDistance = -1;
String espStatus = "idle";

enum LedState {
  LED_OFF,
  LED_ON,
  LED_BLINK,
  LED_BLINK_FAST,
  LED_ONEBLINK
};

struct LedControl {
  uint8_t pin;
  LedState state;
  unsigned long lastToggle;
  bool level;
  bool oneBlinkDone;
};

LedControl ok = { okLed, LED_OFF, 0, LOW, false };
LedControl hit = { hitLed, LED_OFF, 0, LOW, false };

enum GameState : uint8_t {
  IDLE = 0,
  RUNNING = 1
};

struct GameMsg {
  GameState state;
  bool hit;
};

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

// Frame Parser
const uint8_t FRAME_HEADER = 0xF4;
const uint8_t FRAME_END[4] = {0xF8, 0xF7, 0xF6, 0xF5};
const uint8_t DATA_TYPE_BASIC = 0x02;
const int BUFFER_SIZE = 128;
uint8_t buffer[BUFFER_SIZE];
bool inFrame = false;
uint16_t bufIndex = 0;

// Distanzpaket
typedef struct __attribute__((packed)) {
  uint8_t sensorId;
  int distance;
} dist_packet_t;

// LED-Befehl vom Master
typedef struct __attribute__((packed)) {
  bool hitLedOn;
} led_cmd_t;

void onLedCmdRecv(const esp_now_recv_info_t *info, const uint8_t *data, int len) {
  if (len != sizeof(GameMsg)) return;

  GameMsg msg;
  memcpy(&msg, data, sizeof(msg));

  // Status auswerten
  if (msg.state == RUNNING) {
    setLedState(ok,LED_BLINK_FAST);
    espStatus = "running";
  } else {
    setLedState(ok,LED_ON);
    setLedState(hit,LED_OFF);
    espStatus = "idle";
  }

  // Einmal-Event
  if (msg.hit) {
    setLedState(hit,LED_ONEBLINK);
  }
  Serial.println("LED Befehl erhalten");
}

// ESP-NOW Setup
void setupESPNow() {
  WiFi.mode(WIFI_STA);
  WiFi.disconnect();

  if (esp_now_init() != ESP_OK) Serial.println("ESP-NOW Init fehlgeschlagen");

  esp_now_peer_info_t peer{};
  memcpy(peer.peer_addr, masterMac, 6);
  peer.channel = 0;
  peer.encrypt = false;
  if (esp_now_add_peer(&peer) != ESP_OK) Serial.println("Peer hinzufügen fehlgeschlagen");

  esp_now_register_recv_cb(onLedCmdRecv);
  setLedState(ok, LED_ON);
}

// Sendet Distanz an Master
void sendDistance(int dist) {
  dist_packet_t pkt;
  pkt.sensorId = 1;
  pkt.distance = dist;
  esp_now_send(masterMac, (uint8_t*)&pkt, sizeof(pkt));
  //Serial.printf("Gesendet: %d cm\n", dist);
}

void setup() {
  Serial.begin(115200);
  Serial2.begin(9600, SERIAL_8N1, RXD2, TXD2);

  pinMode(okLed, OUTPUT);
  pinMode(hitLed, OUTPUT);

  setLedState(ok, LED_BLINK_FAST);
  setLedState(hit, LED_OFF);

  setupESPNow();
  delay(500);
  Serial.println("Sensor bereit");
}

void loop() {
  updateLed(ok);
  updateLed(hit);
  if(espStatus!="running") return;       

  while (Serial2.available()) {
    uint8_t b = Serial2.read();
    if (!inFrame) {
      if (b == FRAME_HEADER) { buffer[0]=b; bufIndex=1; inFrame=true; }
    } else {
      buffer[bufIndex++] = b;
      if (bufIndex>=BUFFER_SIZE) { bufIndex=0; inFrame=false; }
      if (bufIndex>=4 &&
          buffer[bufIndex-4]==FRAME_END[0] &&
          buffer[bufIndex-3]==FRAME_END[1] &&
          buffer[bufIndex-2]==FRAME_END[2] &&
          buffer[bufIndex-1]==FRAME_END[3]) {

        int dataLen = buffer[4] | (buffer[5]<<8);
        if (dataLen+10 <= bufIndex && buffer[6]==DATA_TYPE_BASIC) {
          uint8_t* data = &buffer[7];
          int movementDist = data[2] | (data[3]<<8);
          int stationaryDist = data[5] | (data[6]<<8);
          int closest = movementDist>0?movementDist:stationaryDist;

          lastDistance = smoothDistance(closest);
          sendDistance(lastDistance);
        }

        bufIndex=0;
        inFrame=false;
      }
    }
  }
}

void setLedState(LedControl &led, LedState newState) {
  led.state = newState;
  led.lastToggle = millis();
  led.oneBlinkDone = false;

  if (newState == LED_ON) {
    digitalWrite(led.pin, HIGH);
  } 
  else if (newState == LED_OFF) {
    digitalWrite(led.pin, LOW);
  }
}

void updateLed(LedControl &led) {
  unsigned long now = millis();
  unsigned long interval;

  switch (led.state) {

    case LED_OFF:
    case LED_ON:
      // nichts tun
      break;

    case LED_BLINK:
      interval = 500;
      if (now - led.lastToggle >= interval) {
        led.lastToggle = now;
        led.level = !led.level;
        digitalWrite(led.pin, led.level);
      }
      break;

    case LED_BLINK_FAST:
      interval = 150;
      if (now - led.lastToggle >= interval) {
        led.lastToggle = now;
        led.level = !led.level;
        digitalWrite(led.pin, led.level);
      }
      break;

    case LED_ONEBLINK:
      if (!led.oneBlinkDone) {
        digitalWrite(led.pin, HIGH);
        if (now - led.lastToggle >= 200) {
          digitalWrite(led.pin, LOW);
          led.oneBlinkDone = true;
          led.state = LED_OFF;
        }
      }
      break;
  }
}
