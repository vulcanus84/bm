#include <WiFi.h>
#include "esp_timer.h"
#include "../common/esp_now_structs.h"
#include "led_control.h"
#include "esp_now_handler.h"
#include "get_distance.h"

// UART Pins for Distance Sensor
#define RXD2 16
#define TXD2 17

void setup() {
  Serial.begin(115200);
  Serial2.begin(9600, SERIAL_8N1, RXD2, TXD2);
  setInitialLedStates();
  setupESPNow();
  Serial.println("Sensor bereit");
}

void loop() {
  updateLeds();
  checkHeartbeat();
  readDistance(Serial2);
  delay(1);
}
