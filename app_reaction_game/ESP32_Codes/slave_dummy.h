#include <WiFi.h>
#include <esp_now.h>

// --------------------- LEDs -----------------------
const int hitLed = 26;

// --------------------- ESP-NOW --------------------
uint8_t masterAddress[] = {0x94, 0xA9, 0x90, 0x6D, 0xF6, 0xB8};

uint8_t deviceMac[6];

typedef struct {
  uint8_t mac[6];
  uint32_t timestamp;
  int16_t distance;   // geglättete Distanz
} CubePacket;

CubePacket packet;

// --------------------- Glättung -------------------
const int FILTER_SIZE = 5;
int filterValues[FILTER_SIZE];
int filterIndex = 0;
bool filterFilled = false;

void resetFilter() {
  filterIndex = 0;
  filterFilled = false;
  memset(filterValues, 0, sizeof(filterValues));
}

int smoothDistance(int newValue) {
  if (newValue <= 0 || newValue > 3000) return 0;

  filterValues[filterIndex] = newValue;
  filterIndex = (filterIndex + 1) % FILTER_SIZE;

  if (filterIndex == 0) filterFilled = true;

  int count = filterFilled ? FILTER_SIZE : filterIndex;
  long sum = 0;
  for (int i = 0; i < count; i++) sum += filterValues[i];

  return sum / count;
}

// ------------------ ESP-NOW Callbacks -------------
void OnDataRecv(const esp_now_recv_info_t *, const uint8_t *, int) {
  // ACK vom Master → LED blinkt
  digitalWrite(hitLed, HIGH);
  delay(100);
  digitalWrite(hitLed, LOW);
}

void OnDataSent(const wifi_tx_info_t *, esp_now_send_status_t status) {
  Serial.print("ESP-NOW Send: ");
  Serial.println(status == ESP_NOW_SEND_SUCCESS ? "OK" : "FAIL");
}

// ------------------ Setup --------------------------
void setup() {
  Serial.begin(115200);
  pinMode(hitLed, OUTPUT);

  WiFi.mode(WIFI_STA);
  delay(100);

  sscanf(WiFi.macAddress().c_str(), "%hhx:%hhx:%hhx:%hhx:%hhx:%hhx",
        &deviceMac[0], &deviceMac[1], &deviceMac[2],
        &deviceMac[3], &deviceMac[4], &deviceMac[5]);

  Serial.print("Slave MAC: ");
  Serial.println(WiFi.macAddress());

  esp_now_init();
  esp_now_register_send_cb(OnDataSent);
  esp_now_register_recv_cb(OnDataRecv);

  esp_now_peer_info_t peer = {};
  memcpy(peer.peer_addr, masterAddress, 6);
  peer.channel = 1;
  peer.encrypt = false;
  esp_now_add_peer(&peer);

  memcpy(packet.mac, deviceMac, 6);
}

// ------------------ Loop ---------------------------
void loop() {
  // Zufallswert generieren (50–2000 mm)
  int rawValue = random(50, 2000);
  int smoothed = smoothDistance(rawValue);

  packet.timestamp = millis();
  packet.distance  = smoothed;

  esp_now_send(masterAddress, (uint8_t*)&packet, sizeof(packet));

  Serial.print("Sent distance: ");
  Serial.println(smoothed);

  delay(200); // Sendeintervall
}
