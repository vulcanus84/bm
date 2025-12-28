#include <WiFi.h>
#include <esp_now.h>

typedef struct __attribute__((packed)) {
  uint8_t sensorId;
  int distance;
} dist_packet_t;

typedef struct __attribute__((packed)) {
  bool hitLedOn;
} led_cmd_t;

uint8_t slaveMac[6] = {0x38,0x18,0x2B,0x69,0xE2,0xA8}; // MAC des Slaves

volatile int lastDistance = 0;
volatile uint32_t packetCounter = 0;
uint32_t lastPrint = 0;

void onDataRecv(const esp_now_recv_info_t *info, const uint8_t *incomingData, int len) {
  if (len != sizeof(dist_packet_t)) return;
  dist_packet_t pkt;
  memcpy(&pkt, incomingData, sizeof(pkt));

  lastDistance = pkt.distance;
  packetCounter++;

  // Prüfen und hitLed steuern
  led_cmd_t cmd;
  cmd.hitLedOn = (pkt.distance < 200);
  esp_now_send(pkt.sensorId == 1 ? slaveMac : slaveMac, (uint8_t*)&cmd, sizeof(cmd));
}

void setup() {
  Serial.begin(115200);

  WiFi.mode(WIFI_STA);
  WiFi.disconnect();

  if (esp_now_init() != ESP_OK) Serial.println("❌ ESP-NOW Init fehlgeschlagen");

  esp_now_register_recv_cb(onDataRecv);

  esp_now_peer_info_t peer{};
  memcpy(peer.peer_addr, slaveMac, 6);
  peer.channel = 0;
  peer.encrypt = false;
  esp_now_add_peer(&peer);

  Serial.println("✅ ESP-NOW Master gestartet");
}

void loop() {
  if (millis() - lastPrint >= 1000) {
    lastPrint = millis();
    uint32_t hz = packetCounter;
    packetCounter = 0;
    Serial.print("📡 ESP-NOW Rate: "); Serial.print(hz); Serial.println(" Hz");
    Serial.print("🔹 Letzte Distanz: "); Serial.println(lastDistance);
  }
}
