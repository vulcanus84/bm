#include "led_control.h"
#include "game_control.h"
#include <FastLED.h>
#include <string.h>

#define LED_PIN   9
#define WIDTH     16
#define HEIGHT    16
#define NUM_LEDS  (WIDTH * HEIGHT)
#define BRIGHTNESS 20

CRGB leds[NUM_LEDS];
QueueHandle_t ledQueue;

// --- Positionsstruktur für 3x3 Quadrate ---
struct SquarePos {
  uint8_t x; // linke obere Ecke
  uint8_t y; // linke obere Ecke
};

// 3x3 Positionen
SquarePos positions[9] = {
  {1, 1}, {6, 1}, {11, 1},
  {1, 6}, {6, 6}, {11, 6},
  {1, 11},{6, 11},{11, 11}
};

// --- Serpentine Mapping ---
uint16_t XY(uint8_t x, uint8_t y) {
  y = HEIGHT - 1 - y;  // vertikale Spiegelung

  if (y % 2 == 0) return y * WIDTH + x;
  else return y * WIDTH + (WIDTH - 1 - x);
}

// --- LED Matrix initialisieren ---
void initLedMatrix() {
  FastLED.addLeds<WS2812B, LED_PIN, GRB>(leds, NUM_LEDS);
  FastLED.setBrightness(BRIGHTNESS);
  FastLED.clear();
  FastLED.show();
}

// --- Zeichnungsfunktionen ---
void showPointOnLedMatrix(int x, int y) {
  if(x < -2000) x = -2000;
  if(x > 2000) x = 2000;
  if(y < 0) y = 0;
  if(y > 6000) y = 6000;
  
  int mappedX = map(x, -2000, 2000, 0, WIDTH - 1);
  int mappedY = map(y, 0, 6000, 0, HEIGHT - 1);
  leds[XY(mappedX, mappedY)] = CRGB::Purple;
  FastLED.show();
}


