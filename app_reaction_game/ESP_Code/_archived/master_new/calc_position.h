#pragma once // Verhindert mehrfaches Einbinden
#include <Arduino.h>

void evaluateZone(String zone);
String getSequenceAsString();
void setSequenceIDs(String seqIdStr);
void setSequenceStrings(String seqStr);
String getNextSequenceId();
void startExercise();
void stopExercise();
void setMaxRuns(int repetitions);