#include <WiFi.h>
#include <HTTPClient.h>
#include <ArduinoJson.h>
#include <esp_now.h>

#define MAX_SENSORS 1
#define MAX_SEQ 10
#define EVENT_QUEUE_SIZE 16
#define CONFIG_INTERVAL 5000

const char* ssid = "Claude";
const char* password = "0792318193";

uint8_t sensorMacs[MAX_SENSORS][6] = {
  {0x38,0x18,0x2B,0x69,0xE2,0xA8}
};

// ===================== SERVER =====================
//const char* CONFIG_URL  = "https://clanic.ch/app_reaction_game/get_infos.php";
//const char* TRIGGER_URL = "https://clanic.ch/app_reaction_game/trigger.php";

const char* CONFIG_URL  = "http://192.168.1.133:8888/bm/app_reaction_game/get_infos.php";
const char* TRIGGER_URL = "http://192.168.1.133:8888/bm/app_reaction_game/trigger.php";

// ===================== ESP-NOW STRUKTUREN =====================
typedef struct {
  uint8_t sensorId;
  char status;               // 'i' | 'r'
  uint8_t seqLength;
  char sequence[MAX_SEQ];
  uint8_t sequenceIds[MAX_SEQ];
  int userId;
  int exerciseId;
} config_msg_t;

typedef struct {
  uint8_t sensorId;
  uint8_t seqPos;
  char range;
  float duration;
  int lastDistance;
} event_msg_t;

// ===================== GLOBAL =====================
String mac;
bool networkReady = false;
String espStatus = "idle";

int userId = 0;
int exerciseId = 0;

unsigned long lastConfigCheck = 0;

// ===================== EVENT QUEUE =====================
event_msg_t eventQueue[EVENT_QUEUE_SIZE];
uint8_t qHead = 0;
uint8_t qTail = 0;
uint8_t qCount = 0;

// ===================== WLAN =====================
void connectWLAN() {
  WiFi.mode(WIFI_STA);
  WiFi.begin(ssid, password);
  Serial.print("Verbinde WLAN");

  while (WiFi.status() != WL_CONNECTED) {
    delay(300);
    Serial.print(".");
  }

  Serial.println("\nWLAN verbunden");
  Serial.println(WiFi.localIP());
  mac = WiFi.macAddress();
  Serial.println(mac);
}

void maintainWLAN() {
  static unsigned long lastCheck = 0;
  if (millis() - lastCheck < 2000) return;
  lastCheck = millis();

  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("WLAN weg → Reconnect");
    WiFi.disconnect();
    WiFi.reconnect();
    networkReady = false;
  }
}

// ===================== HTTP TEST =====================
bool testHttpConnection() {
  HTTPClient http;
  http.setTimeout(2000);
  http.begin(String(CONFIG_URL) + "?mode=ping");
  int code = http.GET();
  http.end();
  return code > 0;
}

void ensureNetworkReady() {
  if (WiFi.status() != WL_CONNECTED) return;

  if (!networkReady && testHttpConnection()) {
    networkReady = true;
    Serial.println("Netzwerk READY");
  }
}

// ===================== CONFIG =====================
bool checkConfig(config_msg_t &cfg) {
  HTTPClient http;

  String url = String(CONFIG_URL) +
               "?mac=" + mac +
               "&user_id=" + userId;

  http.begin(url);
  http.addHeader("User-Agent", "ESP32");

  int code = http.GET();
  if (code <= 0) {
    http.end();
    return false;
  }

  StaticJsonDocument<256> doc;
  if (deserializeJson(doc, http.getString())) {
    http.end();
    return false;
  }
  http.end();

  espStatus  = doc["status"].as<String>();
  userId     = doc["userId"].as<int>();
  exerciseId = doc["exerciseId"].as<int>();

  String seqStr   = doc["sequence"].as<String>();
  String idStr    = doc["sequenceIds"].as<String>();

  cfg.sensorId   = 0xFF;
  cfg.status     = (espStatus == "running") ? 'r' : 'i';
  cfg.userId     = userId;
  cfg.exerciseId = exerciseId;

  cfg.seqLength = 0;
  int s = 0;
  while (s < seqStr.length() && cfg.seqLength < MAX_SEQ) {
    int i = seqStr.indexOf(',', s);
    String t = (i == -1) ? seqStr.substring(s) : seqStr.substring(s, i);
    t.trim();
    cfg.sequence[cfg.seqLength++] = t[0];
    s = (i == -1) ? seqStr.length() : i + 1;
  }

  s = 0;
  for (int i = 0; i < cfg.seqLength; i++) {
    int idx = idStr.indexOf(',', s);
    String t = (idx == -1) ? idStr.substring(s) : idStr.substring(s, idx);
    t.trim();
    cfg.sequenceIds[i] = t.toInt();
    s = (idx == -1) ? idStr.length() : idx + 1;
  }

  return true;
}

// ===================== QUEUE =====================
void enqueueEvent(const event_msg_t &ev) {
  if (qCount >= EVENT_QUEUE_SIZE) {
    Serial.println("Event Queue voll – verwerfe");
    return;
  }
  eventQueue[qTail] = ev;
  qTail = (qTail + 1) % EVENT_QUEUE_SIZE;
  qCount++;
}

bool dequeueEvent(event_msg_t &ev) {
  if (qCount == 0) return false;
  ev = eventQueue[qHead];
  qHead = (qHead + 1) % EVENT_QUEUE_SIZE;
  qCount--;
  return true;
}

// ===================== EVENT → SERVER =====================
void processEventQueue() {
  if (!networkReady || espStatus != "running") return;

  event_msg_t ev;
  if (!dequeueEvent(ev)) return;

  String url = String(TRIGGER_URL) +
               "?pos=" + ev.seqPos +
               "&duration=" + String(ev.duration, 6) +
               "&userId=" + userId +
               "&exerciseId=" + exerciseId +
               "&lastDistance=" + ev.lastDistance;

  HTTPClient http;
  http.begin(url);
  http.addHeader("User-Agent", "ESP32");
  int code = http.GET();
  http.end();

  Serial.printf("Event Sensor %d → HTTP %d\n", ev.sensorId, code);
}

// ===================== ESP-NOW RX =====================
void onEventRecv(const esp_now_recv_info_t *info, const uint8_t *data, int len) {
    if (len != sizeof(event_msg_t)) return;

    event_msg_t ev;
    memcpy(&ev, data, sizeof(ev));

    enqueueEvent(ev);
}


// ===================== SETUP =====================
void setup() {
  Serial.begin(115200);

  connectWLAN();

  esp_now_init();
  esp_now_register_recv_cb(onEventRecv);

  for (int i = 0; i < MAX_SENSORS; i++) {
    esp_now_peer_info_t peer{};
    memcpy(peer.peer_addr, sensorMacs[i], 6);
    peer.channel = 0;
    peer.encrypt = false;
    esp_now_add_peer(&peer);
  }

  Serial.println("MASTER READY");
}

// ===================== LOOP =====================
void loop() {
  maintainWLAN();
  ensureNetworkReady();

  if (millis() - lastConfigCheck > CONFIG_INTERVAL && networkReady) {
    config_msg_t cfg;
    if (checkConfig(cfg)) {
      for (int i = 0; i < MAX_SENSORS; i++) {
        esp_now_send(sensorMacs[i], (uint8_t*)&cfg, sizeof(cfg));
      }
    }
    lastConfigCheck = millis();
  }

  processEventQueue();
}
