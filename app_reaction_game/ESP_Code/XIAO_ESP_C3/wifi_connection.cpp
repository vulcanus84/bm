#include <WiFi.h>
#include <HTTPClient.h>
#include <ArduinoJson.h>
#include <Preferences.h>

#include "wifi_connection.h"
#include "led_control.h"
#include "config_ap.h"
#include "game_control.h"

// ===============================
// Globale Konfiguration
// ===============================

static Preferences prefs;

volatile WifiState wifiState = WIFI_INIT;
QueueHandle_t wifiQueue;

String ssid;
String password;
String mac = "";

String CONFIG_URL;
String TRIGGER_URL;

LedCommand wifi_ledCmd;
WifiCommand wifi_wifiCmd;
GcCommand wifi_gcCmd;

String lastConfigParams  = "";
bool HTTPRequestInProgress = false;

unsigned long lastConfigCheck = 0;
unsigned long tryToConnectSince = 0;
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
// Event senden (nicht blockierend)
// ===============================

void sendEventToServer(String triggerParams) {

  if (wifiState != SERVER_OK) {
    Serial.println("Event senden abgebrochen – Netzwerk nicht bereit");
    return;
  }
  HTTPRequestInProgress = true;
  String url = String(TRIGGER_URL) + triggerParams + "&mac=" + mac;
  Serial.print("Sende Event: ");
  Serial.println(url);

  HTTPClient http;
  http.setTimeout(2000);
  http.begin(url);
  http.addHeader("User-Agent", "ESP32");

  int code = http.GET();
  Serial.print("Antwort Code: ");
  Serial.print(code);
  Serial.print(" / ");

  if (code > 0) {
    Serial.println(http.getString());
  }

  http.end();
  HTTPRequestInProgress = false;
}

String getConfigFromServer(String configParams) {

  if (wifiState != SERVER_OK) {
    Serial.println("Config abrufen abgebrochen – Netzwerk nicht bereit");
    return "";
  }

  if (HTTPRequestInProgress) {
    Serial.println("Config abrufen abgebrochen – HTTP Anfrage läuft bereits");
    return "";
  }

  String url = String(CONFIG_URL) + configParams + "&mac=" + mac;
  // Serial.print("Hole Config: ");
  // Serial.println(url);

  HTTPClient http;
  http.setTimeout(2000);
  http.begin(url);
  http.addHeader("User-Agent", "ESP32");

  int code = http.GET();
  // Serial.print("Antwort Code: ");
  // Serial.println(code);

  if (code > 0) {
    String payload = http.getString();
    http.end();
    return payload;
  }
  http.end();
  return "FEHLER" + String(code);
}

// ===============================
// WLAN TASK
// ===============================

void taskWifi(void *pvParameters) {

  initWLAN();
  
  wifiState = WIFI_CONNECTING;
  WiFi.begin(ssid, password);
  Serial.println("Verbinde mit WLAN...");

  wifi_ledCmd.type = SHOW1;
  xQueueSend(ledQueue, &wifi_ledCmd, 0);
  tryToConnectSince = millis();

  // Task-Schleife
  for (;;) {

    // WLAN-Kommandos verarbeiten (z.B. Events senden)
    if (xQueueReceive(wifiQueue, &wifi_wifiCmd, 0)) {
      switch (wifi_wifiCmd.type) {
        case WIFI_SEND_EVENT:
          sendEventToServer(wifi_wifiCmd.payload);
          break;
        case WIFI_GET_CONFIG:
          Serial.print("Config anfordern mit Parametern: ");
          Serial.println(wifi_wifiCmd.payload);
          String config = getConfigFromServer(wifi_wifiCmd.payload);
          if (config != "") {
            wifi_gcCmd.type = GC_READ_CONFIG;
            wifi_gcCmd.payload = config;
            xQueueSend(gcQueue, &wifi_gcCmd, 0);
          }
          break;
      }
    }

    if (millis() - tryToConnectSince > 20000 && wifiState != AP_MODE && wifiState != SERVER_OK) {
      Serial.println("Keine Verbindung, gehe in AP-Modus...");
      configAP_begin();
      wifiState = AP_MODE;
      wifi_ledCmd.type = SHOW_AP_MODE;
      xQueueSend(ledQueue, &wifi_ledCmd, 0);
    }
    switch (wifiState) {

      case AP_MODE:
        // Im AP-Modus bleibt das Gerät erreichbar, aber es wird nicht versucht, eine WLAN-Verbindung herzustellen
        configAP_loop(); // AP-Modus verwalten
        break;

      // ----------------------------
      case WIFI_CONNECTING:
        if (WiFi.status() == WL_CONNECTED) {
          Serial.println("WLAN verbunden");
          Serial.print("IP: "); Serial.println(WiFi.localIP());
          mac = WiFi.macAddress();
          Serial.print("MAC: "); Serial.println(mac);
          wifiState = SERVER_CHECKING;
          wifi_ledCmd.type = SHOW2;
          xQueueSend(ledQueue, &wifi_ledCmd, 0);
        }
        break;

      // ----------------------------
      case SERVER_CHECKING:
        Serial.println("Teste Server...");
        if (testHttpConnection()) {
          wifiState = SERVER_OK;
          wifi_ledCmd.type = CLEAR;
          xQueueSend(ledQueue, &wifi_ledCmd, 0);
          Serial.println("Server erreichbar");
        } else {
          wifiState = SERVER_ERROR;
          Serial.println("Server NICHT erreichbar");
        }
        break;

      // ----------------------------
      case SERVER_OK:
        // WLAN verloren?
        if (WiFi.status() != WL_CONNECTED) {
          Serial.println("WLAN verloren, reconnect...");
          wifiState = WIFI_CONNECTING;
          WiFi.disconnect();
          WiFi.begin(ssid, password);
        }
        break;

      // ----------------------------
      case SERVER_ERROR:
        Serial.println("Server Fehlerzustand");

        wifiState = WIFI_CONNECTING;
        WiFi.disconnect();
        WiFi.begin(ssid, password);
        break;

      default:
        wifiState = WIFI_CONNECTING;
        break;
    }

    // Niemals blockieren
    vTaskDelay(pdMS_TO_TICKS(100));
  }
}
