#include "wifi_connection.h"
#include "master_connection.h"

void setup() {
  Serial.begin(115200);
  setup_master_connection();
  connectWLAN();
}

void loop() {
  //Check server for new configs every x seconds
  checkServer();

  //Check serial connection over UART to Master ESP
  checkMaster();

  //Give the gateway some time to breathe
  delay(10);
}