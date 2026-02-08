#pragma once // Verhindert mehrfaches Einbinden
#include <Arduino.h>
#include <freertos/FreeRTOS.h>
#include <freertos/queue.h>
#include <FastLED.h>

enum LedCommandType {
  CLEAR,
  SHOW1,
  SHOW2,
  SHOW3,
  SHOW_COUNTDOWN,
  SHOW_PLAYER,
  FINISHED,
  ABORTED,
  SHOW_AP_MODE,
  SHOW_VL,
  SHOW_VM,
  SHOW_VR,
  SHOW_ML,
  SHOW_MM,
  SHOW_MR,
  SHOW_HL,
  SHOW_HM,
  SHOW_HR
};

struct LedCommand {
  LedCommandType type;
  CRGB fillColor;
  int x; // optional für SHOW_PLAYER
  int y; // optional für SHOW_PLAYER
};

extern QueueHandle_t ledQueue;

void initLedMatrix();
void taskLedMatrix(void *pvParameters);
void showPointOnLedMatrix(int x, int y);