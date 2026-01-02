#include "wifi_connection.h"
#include "master_connection.h"
#include "config_ap.h"

#define CONFIG_PIN 25
bool setupMode = false;

void setup() {
  Serial.begin(115200);
  pinMode(CONFIG_PIN, INPUT_PULLUP);
  setupMode = digitalRead(CONFIG_PIN) == LOW;

  if(setupMode) {
    Serial.println("Starte Config Modus...");
    configAP_begin();
  } else {
    Serial.println("Starte Betriebsmodus...");
    setup_master_connection();
    initWLAN();
    connectWLAN();
  }
}

void loop() {
  if(setupMode) {
    configAP_loop();
  } else {
    //Check server for new configs every x seconds
    checkServer();

    //Check serial connection over UART to Master ESP
    checkMaster();
  }

  //Give the gateway some time to breathe
  delay(10);
}