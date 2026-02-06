#include "wifi_connection.h"
#include "led_control.h"
#include "sensor_connection.h"
#include "game_control.h"

void setup() {
  Serial.begin(115200);
  delay(1000); // Warte auf stabile serielle Verbindung

  setupSensorConnection();

  // LED-Matrix initialisieren
  initLedMatrix();

  // Tasks starten
  xTaskCreate(taskWifi, "WifiTask", 8192, NULL, 1, NULL);
  xTaskCreate(taskLedMatrix, "LedMatrixTask", 4096, NULL, 10, NULL);
  xTaskCreate(taskGameControl, "GameControlTask", 4096, NULL, 3, NULL);
  xTaskCreate(taskRadar, "RadarTask", 4096, NULL, 3, NULL);
}

void loop() {
  // Hauptloop bleibt leer, alle Logik in Tasks
}