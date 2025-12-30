#include "get_config.h"
#include <ArduinoJson.h>  // Bibliothek für JSON
#include "esp_now_handler.h"
#include "../common/esp_now_structs.h"
#include "calc_position.h"

String gameStatus = "idle";
int userId = 0;
int exerciseId = 0;

void readConfig(String payload) {
  StaticJsonDocument<256> doc;
  DeserializationError err = deserializeJson(doc, payload);
  if (!err) {
    String oldGameStatus = gameStatus;
    gameStatus = doc["status"].as<String>();
    if(gameStatus != oldGameStatus) {
      if(gameStatus == "running") startExercise();
      if(gameStatus == "idle") stopExercise();
    }
    if (gameStatus == "idle") {
      String seqStr = doc["sequence"].as<String>();
      String seqIdStr = doc["sequenceIds"].as<String>();
      userId = doc["userId"].as<int>();
      exerciseId = doc["exerciseId"].as<int>();

      Serial.print("Status: "); Serial.println(gameStatus);
      Serial.print("UserId: "); Serial.println(userId);
      Serial.print("ExerciseId: "); Serial.println(exerciseId);
      Serial.print("Sequenz: "); Serial.println(seqStr);

      setSequenceIDs(seqIdStr);
      setSequenceStrings(seqStr);
      
      String configString = "CONFIG:?status=" + gameStatus +
              "&user_id=" + String(userId) +
              "&distance=" + String(getLastDistance()) +
              "&sequence=" + getSequenceAsString() +
              "&nextPos=" + getNextSequenceId();

      Serial.println(configString);
      Serial1.println(configString);

    } else {
      Serial.println("Status not idle, no config set");
    }
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