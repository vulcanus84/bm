#pragma once // Verhindert mehrfaches Einbinden
#include <Arduino.h>
#include "esp_now.h"

void setupESPNow();
void onDataRecv(const esp_now_recv_info_t *recv_info, const uint8_t *incomingData, int len);
void sendDistance(int dist);
void checkHeartbeat();