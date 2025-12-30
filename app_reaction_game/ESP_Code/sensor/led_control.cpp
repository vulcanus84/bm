#include "led_control.h"

// =======================
// Globale LED-Instanzen
// =======================
LedControl ok  = { 25, LED_OFF, 0, LOW, false };
LedControl hit = { 26, LED_OFF, 0, LOW, false };

// =======================
// Initialisierung
// =======================
void setInitialLedStates() {
    pinMode(ok.pin, OUTPUT);
    pinMode(hit.pin, OUTPUT);

    setLedState(ok, LED_BLINK_FAST);
    setLedState(hit, LED_OFF);
}

// =======================
// Zustand setzen
// =======================
void setLedState(LedControl &led, LedState newState) {
    led.state = newState;
    led.lastToggle = millis();
    led.oneBlinkDone = false;

    switch (newState) {
        case LED_ON:
            led.level = HIGH;
            digitalWrite(led.pin, HIGH);
            break;

        case LED_OFF:
            led.level = LOW;
            digitalWrite(led.pin, LOW);
            break;

        case LED_ONEBLINK:
            // EIN schalten, Ausschalten erfolgt im updateLeds()
            led.level = HIGH;
            digitalWrite(led.pin, HIGH);
            break;

        default:
            // BLINK / BLINK_FAST: nichts sofort schalten
            break;
    }
}

// =======================
// LEDs aktualisieren
// =======================
void updateLeds() {
    unsigned long now = millis();

    // einfache Iteration, robust
    LedControl* leds[] = { &ok, &hit };

    for (LedControl* pLed : leds) {
        LedControl &led = *pLed;

        switch (led.state) {

            case LED_OFF:
            case LED_ON:
                break;

            case LED_BLINK:
                if (now - led.lastToggle >= 800) {
                    led.lastToggle = now;
                    led.level = !led.level;
                    digitalWrite(led.pin, led.level);
                }
                break;

            case LED_BLINK_FAST:
                if (now - led.lastToggle >= 120) {
                    led.lastToggle = now;
                    led.level = !led.level;
                    digitalWrite(led.pin, led.level);
                }
                break;

            case LED_ONEBLINK:
                // EIN → warten → AUS → zurück zu OFF
                if (now - led.lastToggle >= 200) {
                    led.level = LOW;
                    digitalWrite(led.pin, LOW);
                    led.state = LED_OFF;
                }
                break;
        }
    }
}
