#include "config_ap.h"

#include <WiFi.h>
#include <WebServer.h>
#include <Preferences.h>
#include <DNSServer.h>

static DNSServer dnsServer;

// ================== Konfig ==================
static const char* AP_SSID = "gateway_config";
static const char* AP_PASS = "9876543210";

// ================== Objekte ==================
static WebServer server(80);
static Preferences prefs;

// ================== HTML ==================
static const char PAGE_HTML[] PROGMEM = R"rawliteral(
<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Cube Config</title>
<style>
body { font-family: Arial; background:#f2f2f2; }
.box {
  max-width: 480px;
  margin: 30px auto;
  background: white;
  padding: 20px;
  border-radius: 8px;
}
h3 { margin-top: 20px; }
input, button {
  width: 100%;
  padding: 10px;
  margin-top: 8px;
}
button {
  background: #007AFF;
  color: white;
  border: none;
  border-radius: 5px;
}
.success {
  background: #d4edda;
  color: #155724;
  padding: 10px;
  margin-bottom: 15px;
  border-radius: 5px;
}
.row {
  display: flex;
  gap: 10px;
}
</style>
</head>
<body>
<div class="box">
  %STATUS%

  <form method="POST" action="/save">

    <h3>WLAN</h3>
    <input name="wifi_ssid" placeholder="SSID" value="%WIFI_SSID%">
    <input name="wifi_pw" type="password" placeholder="Passwort" value="%WIFI_PW%">

    <h3>Web</h3>
    <input name="trigger_url" placeholder="Trigger URL" value="%TRIGGER%">
    <input name="config_url" placeholder="Config URL" value="%CONFIG%">

    <button type="submit">Speichern</button>
  </form>
</div>
</body>
</html>
)rawliteral";

// ================== Helfer ==================

String getPref(const char* key, const char* def = "") {
  return prefs.getString(key, def);
}

void setPref(const char* key, const String& val) {
  prefs.putString(key, val);
}

// ================== Render ==================

String renderPage(bool saved) {
  String html = PAGE_HTML;

  html.replace("%STATUS%",
    saved ? "<div class='success'>Konfiguration gespeichert. Gateway wird neu gestartet...</div>" : "");

  html.replace("%WIFI_SSID%", getPref("wifi_ssid"));
  html.replace("%WIFI_PW%", getPref("wifi_pw"));
  html.replace("%TRIGGER%", getPref("trigger_url"));
  html.replace("%CONFIG%", getPref("config_url"));

  return html;
}

// ================== Handler ==================

void handleRoot(bool saved = false) {
  prefs.begin("config", true);
  String page = renderPage(saved);
  prefs.end();
  server.send(200, "text/html", page);
}

void handleSave() {
  prefs.begin("config", false);

  setPref("wifi_ssid", server.arg("wifi_ssid"));
  setPref("wifi_pw", server.arg("wifi_pw"));
  setPref("trigger_url", server.arg("trigger_url"));
  setPref("config_url", server.arg("config_url"));

  prefs.end();

  handleRoot(true);   // gleiche Seite + Erfolgsmeldung
  delay(1000);
  ESP.restart();
}

// ================== Public API ==================

void configAP_begin() {
  WiFi.mode(WIFI_AP);
  WiFi.softAP(AP_SSID, AP_PASS);

  IPAddress ip = WiFi.softAPIP();
  Serial.print("AP IP: ");
  Serial.println(ip);

  // 🔑 Captive Portal DNS (ALLE Domains → ESP)
  dnsServer.start(53, "*", ip);

  server.on("/", HTTP_GET, []() { handleRoot(false); });
  server.on("/save", HTTP_POST, handleSave);

  // Fallback für alle HTTP-Pfade
  server.onNotFound([]() { handleRoot(false); });

  server.begin();
}

void configAP_loop() {
  dnsServer.processNextRequest();
  server.handleClient();
}
