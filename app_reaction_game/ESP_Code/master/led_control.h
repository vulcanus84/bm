#pragma once // Verhindert mehrfaches Einbinden
#include <Arduino.h>
#include <freertos/FreeRTOS.h>
#include <freertos/queue.h>

enum LedCommandType {
  CLEAR,
  SHOW1,
  SHOW2,
  SHOW3,
  SHOW_COUNTDOWN,
};

struct LedCommand {
  LedCommandType type;
};

extern QueueHandle_t ledQueue;

void initLedMatrix();
void taskLedMatrix(void *pvParameters);