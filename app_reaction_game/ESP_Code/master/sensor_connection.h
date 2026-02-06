#pragma once // Verhindert mehrfaches Einbinden
#include <Arduino.h>

void setupSensorConnection();
void checkSensor();
void taskRadar(void *pvParameters);