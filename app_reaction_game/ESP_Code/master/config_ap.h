#pragma once
#include <Arduino.h>

// Initialisiert Access Point + Webserver
void configAP_begin();

// Muss zyklisch in loop() aufgerufen werden
void configAP_loop();
