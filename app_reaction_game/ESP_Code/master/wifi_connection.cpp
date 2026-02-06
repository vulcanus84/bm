#include <WiFi.h>
#include <HTTPClient.h>
#include <ArduinoJson.h>
#include <Preferences.h>

#include "wifi_connection.h"
#include "led_control.h"

// ===============================
// Globale Konfiguration
// ===============================

static Preferences prefs;

volatile WifiState wifiState = WIFI_INIT;

String ssid;
String password;
String mac = "";

String CONFIG_URL;
String TRIGGER_URL;

String lastConfigParams  = "";

unsigned long lastConfigCheck = 0;
const unsigned long CONFIG_INTERVAL = 2000;

// ===============================
// WLAN Konfiguration laden
// ===============================

void initWLAN() {
  Serial.println("Lade Konfiguration...");

  prefs.begin("config", true); // read-only

  ssid        = prefs.getString("wifi_ssid", "Claude");
  password    = prefs.getString("wifi_pw", "0792318193");
  CONFIG_URL  = prefs.getString("config_url", "https://clanic.ch/app_reaction_game/get_infos.php");
  TRIGGER_URL = prefs.getString("trigger_url", "https://clanic.ch/app_reaction_game/trigger.php");

  prefs.end();

  Serial.print("SSID: "); Serial.println(ssid);
  Serial.print("CONFIG_URL: "); Serial.println(CONFIG_URL);
  Serial.print("TRIGGER_URL: "); Serial.println(TRIGGER_URL);
}

// ===============================
// HTTP Ping Test
// ===============================

bool testHttpConnection() {
  HTTPClient http;
  http.setTimeout(2000);

  String url = String(CONFIG_URL) + "?mode=ping";
  Serial.print("HTTP Test: "); Serial.println(url);

  http.begin(url);
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

// ===============================
// WLAN TASK
// ===============================

void taskWifi(void *pvParameters) {

  initWLAN();
  LedCommand cmd;
  
  wifiState = WIFI_CONNECTING;
  WiFi.begin(ssid, password);
  Serial.println("Verbinde mit WLAN...");

  cmd.type = SHOW1;
  xQueueSend(ledQueue, &cmd, 0);

  // Task-Schleife
  for (;;) {

    switch (wifiState) {

      // ----------------------------
      case WIFI_CONNECTING:
        if (WiFi.status() == WL_CONNECTED) {
          Serial.println("WLAN verbunden");
          Serial.print("IP: "); Serial.println(WiFi.localIP());
          mac = WiFi.macAddress();
          Serial.print("MAC: "); Serial.println(mac);
          wifiState = SERVER_CHECKING;
          cmd.type = SHOW2;
          xQueueSend(ledQueue, &cmd, 0);
        }
        break;

      // ----------------------------
      case SERVER_CHECKING:
        Serial.println("Teste Server...");
        if (testHttpConnection()) {
          wifiState = SERVER_OK;
          Serial.println("Server erreichbar");
        } else {
          wifiState = SERVER_ERROR;
          Serial.println("Server NICHT erreichbar");
        }
        break;

      // ----------------------------
      case SERVER_OK:
        cmd.type = SHOW3;
        xQueueSend(ledQueue, &cmd, 0);

        // WLAN verloren?
        if (WiFi.status() != WL_CONNECTED) {
          Serial.println("WLAN verloren, reconnect...");
          wifiState = WIFI_CONNECTING;
          WiFi.disconnect();
          WiFi.begin(ssid, password);
          break;
        }

      // ----------------------------
      case SERVER_ERROR:
        Serial.println("Server Fehlerzustand");

        if (WiFi.status() != WL_CONNECTED) {
          Serial.println("WLAN weg → Neuverbinden");
          wifiState = WIFI_CONNECTING;
          WiFi.disconnect();
          WiFi.begin(ssid, password);
        } else {
          Serial.println("Neuer Server-Test folgt");
          wifiState = SERVER_CHECKING;
        }
        break;

      default:
        wifiState = WIFI_CONNECTING;
        break;
    }

    // Niemals blockieren
    vTaskDelay(pdMS_TO_TICKS(200));
  }
}

// ===============================
// Event senden (nicht blockierend)
// ===============================

void sendEventToServer(String triggerParams) {

  if (wifiState != SERVER_OK) {
    Serial.println("Event senden abgebrochen – Netzwerk nicht bereit");
    return;
  }

  String url = String(TRIGGER_URL) + triggerParams + "&mac=" + mac;
  Serial.print("Sende Event: ");
  Serial.println(url);

  HTTPClient http;
  http.setTimeout(2000);
  http.begin(url);
  http.addHeader("User-Agent", "ESP32");

  int code = http.GET();
  Serial.print("Antwort Code: ");
  Serial.println(code);

  if (code > 0) {
    Serial.println(http.getString());
  }

  http.end();
}

String getConfigFromServer() {

  if (wifiState != SERVER_OK) {
    Serial.println("Config abrufen abgebrochen – Netzwerk nicht bereit");
    return;
  }

  String url = String(CONFIG_URL) + configParams + "&mac=" + mac;
  Serial.print("Hole Config: ");
  Serial.println(url);

  HTTPClient http;
  http.setTimeout(2000);
  http.begin(url);
  http.addHeader("User-Agent", "ESP32");

  int code = http.GET();
  Serial.print("Antwort Code: ");
  Serial.println(code);

  if (code > 0) {
    String payload = http.getString();
    http.end();
    return payload;
  }
  http.end();
  return "FEHLER" + String(code);
}