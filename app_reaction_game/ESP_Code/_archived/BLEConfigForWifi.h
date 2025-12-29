#include <BLEDevice.h>
#include <BLEServer.h>
#include <BLEUtils.h>
#include <BLE2902.h>
#include <Preferences.h>
#include <WiFi.h>

BLECharacteristic *cfgChar;
bool deviceConnected = false;
Preferences prefs;

#define SERVICE_UUID        "12345678-1234-1234-1234-1234567890ab"
#define CHARACTERISTIC_UUID "abcd1234-5678-90ab-cdef-1234567890ab"

// Variablen für Werte
String ssid, password;
int sensorID;
float distanceV, distanceM, distanceH;

// Alte SSID/PW merken
String oldSSID = "";
String oldPassword = "";

// WLAN-Connect asynchron
bool wifiPending = false;

// Letzte Werte für Notify
String lastNotified = "";

// --- WLAN Connect im Loop ---
void loopConnectWiFi() {
  if (!wifiPending) return;
  wifiPending = false;

  if (ssid.length() == 0) return;

  // Nur verbinden, wenn SSID/PW geändert
  if (ssid == oldSSID && password == oldPassword) return;

  oldSSID = ssid;
  oldPassword = password;

  Serial.print("🌐 WLAN verbinden: "); Serial.println(ssid);

  // Alte Verbindung trennen
  WiFi.disconnect(true);
  delay(500);

  WiFi.begin(ssid.c_str(), password.c_str());

  int retries = 0;
  while (WiFi.status() != WL_CONNECTED && retries < 20) {
    delay(500);
    Serial.print(".");
    retries++;
  }

  if (WiFi.status() == WL_CONNECTED) {
    Serial.println("\n✅ WLAN verbunden!");
    Serial.println(WiFi.localIP());
  } else {
    Serial.println("\n❌ WLAN Verbindung fehlgeschlagen");
  }
}

// --- Werte per Notify senden, nur bei Änderung ---
void sendCurrentValues() {
  if (!deviceConnected) return;

  String msg = ssid + "," + password + "," + String(sensorID) + "," +
               String(distanceV) + "," + String(distanceM) + "," + String(distanceH);

  if (msg != lastNotified) {
    cfgChar->setValue(msg.c_str());
    cfgChar->notify();
    lastNotified = msg;
    Serial.println("📤 Werte an Client gesendet: " + msg);
  }
}

// --- BLE Characteristic Callback ---
class MyCharCallbacks : public BLECharacteristicCallbacks {
  void onWrite(BLECharacteristic *pChar) {
    String input = pChar->getValue().c_str();
    Serial.print("Empfangen: "); Serial.println(input);

    int start = 0;
    int index = 0;
    String parts[6];

    for (int i = 0; i <= input.length(); i++) {
      if (i == input.length() || input[i] == ',') {
        if (index < 6) {
          parts[index++] = input.substring(start, i);
        }
        start = i + 1;
      }
    }

    prefs.begin("config", false);
    if (index >= 1 && parts[0].length() > 0) { ssid = parts[0]; prefs.putString("ssid", ssid); }
    if (index >= 2 && parts[1].length() > 0) { password = parts[1]; prefs.putString("pw", password); }
    if (index >= 3 && parts[2].length() > 0) { sensorID = parts[2].toInt(); prefs.putInt("sensorID", sensorID); }
    if (index >= 4 && parts[3].length() > 0) { distanceV = parts[3].toFloat(); prefs.putFloat("distanceV", distanceV); }
    if (index >= 5 && parts[4].length() > 0) { distanceM = parts[4].toFloat(); prefs.putFloat("distanceM", distanceM); }
    if (index >= 6 && parts[5].length() > 0) { distanceH = parts[5].toFloat(); prefs.putFloat("distanceH", distanceH); }
    prefs.end();

    Serial.println("✅ Aktuelle Werte:");
    Serial.println(ssid);
    Serial.println(password);
    Serial.println(sensorID);
    Serial.println(distanceV);
    Serial.println(distanceM);
    Serial.println(distanceH);

    // WLAN Connect Flag setzen
    wifiPending = true;

    // Werte direkt an Client senden (Notify nur wenn sich geändert)
    sendCurrentValues();
  }
};

// --- BLE Server Callback ---
class MyServerCallbacks: public BLEServerCallbacks {
  void onConnect(BLEServer* pServer) {
    deviceConnected = true;
    Serial.println("Client verbunden");

    // Werte sofort an neuen Client senden
    sendCurrentValues();
  }
  void onDisconnect(BLEServer* pServer) {
    deviceConnected = false;
    Serial.println("Client getrennt");
    pServer->getAdvertising()->start(); // wieder sichtbar
  }
};

// --- Setup ---
void setup() {
  Serial.begin(115200);

  // Werte aus Preferences laden
  prefs.begin("config", true);
  ssid = prefs.getString("ssid", "");
  password = prefs.getString("pw", "");
  sensorID = prefs.getInt("sensorID", 0);
  distanceV = prefs.getFloat("distanceV", 0);
  distanceM = prefs.getFloat("distanceM", 0);
  distanceH = prefs.getFloat("distanceH", 0);
  prefs.end();

  oldSSID = "";
  oldPassword = "";
  lastNotified = "";

  Serial.println("BLE Server startet...");
  Serial.println("Aktuelle Werte:");
  Serial.println(ssid);
  Serial.println(password);
  Serial.println(sensorID);
  Serial.println(distanceV);
  Serial.println(distanceM);
  Serial.println(distanceH);

  // --- BLE Setup ---
  BLEDevice::init("ESP32_Config");
  BLEServer *pServer = BLEDevice::createServer();
  pServer->setCallbacks(new MyServerCallbacks());

  BLEService *pService = pServer->createService(SERVICE_UUID);

  cfgChar = pService->createCharacteristic(
    CHARACTERISTIC_UUID,
    BLECharacteristic::PROPERTY_WRITE | BLECharacteristic::PROPERTY_NOTIFY
  );

  cfgChar->addDescriptor(new BLE2902());
  cfgChar->setCallbacks(new MyCharCallbacks());
  cfgChar->setValue("Bereit");

  pService->start();
  pServer->getAdvertising()->start();

  Serial.println("BLE Server läuft und wartet auf Werte...");

  // WLAN Connect Flag setzen für Start
  wifiPending = true;
}

// --- Loop ---
void loop() {
  // WLAN Connect asynchron
  loopConnectWiFi();

  // Notify an Client nur bei Änderung
  if (deviceConnected) {
    sendCurrentValues();
  }

  delay(100); // kleine Pause, damit ESP nicht blockiert
}
