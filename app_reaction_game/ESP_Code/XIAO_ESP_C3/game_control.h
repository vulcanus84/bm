#pragma once
#include <Arduino.h>

enum GameStatus { 
  IDLE, 
  WAIT_START_POSITION, 
  WAIT_COUNTDOWN, 
  RUNNING,
  STOPPING
};

enum GcCommandType {
  GC_READ_CONFIG
};

struct GcCommand {
  GcCommandType type;
  String payload;
};

extern QueueHandle_t gcQueue;

void taskGameControl(void *pvParameters);
GameStatus getGameStatus();
void evaluateZone(String zone);
void startTime();