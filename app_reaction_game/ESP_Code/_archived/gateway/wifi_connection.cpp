#include <WiFi.h>
#include <HTTPClient.h>
#include <ArduinoJson.h>
#include "wifi_connection.h"
#include <Preferences.h>
#include "led_control.h"

static Preferences prefs;
String ssid;
String password;
String mac = "";
bool networkReady = false;

String CONFIG_URL;
String TRIGGER_URL;


unsigned long lastConfigCheck = 0;
const unsigned long CONFIG_INTERVAL = 2000;

String configParams = "?dummy=0";
String triggerParams = "?dummy=0";
String lastConfigParams = "";

void initWLAN() {
  Serial.println("Lade Konfiguration...");
  // Preferences starten
  prefs.begin("config", true); // read-only für Initialisierung

  // Werte laden (Defaults falls leer)
  ssid = prefs.getString("wifi_ssid", "Claude");
  password = prefs.getString("wifi_pw", "0792318193");
  CONFIG_URL = prefs.getString("config_url", "https://clanic.ch/app_reaction_game/get_infos.php");
  TRIGGER_URL = prefs.getString("trigger_url", "https://clanic.ch/app_reaction_game/trigger.php");

  prefs.end(); // fertig
}

void connectWLAN() {
  WiFi.begin(ssid, password);
  Serial.print("SSID: ");
  Serial.print(ssid);
  Serial.print(" / PW: ");
  Serial.print(password);
  Serial.println(" verbinde mit WLAN...");

  
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
    if (testHttpConnection()) {
      networkReady = true;
    } else {
      Serial.print(".");
      delay(500);
    }
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
void checkServer() {
  if (millis() - lastConfigCheck > CONFIG_INTERVAL) {
    lastConfigCheck = millis();

    if (!networkReady || WiFi.status() != WL_CONNECTED) {
      setLedState(ok, LED_OFF);
      connectWLAN();
    }
    if (!networkReady || WiFi.status() != WL_CONNECTED) {
      Serial.println("Netzwerk nicht bereit für Config Abfrage");
      return;
    };

    setLedState(ok, LED_ON);
    Serial.print("Config-Params: ");
    Serial.println(configParams);
    String url = String(CONFIG_URL) + configParams + "&mac=" + mac;
    Serial.println("Abfrage URL: " + url);

    HTTPClient http;
    http.begin(url);
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
      setLedState(ok, LED_OFF);
      connectWLAN();
    }

    http.end();
  }
}

// ---- Event senden ----
void sendEventToServer() {
  if (!networkReady || WiFi.status() != WL_CONNECTED) connectWLAN();
  if (!networkReady || WiFi.status() != WL_CONNECTED) {
    Serial.println("Netzwerk nicht bereit für Event Senden");
    return;
  };

  String url = String(TRIGGER_URL) + triggerParams + "&mac=" + mac;
  Serial.print("Sende: "); Serial.println(url);

  HTTPClient http;
  http.begin(url);
  http.addHeader("User-Agent", "ESP32");
  int code = http.GET();
  Serial.print("Antwort Code: "); Serial.println(code);
  if (code > 0) Serial.println(http.getString());
  http.end();
}
