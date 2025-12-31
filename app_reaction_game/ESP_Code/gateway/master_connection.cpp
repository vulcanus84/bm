#include "master_connection.h"
#include "wifi_connection.h"

// ---- Kommunikation mit ESP-NOW Handler über UART ----
const uint8_t RXD2 = 22;
const uint8_t TXD2 = 23;

void setup_master_connection() {
  Serial1.begin(9600, SERIAL_8N1, RXD2, TXD2);
}

void checkMaster() {
  if (Serial1.available()) {
    String msg = Serial1.readStringUntil('\n');
    Serial.println("Received: " + msg);
    //Get Trigger command
    if (msg.startsWith("EVENT:")) {
      triggerParams = msg.substring(6);
      Serial.print("Trigger Params gesetzt: "); Serial.println(triggerParams);
      sendEventToServer();
      return;
    }

    //Get config params
    if (msg.startsWith("CONFIG:")) {
      configParams = msg.substring(7);
      configParams.replace("\r", "");
      configParams.replace("\n", "");
      configParams.trim();
      Serial.print("Neue Config Params: "); Serial.println(configParams);
    }
    
  }
}
