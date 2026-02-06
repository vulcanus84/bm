#pragma once
#include <Arduino.h>

void taskGameControl(void *pvParameters);
void readConfig(String payload);
String getGameStatus();
void setGameStatus(String gS);
int getUserId();
int getExerciseId();
void evaluateZone(String zone);
