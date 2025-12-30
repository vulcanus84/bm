#include <WiFi.h>
#include <esp_now.h>
#include "esp_timer.h"
#include <ArduinoJson.h>  // Bibliothek für JSON
#include "../common/esp_now_structs.h"
#include "esp_now_handler.h"
#include "calc_position.h"
#include "get_config.h"

// ==================== UART DEFINITIONS ====================
#define UART_RX 6
#define UART_TX 7

unsigned long lastConfigSend = 0;

// ==================== SETUP ====================
void setup() {
  Serial.begin(115200);              // USB Debug
  Serial1.begin(9600, SERIAL_8N1, UART_RX, UART_TX);  // UART zum Gateway
  setupESPNow();
  Serial.println("Master bereit");
}

// ==================== LOOP ====================
void loop() {
  // UART Kommunikation mit Gateway
  if (Serial1.available()) {
    String msg = Serial1.readStringUntil('\n');
    Serial.print("Gateway: "); Serial.println(msg);
    readConfig(msg);
  }
  
  if (millis() - lastConfigSend > 2000) {
    sendConfig();
    lastConfigSend = millis();
  }
  delay(10);
}