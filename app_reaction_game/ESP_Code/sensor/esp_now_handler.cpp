#include <WiFi.h>
#include "esp_timer.h"
#include "../common/esp_now_structs.h"
#include "led_control.h"
#include "esp_now_handler.h"

uint8_t masterMac[6] = {0x94,0xA9,0x90,0x6D,0xF6,0xB8};
unsigned long lastHeartbeatReceived = 0;
unsigned long lastHeartbeatSend = 0;
unsigned long lastHeartbeatCheckFailed = 0;

String espStatus = "idle";

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

  esp_now_register_recv_cb(onDataRecv);
  setLedState(ok, LED_ON);
}


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
        lastHeartbeatReceived = millis();
        if(espStatus != "running") setLedState(ok, LED_ON);
      }
      break;

    case PKT_GAMEMSG_TO_SENSOR:
      if (len == sizeof(GameMsg)) {
        const GameMsg *pkt =
          (const GameMsg *)incomingData;
          // Status auswerten
          if (pkt->state == RUNNING) {
            setLedState(ok,LED_BLINK_FAST);
            espStatus = "running";
          } else {
            setLedState(ok,LED_ON);
            setLedState(hit,LED_OFF);
            espStatus = "idle";
          }

          // Einmal-Event
          if (pkt->hit == 1) {
            setLedState(hit,LED_ONEBLINK);
          }
      }
      break;
  }
}

// checkHeartbeat
void checkHeartbeat() {
  if (millis() - lastHeartbeatReceived > 3000) {
    if(millis() - lastHeartbeatCheckFailed > 2000) {
      lastHeartbeatCheckFailed = millis();
      setLedState(ok, LED_BLINK);
      return;
    }
  } 
  
  if (millis() - lastHeartbeatSend > 2000) {
    lastHeartbeatSend = millis();
    HeartbeatPacket pkt;
    pkt.header.type = PKT_HEARTBEAT;
    pkt.header.sensorId = 1;
    pkt.ok = 1;
    esp_now_send(masterMac, (uint8_t*)&pkt, sizeof(pkt));
  }
}

// Sendet Distanz an Master
void sendDistance(int dist) {
  DistancePacket pkt;
  pkt.header.type = PKT_DISTANCE_TO_MASTER;
  pkt.header.sensorId = 1;
  pkt.distance = dist;
  esp_now_send(masterMac, (uint8_t*)&pkt, sizeof(pkt));
}
