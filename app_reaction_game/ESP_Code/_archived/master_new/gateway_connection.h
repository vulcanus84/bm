#pragma once // Verhindert mehrfaches Einbinden
#include <Arduino.h>

void readConfig(String payload);
String getGameStatus();
void setGameStatus(String gS);
int getUserId();
int getExerciseId();
void sendConfig();
void setupGatewayConnection();
void checkGateway();
void sendEventToGateway(String eventString);