#include "led_control.h"

// Initialisierung der LEDs
LedControl ok  = { 25, LED_OFF, 0, LOW, false };  // Pin 25
LedControl hit = { 26, LED_OFF, 0, LOW, false };  // Pin 26

void setInitialLedStates() {
    pinMode(ok.pin, OUTPUT);
    pinMode(hit.pin, OUTPUT);

    setLedState(ok, LED_BLINK_FAST);
    setLedState(hit, LED_OFF);
}

void setLedState(LedControl &led, LedState newState) {
    led.state = newState;
    led.lastToggle = millis();
    led.oneBlinkDone = false;

    if (newState == LED_ON) digitalWrite(led.pin, HIGH);
    else if (newState == LED_OFF) digitalWrite(led.pin, LOW);
}

void updateLeds() {
    unsigned long now = millis();
    unsigned long interval;

    // Pointers auf LEDs, sauber in Schleife iterierbar
    LedControl* leds[] = { &ok, &hit };

    for (LedControl* pLed : leds) {
        LedControl &led = *pLed; // Referenz auf aktuelles Objekt

        switch (led.state) {
            case LED_OFF:
            case LED_ON:
                break;

            case LED_BLINK:
                interval = 800;
                if (now - led.lastToggle >= interval) {
                    led.lastToggle = now;
                    led.level = !led.level;
                    digitalWrite(led.pin, led.level);
                }
                break;

            case LED_BLINK_FAST:
                interval = 120;
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
}
