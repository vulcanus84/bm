#include <WiFi.h>
#include "esp_timer.h"
#include <ArduinoJson.h>  // Bibliothek für JSON
#include "led_control.h"
#include "calc_position.h"
#include "gateway_connection.h"
#include "sensor_connection.h"

// ==================== SETUP ====================
void setup() {
  Serial.begin(115200);
  setupGatewayConnection();
  setupSensorConnection();
  setInitialLedStates();

  Serial.println("Master bereit");
}

void loop()
{
  checkGateway();
  checkSensor();
  updateLeds();

  delay(10); // ruhig & stabil
}

