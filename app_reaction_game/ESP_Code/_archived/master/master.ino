#include <WiFi.h>
#include <esp_now.h>
#include "esp_timer.h"
#include <ArduinoJson.h>  // Bibliothek für JSON
#include "../common/esp_now_structs.h"
#include "esp_now_handler.h"
#include "calc_position.h"
#include "gateway_connection.h"

// ==================== SETUP ====================
void setup() {
  Serial.begin(115200);
  setupGatewayConnection();
  setupESPNow();
  Serial.println("Master bereit");
}

// ==================== LOOP ====================
void loop() {
  checkGateway();
  delay(10);
}