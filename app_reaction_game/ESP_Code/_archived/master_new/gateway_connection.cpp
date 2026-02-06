#include "gateway_connection.h"
#include <SoftwareSerial.h>
#include <ArduinoJson.h>  // Bibliothek für JSON
#include "calc_position.h"

const uint8_t gatewayRX = 4;
const uint8_t gatewayTX = 5;
SoftwareSerial gatewaySerial(gatewayRX, gatewayTX);

String gameStatus = "idle";
int userId = 0;
int exerciseId = 0;
int repetitions = 0;
unsigned long lastConfigSend = 0;

void setupGatewayConnection() {
  gatewaySerial.begin(9600, SWSERIAL_8N1, gatewayRX, gatewayTX, false, 256, 0);
}

void checkGateway() {
  if (millis() - lastConfigSend > 2000) {
    sendConfig();
    lastConfigSend = millis();
  } else {
    if (gatewaySerial.available()) {
      String msg = gatewaySerial.readStringUntil('\n');
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
    repetitions = doc["repetitions"].as<int>();

    setSequenceIDs(seqIdStr);
    setSequenceStrings(seqStr);
    setMaxRuns(repetitions);

    if(gameStatus != oldGameStatus) {
      if(gameStatus == "running") startExercise();
      if(gameStatus == "idle") stopExercise();
    }

    // Send back information with additional distance information
    String configString = "CONFIG:?status=" + gameStatus +
            "&user_id=" + String(userId) +
            "&sequence=" + getSequenceAsString() +
            "&nextPos=" + getNextSequenceId() +
            "&repetitions=" + String(repetitions);

    Serial.println(configString);
    gatewaySerial.println(configString);
  } else {
    Serial.print("JSON Fehler: "); Serial.println(err.c_str());
  }
}


void sendConfig() {
  String configString = "CONFIG:?status=" + gameStatus +
          "&user_id=" + String(userId) +
          "&sequence=" + getSequenceAsString() +
          "&nextPos=" + getNextSequenceId();

  gatewaySerial.println(configString);
}

void sendEventToGateway(String eventString) {
  gatewaySerial.println(eventString);
}

String getGameStatus() {
  return gameStatus;
}

void setGameStatus(String gS) {
  gameStatus = gS;
}

int getUserId() {
  return userId;
}

int getExerciseId() {
  return exerciseId;
}