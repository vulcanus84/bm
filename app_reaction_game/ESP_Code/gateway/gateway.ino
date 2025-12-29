  #include <WiFi.h>
  #include <HTTPClient.h>
  #include <ArduinoJson.h>
  // ===================== DEFINITIONEN =====================

  // ---- WLAN ----
  const char* ssid = "Claude";
  const char* password = "0792318193";
  String mac = "";
  bool networkReady = false;

  // ---- Kommunikation mit ESP-NOW Handler über UART ----
  const uint8_t RXD2 = 22;
  const uint8_t TXD2 = 23;
  
  // ---- Server URLs ----
  //const char* CONFIG_URL  = "https://clanic.ch/app_reaction_game/get_infos.php";
  //const char* TRIGGER_URL = "https://clanic.ch/app_reaction_game/trigger.php";

  const char* CONFIG_URL  = "http://192.168.1.133:8888/bm/app_reaction_game/get_infos.php";
  const char* TRIGGER_URL = "http://192.168.1.133:8888/bm/app_reaction_game/trigger.php";

    // ---- Timing ----
  unsigned long lastConfigCheck = 0;
  const unsigned long CONFIG_INTERVAL = 2000; // 5 Sekunden

  // ---- Status ----
  String configParams = "?dummy=0";
  String triggerParams = "";
  String lastConfigParams = "";
  
  // ---- WLAN ----
  void connectWLAN() {
    WiFi.begin(ssid, password);
    Serial.println("Verbinde mit WLAN...");

    while (WiFi.status() != WL_CONNECTED) {
      Serial.print(".");
      delay(500);
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
      Serial.println("Netzwerk OK");
    } else {
      Serial.print(CONFIG_URL);
      Serial.println("?mode=ping nicht erreichbar!");
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
    if (!networkReady || WiFi.status() != WL_CONNECTED) connectWLAN();
    if (!networkReady || WiFi.status() != WL_CONNECTED) {
      Serial.println("Netzwerk nicht bereit für Config Abfrage");
      return;
    };

    HTTPClient http;
    Serial.print("Config-Params: ");
    Serial.println(configParams);
    Serial.print("Abfrage URL: "); Serial.println(String(CONFIG_URL) + 
              configParams + "&mac=" + mac
    );
    http.begin(String(CONFIG_URL) + configParams + "&mac=" + mac
    );

    http.addHeader("User-Agent", "ESP32");
    int code = http.GET();
    if (code > 0) {
      String payload = http.getString();
      if(lastConfigParams != payload) {
        Serial.println("Neue Konfiguration erhalten");
        Serial.print("Config JSON: "); Serial.println(payload);
        Serial1.println(payload);
        lastConfigParams = payload;
      } else {
        Serial.println("Keine neue Konfiguration");
      }

    } else {
      Serial.print("HTTP Fehler: "); Serial.println(code);
      connectWLAN();
    }

    http.end();
  }

  // ---- Event senden ----
  void sendEventToServer() {
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
    Serial1.begin(9600, SERIAL_8N1, RXD2, TXD2);
    connectWLAN();
  }

  // ===================== LOOP ======================
  void loop() {
    if (millis() - lastConfigCheck > CONFIG_INTERVAL) {
      checkConfig();
      lastConfigCheck = millis();
    }
  
    if (Serial1.available()) {
      String msg = Serial1.readStringUntil('\n');
      Serial.println("Received: " + msg);
      //Get Trigger command
      if (msg.startsWith("EVENT:")) {
        triggerParams = msg.substring(6);
        Serial.print("Trigger Params gesetzt: "); Serial.println(triggerParams);
        sendEventToServer();
        return;
      }

      //Get config params
      if (msg.startsWith("CONFIG:")) {
        configParams = msg.substring(7);
        configParams.replace("\r", "");
        configParams.replace("\n", "");
        configParams.trim();
        Serial.print("Neue Config Params: "); Serial.println(configParams);
      }
      
    } 
  }
