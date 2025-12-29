#include "led_control.h"

void setLedState(LedControl &led, LedState newState) {
  led.state = newState;
  led.lastToggle = millis();
  led.oneBlinkDone = false;

  if (newState == LED_ON) digitalWrite(led.pin, HIGH);
  else if (newState == LED_OFF) digitalWrite(led.pin, LOW);
}

void updateLed(LedControl &led) {
  unsigned long now = millis();
  unsigned long interval;

  switch (led.state) {
    case LED_OFF:
    case LED_ON:
      break;

    case LED_BLINK:
      interval = 500;
      if (now - led.lastToggle >= interval) {
        led.lastToggle = now;
        led.level = !led.level;
        digitalWrite(led.pin, led.level);
      }
      break;

    case LED_BLINK_FAST:
      interval = 150;
      if (now - led.lastToggle >= interval) {
        led.lastToggle = now;
        led.level = !led.level;
        digitalWrite(led.pin, led.level);
      }
      break;

    case LED_ONEBLINK:
      if (!led.oneBlinkDone && now - led.lastToggle >= 200) {
        digitalWrite(led.pin, LOW);
        led.oneBlinkDone = true;
        led.state = LED_OFF;
      }
      break;
  }
}
