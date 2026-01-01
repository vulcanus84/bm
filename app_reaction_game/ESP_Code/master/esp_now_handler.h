#pragma once // Verhindert mehrfaches Einbinden
#include <Arduino.h>
#include "esp_now.h"
#include "../common/esp_now_structs.h"

void setupESPNow();
void onDataRecv(const esp_now_recv_info_t *recv_info, const uint8_t *incomingData, int len);
void sendToSensor(PacketType type, int hitLed);
String getGameStatus();
void setGameStatus(String gameStatus);
int getLastDistance();