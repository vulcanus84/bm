#include <FastLED.h>

#define LED_PIN   9
#define WIDTH     16
#define HEIGHT    16
#define NUM_LEDS  (WIDTH * HEIGHT)
#define BRIGHTNESS 50

CRGB leds[NUM_LEDS];

struct SquarePos {
  uint8_t x; // linke obere Ecke
  uint8_t y; // linke obere Ecke
};

// 3x3 Positionen
SquarePos positions[9] = {
  {11, 1},    // vorne links
  {6, 1},    // vorne mitte
  {1, 1},   // vorne rechts
  {11, 6},    // mitte links
  {6, 6},    // mitte mitte
  {1, 6},   // mitte rechts
  {11, 11},   // hinten links
  {6, 11},   // hinten mitte
  {1, 11}   // hinten rechts
};

void drawSquareWithBlueBorder(uint8_t posId, CRGB fillColor) {
  FastLED.clear();
  // äußere Linie = blau (2px Rand)
  for (uint8_t x = 0; x < 16; x++) {
    leds[XY(x, 0)] = CRGB::Blue;         // obere Kante
    leds[XY(x, 15)] = CRGB::Blue;     // untere Kante
    leds[XY(0, x)] = CRGB::Blue;     // linke Kante
    leds[XY(15, x)] = CRGB::Blue;     // rechte Kante
  }

  // Quadrat zeichnen
  uint8_t startX = positions[posId].x;
  uint8_t startY = positions[posId].y;
  for (uint8_t x = startX; x < startX + 4; x++) {
    for (uint8_t y = startY; y < startY + 4; y++) {
      leds[XY(x, y)] = fillColor;
    }
  }

  FastLED.show();
}


// Serpentine Mapping
uint16_t XY(uint8_t x, uint8_t y) {
  if (y % 2 == 0) return y * WIDTH + x;        // gerade Zeile: links→rechts
  else return y * WIDTH + (WIDTH - 1 - x);     // ungerade Zeile: rechts→links
}

// --- Hier kannst du die Bitmaps selbst zeichnen ---
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

// --- Zahl zeichnen ---
void drawNumber(const uint16_t number[16], CRGB color) {
  for (uint8_t y = 0; y < 16; y++) {
    for (uint8_t x = 0; x < 16; x++) {
      if (number[y] & (1 << (15 - x))) {
        leds[XY(x, y)] = color;
      }
    }
  }
}

void showNumber(const uint16_t number[16], CRGB color, uint16_t delayMs) {
  FastLED.clear();
  drawNumber(number, color);
  FastLED.show();
  delay(delayMs);
}

void drawZoomSquareOutwards(CRGB color, uint16_t delayMs) {
  FastLED.clear();
  
  uint8_t steps = WIDTH / 2; // Anzahl der Rahmenlinien
  for (int8_t i = steps - 1; i >= 0; i--) { // von innen nach außen
    FastLED.clear(); // nur aktuelle Linie anzeigen
    
    // obere und untere Kante
    for (uint8_t x = i; x < WIDTH - i; x++) {
      leds[XY(x, i)] = color;              // obere Kante
      leds[XY(x, HEIGHT - 1 - i)] = color; // untere Kante
    }
    
    // linke und rechte Kante
    for (uint8_t y = i + 1; y < HEIGHT - 1 - i; y++) {
      leds[XY(i, y)] = color;              // linke Kante
      leds[XY(WIDTH - 1 - i, y)] = color;  // rechte Kante
    }
    
    FastLED.show();
    delay(delayMs); // Geschwindigkeit: kleiner = schneller
  }
}

void setAllWhite() {
  for (uint16_t i = 0; i < NUM_LEDS; i++) {
    leds[i] = CRGB::White;
  }
  FastLED.show();
}

void setup() {
  FastLED.addLeds<WS2812B, LED_PIN, GRB>(leds, NUM_LEDS);
  FastLED.setBrightness(BRIGHTNESS);
  FastLED.clear(true);
}

void loop() {
  showNumber(number3, CRGB::Red, 1000);    // 3
  showNumber(number2, CRGB::Orange, 1000); // 2
  showNumber(number1, CRGB::Green, 1000); // 1
  drawZoomSquareOutwards(CRGB::Blue, 30);  // schneller rauszoomendes Quadrat
  for(uint8_t x = 0; x < 9; x++) {
    drawSquareWithBlueBorder(x,CRGB::Red);
    delay(1000);                              // kleine Pause
  }
}
