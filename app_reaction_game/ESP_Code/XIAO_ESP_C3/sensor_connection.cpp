#include "sensor_connection.h"
#include "led_control.h"
#include <Arduino.h>
#include <LD2450.h>
#include "game_control.h"

extern QueueHandle_t ledQueue;  // optional, wenn du auf Sensor-Zonen visuelle Rückmeldung willst

// RX / TX Pins
const uint8_t sensorRX = 6;
const uint8_t sensorTX = 7;

LedCommand sc_ledCmd;

// SENSOR INSTANCE
LD2450 ld2450;
String lastZone = "";

// ----------------------------
// Hilfsfunktion für Zone
String getZone(int x, int y)
{
  char col = 0;
  char row = 0;

  // X-Achse
  if (x < -1200) col = 'L';
  else if (x > 1200) col = 'R';
  else if (x > -600 && x < 600) col = 'M';

  // Y-Achse
  if (y < 1500) row = 'V';
  else if (y > 5000) row = 'H';
  else if (y > 2500 && y < 4000) row = 'M';

  if (col == 0 || row == 0) return ""; // ungültig

  String zone = "";
  zone += row;
  zone += col;
  return zone;
}

// ----------------------------
// Sensor initialisieren
void setupSensorConnection() {
  Serial1.begin(256000, SERIAL_8N1, sensorRX, sensorTX); // RX=6, TX=7
  ld2450.begin(Serial1, false);
}

// ----------------------------
// Sensor auslesen
void readSensor() {
  ld2450.read();

  int nearestDist = 999999;
  LD2450::RadarTarget nearest;
  bool found = false;

  for (int i = 0; i < ld2450.getSensorSupportedTargetCount(); i++) {
    const LD2450::RadarTarget t = ld2450.getTarget(i);
    if (t.valid && t.distance < nearestDist) {
      nearest = t;
      nearestDist = t.distance;
      found = true;
    }
  }

  if (found) {
    // sc_ledCmd.type = SHOW_PLAYER;
    // sc_ledCmd.fillColor = CRGB::Purple;
    // sc_ledCmd.x = nearest.x;
    // sc_ledCmd.y = nearest.y;
    // xQueueSend(ledQueue, &sc_ledCmd, 0);
    showPointOnLedMatrix(nearest.x, nearest.y); // Optional: Zeige die Position auf der LED-Matrix
    String zone = getZone(nearest.x, nearest.y);

    if (zone != lastZone && zone != "") {
      lastZone = zone;
      Serial.print("Sensor Zone: ");
      Serial.println(zone);
      evaluateZone(zone);
    }
  }
}

// ----------------------------
// Task für FreeRTOS
void taskRadar(void *pvParameters) {
  setupSensorConnection();

  for (;;) {
    readSensor();
    vTaskDelay(pdMS_TO_TICKS(10));
  }
}
