  #include <WiFi.h>
  #include <HTTPClient.h>
  #include <ArduinoJson.h>  // Bibliothek für JSON

  // ===================== DEFINITIONEN =====================

  // ---- Maximalwerte ----
  const uint8_t MAX_SEQ = 10;
  const uint16_t BUFFER_SIZE = 128;
  const uint8_t FILTER_SIZE = 5;

  // ---- Range Typ ----
  enum RangeType { V, M, H, X }; // X = undefiniert
  RangeType sequence[MAX_SEQ];
  uint8_t sequenceIds[MAX_SEQ];
  int SEQ_LENGTH = 0;
  int seqIndex = 0;
  RangeType lastRange = X; // undefinierter Zustand

  int userId = 0;        // wie vorher
  int exerciseId = 0;    // neu, genau wie userId

  // ---- WLAN ----
  const char* ssid = "Claude";
  const char* password = "0792318193";
  String mac = "";
  bool networkReady = false;

  // ---- LEDs ----
  const uint8_t okLed  = 25;
  const uint8_t hitLed = 26;
  unsigned long lastLedToggle = 0;
  bool okLedState = LOW;

  // ---- Radar UART ----
  const uint8_t RXD2 = 16;
  const uint8_t TXD2 = 17;

  // ---- Frame Konstanten ----
  const uint8_t FRAME_HEADER_1 = 0xF4;
  const uint8_t FRAME_HEADER_2 = 0xF3;
  const uint8_t FRAME_HEADER_3 = 0xF2;
  const uint8_t FRAME_HEADER_4 = 0xF1;

  const uint8_t FRAME_END_1 = 0xF8;
  const uint8_t FRAME_END_2 = 0xF7;
  const uint8_t FRAME_END_3 = 0xF6;
  const uint8_t FRAME_END_4 = 0xF5;

  const uint8_t DATA_TYPE_BASIC = 0x02;

  // ---- Server URLs ----
  const char* CONFIG_URL  = "https://clanic.ch/bm/app_reaction_game/get_infos.php";
  const char* TRIGGER_URL = "https://clanic.ch/bm/app_reaction_game/trigger.php";

  // ---- Frame Buffer ----
  uint8_t buffer[BUFFER_SIZE];
  bool inFrame = false;
  uint16_t bufIndex = 0;

  // ---- Glättung ----
  int filterValues[FILTER_SIZE];
  uint8_t filterIndex = 0;
  bool filterFilled = false;
  int lastSmoothed = -1;
  int lastDistance = -1;

  // ---- Timing ----
  unsigned long long lastEventTime = 0;
  unsigned long lastConfigCheck = 0;
  const unsigned long CONFIG_INTERVAL = 5000; // 5 Sekunden

  // ---- Status ----
  String espStatus = "idle"; // idle oder running

  // ===================== HILFSFUNKTIONEN =====================

  // ---- Range Logik ----
  RangeType getRange(int dist) {
    if (dist > 0 && dist < 120) return V;
    if (dist >= 220 && dist <= 260) return M;
    if (dist > 450) return H;
    return X; // undefiniert
  }

  const char* rangeToChar(RangeType r) {
    switch(r) {
      case V: return "V";
      case M: return "M";
      case H: return "H";
      default: return "X";
    }
  }

  // ---- LED ----
  void updateOkLed() {
    unsigned long interval = (espStatus == "running") ? 200 : 2000;
    unsigned long now = millis();

    if (now - lastLedToggle >= interval) {
      okLedState = !okLedState;
      digitalWrite(okLed, okLedState);
      lastLedToggle = now;
    }
  }

  // ---- Glättung ----
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

  // ---- WLAN ----
  void connectWLAN() {
    WiFi.begin(ssid, password);
    Serial.println("Verbinde mit WLAN...");

    bool blink = false;
    while (WiFi.status() != WL_CONNECTED) {
      delay(300);
      blink = !blink;
      digitalWrite(okLed, blink);
      Serial.print(".");
    }

    Serial.println("\nWLAN verbunden");
    Serial.println(WiFi.localIP());
    Serial.println(WiFi.macAddress());
    mac = WiFi.macAddress();

    Serial.println("Warte auf HTTP-Verfügbarkeit...");
    unsigned long start = millis();
    networkReady = false;

    while (!networkReady && millis() - start < 10000) {
      if (testHttpConnection()) networkReady = true;
      else delay(500);
    }

    if (networkReady) {
      Serial.println("Netzwerk READY");
      digitalWrite(okLed, HIGH);
    } else {
      Serial.println("HTTP nicht erreichbar!");
      digitalWrite(okLed, LOW);
    }
  }

  // ---- HTTP Test ----
  bool testHttpConnection() {
    HTTPClient http;
    http.setTimeout(2000);
    http.begin(String(CONFIG_URL) + "?mode=ping");
    int code = http.GET();
    http.end();

    if (code > 0) {
      Serial.println("HTTP Test OK");
      return true;
    } else {
      Serial.print("HTTP Test fehlgeschlagen: ");
      Serial.println(code);
      return false;
    }
  }

  // ---- Config Abfrage ----
  void checkConfig() {
    HTTPClient http;
    http.begin(String(CONFIG_URL) +
              "?mac=" + mac +
              "&user_id=" + userId +
              "&sequence=" + getSequenceAsString() +
              "&distance=" + String(lastDistance) +
              "&nextPos=" + sequenceIds[seqIndex]
    );

    http.addHeader("User-Agent", "ESP32");
    int code = http.GET();
    if (code > 0) {
      String payload = http.getString();
      Serial.print("Config JSON: "); Serial.println(payload);

      StaticJsonDocument<256> doc;
      DeserializationError err = deserializeJson(doc, payload);
      if (!err) {
        espStatus = doc["status"].as<String>();
        if (espStatus == "idle") {
          seqIndex = 0;
          lastRange = X;

          String seqStr = doc["sequence"].as<String>();
          String seqIdStr = doc["sequenceIds"].as<String>();
          userId = doc["userId"].as<int>();
          exerciseId = doc["exerciseId"].as<int>(); // neu

          Serial.print("Status: "); Serial.println(espStatus);
          Serial.print("UserId: "); Serial.println(userId);
          Serial.print("ExerciseId: "); Serial.println(exerciseId);
          Serial.print("Sequenz: "); Serial.println(seqStr);

          // Sequenz-Buchstaben parsen
          SEQ_LENGTH = 0;
          int start = 0;
          while (start < seqStr.length() && SEQ_LENGTH < MAX_SEQ) {
            int idx = seqStr.indexOf(',', start);
            String token = (idx == -1) ? seqStr.substring(start) : seqStr.substring(start, idx);
            start = (idx == -1) ? seqStr.length() : idx + 1;

            token.trim();
            if (token == "V") sequence[SEQ_LENGTH++] = V;
            else if (token == "M") sequence[SEQ_LENGTH++] = M;
            else if (token == "H") sequence[SEQ_LENGTH++] = H;
          }

          // sequenceIds parsen
          start = 0;
          uint8_t idIndex = 0;
          while (start < seqIdStr.length() && idIndex < MAX_SEQ) {
            int idx = seqIdStr.indexOf(',', start);
            String token = (idx == -1) ? seqIdStr.substring(start) : seqIdStr.substring(start, idx);
            start = (idx == -1) ? seqIdStr.length() : idx + 1;

            token.trim();
            if (token.length() > 0) sequenceIds[idIndex++] = token.toInt();
          }
        }
      } else {
        Serial.print("JSON Fehler: "); Serial.println(err.c_str());
      }

    } else {
      Serial.print("HTTP Fehler: "); Serial.println(code);
      connectWLAN();
    }

    http.end();
  }

  // ---- Event senden ----
  void sendEventToServer(double durationSeconds, RangeType range) {
    if (!networkReady) return;
    if (espStatus != "running") return;
    if (userId <= 0 || exerciseId <= 0) return; // neu: exerciseId

    if (WiFi.status() != WL_CONNECTED) connectWLAN();

    digitalWrite(hitLed, HIGH);

    String url =
      String(TRIGGER_URL) + "?pos=" + String(sequenceIds[seqIndex]) +
      "&duration=" + String(durationSeconds, 6) +
      "&userId=" + userId +
      "&exerciseId=" + exerciseId + // neu
      "&lastDistance=" + String(lastDistance);

    Serial.print("Sende: "); Serial.println(url);

    HTTPClient http;
    http.begin(url);
    http.addHeader("User-Agent", "ESP32");
    int code = http.GET();
    Serial.print("Antwort Code: "); Serial.println(code);
    if (code > 0) Serial.println(http.getString());
    delay(200);
    http.end();
    digitalWrite(hitLed, LOW);
  }

  // ---- Frame Parsing ----
  void parseFrame(uint8_t* frame, int length) {
    if (length < 12) return;

    int dataLen = frame[4] | (frame[5] << 8);
    if (dataLen + 10 > length) return;

    if (frame[6] != DATA_TYPE_BASIC) return;

    uint8_t* data = &frame[7];
    uint8_t targetStatus = data[1];
    if (targetStatus == 0x00) return;

    int movementDist   = data[2] | (data[3] << 8);
    int stationaryDist = data[5] | (data[6] << 8);

    int closest = movementDist > 0 ? movementDist : stationaryDist;
    int smoothed = smoothDistance(closest);
    lastDistance = smoothed;

    RangeType currentRange = getRange(smoothed);

    if(espStatus=="running") {
      Serial.print("Raw: "); Serial.print(closest);
      Serial.print(" cm | Smoothed: "); Serial.print(smoothed);
      Serial.print(" cm | Range: "); Serial.print(rangeToChar(currentRange));
      Serial.print(" | Last Range: "); Serial.print(rangeToChar(lastRange));
      Serial.print(" | Erwartet: "); Serial.println(rangeToChar(sequence[seqIndex]));
    }

    if (SEQ_LENGTH > 0 &&
        currentRange != X &&
        currentRange == sequence[seqIndex] &&
        currentRange != lastRange &&
        espStatus == "running") {

      unsigned long long now = micros();
      double duration = (lastEventTime == 0) ? 0 : (now - lastEventTime) / 1e6;

      Serial.println("=== EVENT ===");
      Serial.print("Distanz: "); Serial.println(smoothed);
      Serial.print("Dauer seit letztem: "); Serial.println(duration, 6);

      sendEventToServer(duration, currentRange);
      lastRange = currentRange;

      seqIndex++;
      if (seqIndex >= SEQ_LENGTH) {
        seqIndex = 0;
        Serial.println("=== SEQUENZ BEENDET ===");
      }

      lastEventTime = now;
    }
  }

  // ---- Hilfsfunktion: Sequenz als String ----
  String getSequenceAsString() {
    String result = "";
    for (int i = 0; i < SEQ_LENGTH; i++) {
      result += rangeToChar(sequence[i]);
      if (i < SEQ_LENGTH - 1) result += ",";
    }
    return result;
  }

  // ===================== SETUP =====================
  void setup() {
    Serial.begin(115200);

    pinMode(okLed, OUTPUT);
    pinMode(hitLed, OUTPUT);

    Serial2.begin(9600, SERIAL_8N1, RXD2, TXD2);

    connectWLAN();
  }

  // ===================== LOOP ======================
  void loop() {
    updateOkLed();

    if (millis() - lastConfigCheck > CONFIG_INTERVAL) {
      checkConfig();
      lastConfigCheck = millis();
    }

    while (Serial2.available()) {
      uint8_t b = Serial2.read();

      if (!inFrame) {
        if (b == FRAME_HEADER_1) {
          buffer[0] = b;
          bufIndex = 1;
          inFrame = true;
        }
      } else {
        buffer[bufIndex++] = b;

        if (bufIndex >= BUFFER_SIZE) {
          bufIndex = 0;
          inFrame = false;
        }

        if (bufIndex >= 4 &&
            buffer[bufIndex - 4] == FRAME_END_1 &&
            buffer[bufIndex - 3] == FRAME_END_2 &&
            buffer[bufIndex - 2] == FRAME_END_3 &&
            buffer[bufIndex - 1] == FRAME_END_4) {

          parseFrame(buffer, bufIndex);
          bufIndex = 0;
          inFrame = false;
        }
      }
    }
  }
