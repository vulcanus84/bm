#pragma once
#include <Arduino.h>

enum LedState {
  LED_OFF,
  LED_ON,
  LED_BLINK,
  LED_BLINK_FAST,
  LED_ONEBLINK
};

struct LedControl {
  uint8_t pin;
  LedState state;
  unsigned long lastToggle;
  bool level;
  bool oneBlinkDone;
};

void setLedState(LedControl &led, LedState newState);
void updateLed(LedControl &led);
