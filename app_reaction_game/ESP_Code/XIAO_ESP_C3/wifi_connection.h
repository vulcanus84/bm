#pragma once // Verhindert mehrfaches Einbinden
#include <Arduino.h>

enum WifiState {
  WIFI_INIT,
  WIFI_CONNECTING,
  WIFI_CONNECTED,
  SERVER_CHECKING,
  SERVER_OK,
  SERVER_ERROR,
  AP_MODE
};

extern volatile WifiState wifiState;

enum WifiCommandType {
  WIFI_SEND_EVENT,
  WIFI_GET_CONFIG
};

struct WifiCommand {
  WifiCommandType type;
  String payload;
};

extern QueueHandle_t wifiQueue;

void taskWifi(void *pvParameters);
String getConfigFromServer(String configParams);