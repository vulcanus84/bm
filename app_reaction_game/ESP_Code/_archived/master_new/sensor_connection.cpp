#include "sensor_connection.h"
#include <Arduino.h>
#include <LD2450.h>
#include "calc_position.h"

const uint8_t sensorRX = 6;
const uint8_t sensorTX = 7;

// SENSOR INSTANCE
LD2450 ld2450;
String lastZone = "";

String getZone(int x, int y)
{
  char col = 0;
  char row = 0;

  // X-Achse (links / mitte / rechts)
  if (x < -1200) col = 'L';
  else if (x > 1200) col = 'R';
  else if (x > -600 && x < 600) col = 'M';

  // Y-Achse (vorne / mitte / hinten)
  if (y < 1500) row = 'V';
  else if (y > 5000) row = 'H';
  else if (y > 2500 && y < 4000) row = 'M';

  if (col == 0 || row == 0) return ""; // ungültig

  String zone = "";
  zone += row;
  zone += col;
  return zone;
}


void setupSensorConnection() {
  Serial1.begin(256000, SERIAL_8N1, sensorRX, sensorTX); // RX=6, TX=7
  ld2450.begin(Serial1, false);
}

void checkSensor() {
  ld2450.read();

  int nearestDist = 999999;
  LD2450::RadarTarget nearest;
  bool found = false;

  // Nächstes gültiges Target suchen
  for (int i = 0; i < ld2450.getSensorSupportedTargetCount(); i++) {
    const LD2450::RadarTarget t = ld2450.getTarget(i);
    if (t.valid && t.distance < nearestDist) {
      nearest = t;
      nearestDist = t.distance;
      found = true;
    }
  }

  if (found) {
    String zone = getZone(nearest.x, nearest.y);

    // NUR bei Wechsel ausgeben
    if (zone != lastZone && zone != "") {
      lastZone = zone;
      Serial.println(zone);
      evaluateZone(zone);
    }
  }
}