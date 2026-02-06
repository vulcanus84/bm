#include "led_control.h"
#include <FastLED.h>
#include <string.h>

#define LED_PIN   9
#define WIDTH     16
#define HEIGHT    16
#define NUM_LEDS  (WIDTH * HEIGHT)
#define BRIGHTNESS 50

CRGB leds[NUM_LEDS];
QueueHandle_t ledQueue;

// --- Positionsstruktur für 3x3 Quadrate ---
struct SquarePos {
  uint8_t x; // linke obere Ecke
  uint8_t y; // linke obere Ecke
};

// 3x3 Positionen
SquarePos positions[9] = {
  {11, 1}, {6, 1}, {1, 1},
  {11, 6}, {6, 6}, {1, 6},
  {11, 11},{6, 11},{1, 11}
};

// --- Serpentine Mapping ---
uint16_t XY(uint8_t x, uint8_t y) {
  if (y % 2 == 0) return y * WIDTH + x;
  else return y * WIDTH + (WIDTH - 1 - x);
}

// --- LED Matrix initialisieren ---
void initLedMatrix() {
  FastLED.addLeds<WS2812B, LED_PIN, GRB>(leds, NUM_LEDS);
  FastLED.setBrightness(BRIGHTNESS);
  ledQueue = xQueueCreate(10, sizeof(LedCommand)); // Queue für 10 Befehle
  FastLED.clear();
  FastLED.show();
}

// --- Zeichnungsfunktionen ---

void drawSquareWithBlueBorder(uint8_t posId, CRGB fillColor) {
  FastLED.clear();
  // Rahmen (2px Rand)
  for (uint8_t x = 0; x < 16; x++) {
    leds[XY(x, 0)] = CRGB::Blue;
    leds[XY(x, 15)] = CRGB::Blue;
    leds[XY(0, x)] = CRGB::Blue;
    leds[XY(15, x)] = CRGB::Blue;
  }

  uint8_t startX = positions[posId].x;
  uint8_t startY = positions[posId].y;
  for (uint8_t x = startX; x < startX + 4; x++) {
    for (uint8_t y = startY; y < startY + 4; y++) {
      leds[XY(x, y)] = fillColor;
    }
  }
  FastLED.show();
}

const uint16_t number3[16] = {
  0b0000000000000000,
  0b0000001111100000,
  0b0000011111100000,
  0b0000111000000000,
  0b0000110000000000,
  0b0000110000000000,
  0b0000011000000000,
  0b0000001111000000,
  0b0000001111000000,
  0b0000011000000000,
  0b0000110000000000,
  0b0000110000000000,
  0b0000111000000000,
  0b0000011111100000,
  0b0000001111100000,
  0b0000000000000000
};

const uint16_t number2[16] = {
  0b0000000000000000,
  0b0000001111000000,
  0b0000011111100000,
  0b0000111000110000,
  0b0000110000010000,
  0b0000110000000000,
  0b0000111000000000,
  0b0000011100000000,
  0b0000001100000000,
  0b0000001110000000,
  0b0000000111000000,
  0b0000000011100000,
  0b0000000001110000,
  0b0000111111110000,
  0b0000111111110000,
  0b0000000000000000
};

const uint16_t number1[16] = {
  0b0000000000000000,
  0b0000011110000000,
  0b0000011111000000,
  0b0000011111100000,
  0b0000011101110000,
  0b0000011100111000,
  0b0000011100011100,
  0b0000011100000000,
  0b0000011100000000,
  0b0000011100000000,
  0b0000011100000000,
  0b0000011100000000,
  0b0000011100000000,
  0b0000011100000000,
  0b0000011100000000,
  0b0000000000000000
};

void drawNumber(const uint16_t number[16], CRGB color) {
  for (uint8_t y = 0; y < 16; y++) {
    for (uint8_t x = 0; x < 16; x++) {
      if (number[y] & (1 << (15 - x))) leds[XY(x, y)] = color;
      else leds[XY(x, y)] = CRGB::Black;
    }
    vTaskDelay(pdMS_TO_TICKS(5)); // non-blocking kleine Pause
  }
  FastLED.show();
}

// Zoom-Square Schrittweise
void drawZoomSquareOutwardsStep(CRGB color, uint8_t step, uint8_t maxSteps) {
  uint8_t i = maxSteps - 1 - step;
  FastLED.clear();
  for (uint8_t x = i; x < WIDTH - i; x++) {
    leds[XY(x, i)] = color;
    leds[XY(x, HEIGHT - 1 - i)] = color;
  }
  for (uint8_t y = i + 1; y < HEIGHT - 1 - i; y++) {
    leds[XY(i, y)] = color;
    leds[XY(WIDTH - 1 - i, y)] = color;
  }
  FastLED.show();
}

// Alle LEDs weiß
void setAllWhite() {
  for (uint16_t i = 0; i < NUM_LEDS; i++) leds[i] = CRGB::White;
  FastLED.show();
}

// --- LED Task ---
void taskLedMatrix(void *pvParameters) {
  LedCommand cmd;

  for (;;) {
    // Queue abfragen (non-blocking)
    if (xQueueReceive(ledQueue, &cmd, 0)) {
      switch (cmd.type) {
        case CLEAR:
          FastLED.clear();
          FastLED.show();
          break;

        case SHOW1:
          drawNumber(number1, CRGB::Red);
          break;

        case SHOW2:
          drawNumber(number2, CRGB::Green);
          break;

        case SHOW3:
          drawNumber(number3, CRGB::Blue);
          break;

        case SHOW_COUNTDOWN:
          for (int i = 3; i >= 1; i--) {
            switch(i) {
              case 3: drawNumber(number3, CRGB::Red); break;
              case 2: drawNumber(number2, CRGB::Green); break;
              case 1: drawNumber(number1, CRGB::Blue); break;
            }
            vTaskDelay(pdMS_TO_TICKS(1000)); // 1 Sekunde warten
          }
          drawSquareWithBlueBorder(4, CRGB::Red); // Mitte leeren
          break;
      }
    }

    vTaskDelay(pdMS_TO_TICKS(50)); // Task-yield
  }
}
