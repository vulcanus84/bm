#include "get_distance.h"
#include "esp_now_handler.h"

#define FILTER_SIZE 5

int filterValues[FILTER_SIZE];
uint8_t filterIndex = 0;
bool filterFilled = false;
int lastSmoothed = -1;
int lastDistance = -1;

// Frame Parser
const uint8_t FRAME_HEADER = 0xF4;
const uint8_t FRAME_END[4] = {0xF8, 0xF7, 0xF6, 0xF5};
const uint8_t DATA_TYPE_BASIC = 0x02;
const int BUFFER_SIZE = 128;
uint8_t buffer[BUFFER_SIZE];
bool inFrame = false;
uint16_t bufIndex = 0;

int smoothDistance(int newValue) {
  if (newValue <= 0 || newValue > 3000) return lastSmoothed;
  filterValues[filterIndex] = newValue;
  filterIndex = (filterIndex + 1) % FILTER_SIZE;
  if (filterIndex == 0) filterFilled = true;
  int count = filterFilled ? FILTER_SIZE : filterIndex;
  long sum = 0;
  for (int i = 0; i < count; i++) sum += filterValues[i];
  lastSmoothed = sum / count;
  return lastSmoothed;
}

void readDistance(HardwareSerial& serial) {
   while (Serial2.available()) {
    uint8_t b = Serial2.read();
    if (!inFrame) {
      if (b == FRAME_HEADER) { buffer[0]=b; bufIndex=1; inFrame=true; }
    } else {
      buffer[bufIndex++] = b;
      if (bufIndex>=BUFFER_SIZE) { bufIndex=0; inFrame=false; }
      if (bufIndex>=4 &&
          buffer[bufIndex-4]==FRAME_END[0] &&
          buffer[bufIndex-3]==FRAME_END[1] &&
          buffer[bufIndex-2]==FRAME_END[2] &&
          buffer[bufIndex-1]==FRAME_END[3]) {

        int dataLen = buffer[4] | (buffer[5]<<8);
        if (dataLen+10 <= bufIndex && buffer[6]==DATA_TYPE_BASIC) {
          uint8_t* data = &buffer[7];
          int movementDist = data[2] | (data[3]<<8);
          int stationaryDist = data[5] | (data[6]<<8);
          int closest = movementDist>0?movementDist:stationaryDist;

          lastDistance = smoothDistance(closest);
          sendDistance(lastDistance);
        }

        bufIndex=0;
        inFrame=false;
      }
    }
  }
}