#pragma once // Verhindert mehrfaches Einbinden
#include <Arduino.h>

extern String configParams;
extern String triggerParams;

bool testHttpConnection();
void connectWLAN();
void checkServer();
void sendEventToServer();