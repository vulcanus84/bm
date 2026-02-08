#include "wifi_connection.h"
#include "led_control.h"
#include "sensor_connection.h"
#include "game_control.h"
#include "perf_mon.h"

void setup() {
  Serial.begin(115200);
  delay(1000); // Warte auf stabile serielle Verbindung

  setupSensorConnection();

  // LED-Matrix initialisieren
  initLedMatrix();

  // Queues starten
  ledQueue = xQueueCreate(50, sizeof(LedCommand)); // Queue für LED-Befehle
  gcQueue = xQueueCreate(5, sizeof(GcCommand)); // Queue für Game Control-Kommunikation
  wifiQueue = xQueueCreate(5, sizeof(WifiCommand)); // Queue für WLAN-Kommunikation

  // Tasks starten
  xTaskCreate(taskWifi, "WifiTask", 8192, NULL, 1, NULL);
  xTaskCreate(taskLedMatrix, "LedMatrixTask", 4096, NULL, 10, NULL);
  xTaskCreate(taskGameControl, "GameControlTask", 8192, NULL, 5, NULL);
  xTaskCreate(taskRadar, "RadarTask", 8192, NULL, 8, NULL);

  //xTaskCreate(taskPerformanceMonitor,"PerfMon",4096,NULL,1,NULL);
}

void loop() {
  // Hauptloop bleibt leer, alle Logik in Tasks
    vTaskDelay(pdMS_TO_TICKS(100));
}