void drawSquareWithBlueBorder(uint8_t posId, CRGB fillColor) {
  FastLED.clear();
  CRGB borderColor = CRGB::Blue;
  CRGB darkGray = CRGB(20, 20, 20); // Sehr dunkles Grau für inaktives Spiel

  if(getGameStatus() != IDLE) {
    borderColor = CRGB::Blue; // Blau für laufendes Spiel
  } else {
    borderColor = darkGray; // Grau für inaktives Spiel
  }

  for (uint8_t x = 0; x < 16; x++) {
    leds[XY(x, 0)] = borderColor;
    leds[XY(x, 15)] = borderColor;
    leds[XY(0, x)] = borderColor;
    leds[XY(15, x)] = borderColor;
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
  0b0000011111000000,
  0b0000011111100000,
  0b0000000001110000,
  0b0000000000110000,
  0b0000000000110000,
  0b0000000000110000,
  0b0000011111100000,
  0b0000011111100000,
  0b0000000000110000,
  0b0000000000110000,
  0b0000000000110000,
  0b0000000001110000,
  0b0000011111100000,
  0b0000011111000000,
  0b0000000000000000
};

const uint16_t number2[16] = {
  0b0000000000000000,
  0b0000001111000000,
  0b0000011111100000,
  0b0000110001110000,
  0b0000100000110000,
  0b0000000000110000,
  0b0000000001110000,
  0b0000000011100000,
  0b0000000011000000,
  0b0000000111000000,
  0b0000001110000000,
  0b0000011100000000,
  0b0000111000000000,
  0b0000111111110000,
  0b0000111111110000,
  0b0000000000000000
};

const uint16_t number1[16] = {
  0b0000000000000000,
  0b0000000111100000,
  0b0000001111100000,
  0b0000011111100000,
  0b0000111011100000,
  0b0001110011100000,
  0b0011100011100000,
  0b0000000011100000,
  0b0000000011100000,
  0b0000000011100000,
  0b0000000011100000,
  0b0000000011100000,
  0b0000000011100000,
  0b0000000011100000,
  0b0000000011100000,
  0b0000000000000000
};

const uint16_t smiley_happy[16] = {
  0b0000000000000000,
  0b0000000000000000,
  0b0000100000001000,
  0b0001110000011100,
  0b0011111000111110,
  0b0001110000011100,
  0b0000100000001000,
  0b0000000110000000,
  0b0000000110000000,
  0b0000000110000000,
  0b0000000110000000,
  0b0011000000001100,
  0b0001100000011000,
  0b0000110000110000,
  0b0000011111100000,
  0b0000000000000000
};

const uint16_t smiley_sad[16] = {
  0b0000000000000000,
  0b0000000000000000,
  0b0000100000001000,
  0b0001110000011100,
  0b0011111000111110,
  0b0001110000011100,
  0b0000100000001000,
  0b0000000110000000,
  0b0000000110000000,
  0b0000000110000000,
  0b0000000110000000,
  0b0000000000000000,
  0b0000111111110000,
  0b0001100000011000,
  0b0011000000001100,
  0b0000000000000000
};

const uint16_t ap_mode[16] = {
  0b0000000000000000,
  0b0000000000000000,
  0b0000001111000000,
  0b0000010000100000,
  0b0000100110010000,
  0b0001001001001000,
  0b0000010000100000,
  0b0000000110000000,
  0b0000000000000000,
  0b0000000000000000,
  0b0000001001110000,
  0b0000010101010000,
  0b0000011101110000,
  0b0000010101000000,
  0b0000010101000000,
  0b0000000000000000
};


void drawSymbol(const uint16_t number[16], CRGB color) {
  for (uint8_t y = 0; y < 16; y++) {
    for (uint8_t x = 0; x < 16; x++) {
      if (number[y] & (1 << (15 - x))) leds[XY(x, y)] = color;
      else leds[XY(x, y)] = CRGB::Black;
    }
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
        case SHOW_PLAYER:
          showPointOnLedMatrix(cmd.x, cmd.y);
          break;

        case CLEAR:
          FastLED.clear();
          FastLED.show();
          break;

        case SHOW1:
          drawSymbol(number1, CRGB::Green);
          break;

        case SHOW2:
          drawSymbol(number2, CRGB::Orange);
          break;

        case SHOW3:
          drawSymbol(number3, CRGB::Red);
          break;

        case SHOW_AP_MODE:
          drawSymbol(ap_mode, CRGB::Green);
          break;

        case FINISHED:
          drawSymbol(smiley_happy, CRGB::Green);
          vTaskDelay(pdMS_TO_TICKS(2000));
          break;

        case ABORTED:
          drawSymbol(smiley_sad, CRGB::Red);
          vTaskDelay(pdMS_TO_TICKS(2000));
          break;


        case SHOW_COUNTDOWN:
          for (int i = 3; i >= 1; i--) {
            switch(i) {
              case 3: drawSymbol(number3, CRGB::Red); break;
              case 2: drawSymbol(number2, CRGB::Orange); break;
              case 1: drawSymbol(number1, CRGB::Green); break;
            }
            vTaskDelay(pdMS_TO_TICKS(1000)); // 1 Sekunde warten
          }
          startTime(); // Timer starten, wenn die erste Position angezeigt wird
          break;

        case SHOW_VL:
          drawSquareWithBlueBorder(0, cmd.fillColor);
          if(cmd.fillColor == CRGB::Green) vTaskDelay(pdMS_TO_TICKS(500));
          break;
        case SHOW_VM:
          drawSquareWithBlueBorder(1, cmd.fillColor);
          if(cmd.fillColor == CRGB::Green) vTaskDelay(pdMS_TO_TICKS(500));
          break;
        case SHOW_VR:
          drawSquareWithBlueBorder(2, cmd.fillColor);
          if(cmd.fillColor == CRGB::Green) vTaskDelay(pdMS_TO_TICKS(500));
          break;
        case SHOW_ML:
          drawSquareWithBlueBorder(3, cmd.fillColor);
          if(cmd.fillColor == CRGB::Green) vTaskDelay(pdMS_TO_TICKS(500));
          break;
        case SHOW_MM:
          drawSquareWithBlueBorder(4, cmd.fillColor);
          if(cmd.fillColor == CRGB::Green) vTaskDelay(pdMS_TO_TICKS(500));
          break;
        case SHOW_MR:
          drawSquareWithBlueBorder(5, cmd.fillColor);
          if(cmd.fillColor == CRGB::Green) vTaskDelay(pdMS_TO_TICKS(500));
          break;
        case SHOW_HL:
          drawSquareWithBlueBorder(6, cmd.fillColor);
          if(cmd.fillColor == CRGB::Green) vTaskDelay(pdMS_TO_TICKS(500));
          break;
        case SHOW_HM:
          drawSquareWithBlueBorder(7, cmd.fillColor);
          if(cmd.fillColor == CRGB::Green) vTaskDelay(pdMS_TO_TICKS(500));
          break;
        case SHOW_HR:
          drawSquareWithBlueBorder(8, cmd.fillColor);
          if(cmd.fillColor == CRGB::Green) vTaskDelay(pdMS_TO_TICKS(500));
          break;
      }
    }

    vTaskDelay(pdMS_TO_TICKS(10));
  }
}
