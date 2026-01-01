#include <WiFi.h>
#include "esp_timer.h"
#include "esp_now_handler.h"
#include "calc_position.h"
#include "gateway_connection.h"

uint8_t sensorMac[6] = {0x38,0x18,0x2B,0x69,0xE2,0xA8}; // Sensor MAC
int lastDistance = 0;

int getLastDistance() {
  return lastDistance;
}

// ESP-NOW Setup
void setupESPNow() {
  WiFi.mode(WIFI_STA);
  WiFi.disconnect();

  if (esp_now_init() != ESP_OK) Serial.println("ESP-NOW Init fehlgeschlagen");

  esp_now_peer_info_t peer{};
  memcpy(peer.peer_addr, sensorMac, 6);
  peer.channel = 0;
  peer.encrypt = false;
  if (esp_now_add_peer(&peer) != ESP_OK) Serial.println("Peer hinzufügen fehlgeschlagen");

  esp_now_register_recv_cb(onDataRecv);
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
        sendToSensor(PKT_HEARTBEAT, true);
      }
      break;

    case PKT_DISTANCE_TO_MASTER:
      if (len == sizeof(DistancePacket)) {
        const DistancePacket *pkt =
          (const DistancePacket *)incomingData;
          lastDistance = pkt->distance;
          if(getGameStatus() == "running") {
            evaluateDistance(pkt->distance);
          }
      }
      break;
  }
}

void sendToSensor(PacketType type, int hitLed) {
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
      Serial.println(getGameStatus());
      msg.state = (getGameStatus() == "running") ? RUNNING : IDLE;
      msg.hit = hitLed;
      esp_now_send(sensorMac, (uint8_t*)&msg, sizeof(msg));
      break;
    }
  }
}