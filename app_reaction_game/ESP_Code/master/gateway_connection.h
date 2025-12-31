#pragma once // Verhindert mehrfaches Einbinden
#include <Arduino.h>

void readConfig(String payload);
String getGameStatus();
int getUserId();
int getExerciseId();
void sendConfig();
void setupGatewayConnection();
void checkGateway();