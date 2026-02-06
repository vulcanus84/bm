#pragma once // Verhindert mehrfaches Einbinden
#include <Arduino.h>

enum LedState {
  LED_OFF,
  LED_ON,
  LED_BLINK,
  LED_BLINK_FAST,
  LED_ONEBLINK,
  LED_FIVEBLINKS
};

struct LedControl {
  uint8_t pin;
  LedState state;
  unsigned long lastToggle;
  bool level;
  bool oneBlinkDone;
  uint8_t blinkCount; 
};

// Globale Instanzen (werden in .cpp definiert)
extern LedControl ok;
extern LedControl hit;

void setLedState(LedControl &led, LedState newState);
void updateLeds();
void setInitialLedStates();