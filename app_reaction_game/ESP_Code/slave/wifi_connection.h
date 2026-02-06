#pragma once // Verhindert mehrfaches Einbinden
#include <Arduino.h>

enum WifiState {
  WIFI_INIT,
  WIFI_CONNECTING,
  WIFI_CONNECTED,
  SERVER_CHECKING,
  SERVER_OK,
  SERVER_ERROR
};

extern volatile WifiState wifiState;

void taskWifi(void *pvParameters);
void sendEventToServer(String triggerParams);
String getConfigFromServer(String configParams);