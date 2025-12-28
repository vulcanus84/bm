  #include <WiFi.h>
  #include <HTTPClient.h>
  #include <ArduinoJson.h>  // Bibliothek für JSON
  // ===================== DEFINITIONEN =====================

  // ---- WLAN ----
  const char* ssid = "Claude";
  const char* password = "0792318193";
  String mac = "";
  bool networkReady = false;

  // ---- Kommunikation mit ESP-NOW Handler über UART ----
  const uint8_t RXD2 = 16;
  const uint8_t TXD2 = 17;
  
  // ---- Server URLs ----
  //const char* CONFIG_URL  = "https://clanic.ch/app_reaction_game/get_infos.php";
  //const char* TRIGGER_URL = "https://clanic.ch/app_reaction_game/trigger.php";

  const char* CONFIG_URL  = "http://192.168.1.133:8888/bm/app_reaction_game/get_infos.php";
  const char* TRIGGER_URL = "http://192.168.1.133:8888/bm/app_reaction_game/trigger.php";

    // ---- Timing ----
  int64_t lastEventTime = 0;
  unsigned long lastConfigCheck = 0;
  const unsigned long CONFIG_INTERVAL = 5000; // 5 Sekunden

  // ---- Status ----
  String espStatus = "idle"; // idle oder running
  String configParams = "";
  String triggerParams = "";
  
  // ---- WLAN ----
  void connectWLAN() {
    WiFi.begin(ssid, password);
    Serial.println("Verbinde mit WLAN...");

    while (WiFi.status() != WL_CONNECTED) {
      Serial.print(".");
    }

    Serial.println("\nWLAN verbunden");
    Serial.println(WiFi.localIP());
    Serial.println(WiFi.macAddress());
    mac = WiFi.macAddress();

    Serial.println("Verbindung zum Server...");
    unsigned long start = millis();
    networkReady = false;

    while (!networkReady && millis() - start < 10000) {
      if (testHttpConnection()) networkReady = true;
      else delay(500);
    }

    if (networkReady) {
      Serial.println("Netzwerk READY");
    } else {
      Serial.println("HTTP nicht erreichbar!");
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
      return true;
    } else {
      Serial.print("HTTP Test fehlgeschlagen: ");
      Serial.println(code);
      return false;
    }
  }

  // ---- Config Abfrage ----
  void checkConfig() {
    if (!networkReady || WiFi.status() != WL_CONNECTED) connectWLAN();
    if (!networkReady || WiFi.status() != WL_CONNECTED) {
      Serial.println("Netzwerk nicht bereit für Config Abfrage");
      return;
    };

    HTTPClient http;
    http.begin(String(CONFIG_URL) +
              "?mac=" + mac + configParams
    );

    http.addHeader("User-Agent", "ESP32");
    int code = http.GET();
    if (code > 0) {
      String payload = http.getString();
      Serial.print("Config JSON: "); Serial.println(payload);
      //Send to ESP Handler

    } else {
      Serial.print("HTTP Fehler: "); Serial.println(code);
      connectWLAN();
    }

    http.end();
  }

  // ---- Event senden ----
  void sendEventToServer() {
    if (espStatus != "running") return;
    if (!networkReady || WiFi.status() != WL_CONNECTED) connectWLAN();
    if (!networkReady || WiFi.status() != WL_CONNECTED) {
      Serial.println("Netzwerk nicht bereit für Event Senden");
      return;
    };

    String url = String(TRIGGER_URL) + triggerParams;
    Serial.print("Sende: "); Serial.println(url);

    HTTPClient http;
    http.begin(url);
    http.addHeader("User-Agent", "ESP32");
    int code = http.GET();
    Serial.print("Antwort Code: "); Serial.println(code);
    if (code > 0) Serial.println(http.getString());
    http.end();
  }

  // ===================== SETUP =====================
  void setup() {
    Serial.begin(115200);
    Serial2.begin(9600, SERIAL_8N1, RXD2, TXD2);
    connectWLAN();
  }

  // ===================== LOOP ======================
  void loop() {
    if (millis() - lastConfigCheck > CONFIG_INTERVAL) {
      checkConfig();
      lastConfigCheck = millis();
    }
  
    if (Serial2.available()) {
      String msg = Serial2.readStringUntil('\n');
      Serial.println("Received: " + msg);
      //Get Trigger command
      triggerParams = msg;
      sendEventToServer();

      //Get config params
      configParams = msg;
    } 
  }
