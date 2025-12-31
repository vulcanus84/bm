#include "gateway_connection.h"
#include <ArduinoJson.h>  // Bibliothek für JSON
#include "esp_now_handler.h"
#include "../common/esp_now_structs.h"
#include "calc_position.h"

const uint8_t gatewayRX = 6;
const uint8_t gatewayTX = 7;

String gameStatus = "idle";
int userId = 0;
int exerciseId = 0;
unsigned long lastConfigSend = 0;

void setupGatewayConnection() {
  Serial1.begin(9600, SERIAL_8N1, gatewayRX, gatewayTX);  // UART zum Gateway
}

void checkGateway() {
  if (millis() - lastConfigSend > 2000) {
    sendConfig();
    lastConfigSend = millis();
  } else {
    if (Serial1.available()) {
      String msg = Serial1.readStringUntil('\n');
      Serial.print("Gateway: "); Serial.println(msg);
      readConfig(msg);
    }
  }
}
void readConfig(String payload) {
  StaticJsonDocument<256> doc;
  DeserializationError err = deserializeJson(doc, payload);
  if (!err) {
    String oldGameStatus = gameStatus;
    gameStatus = doc["status"].as<String>();
    String seqStr = doc["sequence"].as<String>();
    String seqIdStr = doc["sequenceIds"].as<String>();
    userId = doc["userId"].as<int>();
    exerciseId = doc["exerciseId"].as<int>();

    setSequenceIDs(seqIdStr);
    setSequenceStrings(seqStr);

    if(gameStatus != oldGameStatus) {
      if(gameStatus == "running") startExercise();
      if(gameStatus == "idle") stopExercise();
    }

    // Send back information with additional distance information
    String configString = "CONFIG:?status=" + gameStatus +
            "&user_id=" + String(userId) +
            "&distance=" + String(getLastDistance()) +
            "&sequence=" + getSequenceAsString() +
            "&nextPos=" + getNextSequenceId();

    Serial.println(configString);
    Serial1.println(configString);
  } else {
    Serial.print("JSON Fehler: "); Serial.println(err.c_str());
  }
}


void sendConfig() {
  String configString = "CONFIG:?status=" + gameStatus +
          "&user_id=" + String(userId) +
          "&distance=" + String(getLastDistance()) +
          "&sequence=" + getSequenceAsString() +
          "&nextPos=" + getNextSequenceId();

  Serial1.println(configString);
}

String getGameStatus() {
  return gameStatus;
}

int getUserId() {
  return userId;
}

int getExerciseId() {
  return exerciseId;
}