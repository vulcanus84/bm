#include <Arduino.h>
#include <LD2450.h>

// SENSOR INSTANCE
LD2450 ld2450;

void setup()
{
  // Native USB Serial (XIAO ESP32-C3)
  Serial.begin(115200);

  // Max. 2 Sekunden warten – kein Deadlock
  unsigned long start = millis();
  while (!Serial && millis() - start < 2000) {
    delay(10);
  }

  Serial.println();
  Serial.println("XIAO ESP32-C3 BOOTING");

  // ✅ Serial1 ist bereits vorhanden → nur konfigurieren
  Serial1.begin(256000, SERIAL_8N1, 6, 7); // RX=6, TX=7

  Serial.println("UART STARTED");

  // Sensor initialisieren (nicht blockierend)
  ld2450.begin(Serial1, false);

  Serial.println("WAITING FOR SENSOR...");

  bool sensor_ok = false;
  unsigned long sensorStart = millis();

  while (millis() - sensorStart < 2000) {
    if (!ld2450.waitForSensorMessage()) {
      sensor_ok = true;
      break;
    }
    delay(10);
  }

  if (sensor_ok) {
    Serial.println("SENSOR CONNECTION OK");
  } else {
    Serial.println("SENSOR NOT RESPONDING (CONTINUING)");
  }

  Serial.println("SETUP FINISHED");
}

String lastZone = "";

String getZone(int x, int y)
{
  char col;
  char row;

  // X-Achse (links / mitte / rechts)
  if (x < -1000) col = 'L';
  else if (x > 1000) col = 'R';
  else col = 'M';

  // Y-Achse (vorne / mitte / hinten)
  if (y < 2000) row = 'V';
  else if (y > 4000) row = 'H';
  else row = 'M';

  String zone = "";
  zone += row;
  zone += col;
  return zone;
}

void loop()
{
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
    if (zone != lastZone) {
      lastZone = zone;
      Serial.println(zone);
    }
  }

  delay(20); // ruhig & stabil
}

