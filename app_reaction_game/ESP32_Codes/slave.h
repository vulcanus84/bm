#include <WiFi.h>
#include <esp_now.h>

// --------------------- LEDs -----------------------
const int hitLed = 26;

// --------------------- Radar UART -----------------
#define RXD2 16
#define TXD2 17

#define FRAME_HEADER_1 0xF4
#define FRAME_HEADER_2 0xF3
#define FRAME_HEADER_3 0xF2
#define FRAME_HEADER_4 0xF1

#define FRAME_END_1 0xF8
#define FRAME_END_2 0xF7
#define FRAME_END_3 0xF6
#define FRAME_END_4 0xF5

#define DATA_TYPE_BASIC 0x02

const int BUFFER_SIZE = 128;
uint8_t buffer[BUFFER_SIZE];
bool inFrame = false;
int bufIndex = 0;

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

// --------------------- ESP-NOW --------------------
uint8_t masterAddress[] = {0x94, 0xA9, 0x90, 0x6D, 0xF6, 0xB8};

typedef struct {
  uint8_t mac[6];       // <<< MAC als ID
  uint32_t timestamp;
  int16_t distance;
} CubePacket;

CubePacket packet;

// ------------------ ESP-NOW Callbacks -------------
void OnDataRecv(const esp_now_recv_info_t *, const uint8_t *, int) {
  digitalWrite(hitLed, HIGH);
  delay(100);
  digitalWrite(hitLed, LOW);
}

void OnDataSent(const wifi_tx_info_t *, esp_now_send_status_t status) {
  Serial.print("ESP-NOW Send: ");
  Serial.println(status == ESP_NOW_SEND_SUCCESS ? "OK" : "FAIL");
}

// ------------------ Frame Auswertung ---------------
void parseFrame(uint8_t* frame, int length) {
  if (length < 12) return;
  if (frame[6] != DATA_TYPE_BASIC) return;

  uint8_t* data = &frame[7];
  uint8_t targetStatus = data[1];

  int movementDist   = data[2] | (data[3] << 8);
  int stationaryDist = data[5] | (data[6] << 8);

  int finalDistance = 0;

  if (targetStatus == 0x00) {
    resetFilter();
    finalDistance = 0;
  } else {
    int closest = movementDist > 0 ? movementDist : stationaryDist;
    finalDistance = smoothDistance(closest);
  }

  packet.timestamp = millis();
  packet.distance  = finalDistance;

  esp_now_send(masterAddress, (uint8_t*)&packet, sizeof(packet));
}

// ------------------ Setup --------------------------
void setup() {
  Serial.begin(115200);
  pinMode(hitLed, OUTPUT);

  Serial2.begin(115200, SERIAL_8N1, RXD2, TXD2);

  WiFi.mode(WIFI_STA);
  esp_now_init();

  // 👉 Eigene MAC lesen & speichern
  esp_read_mac(packet.mac, ESP_MAC_WIFI_STA);

  Serial.print("Slave MAC: ");
  for (int i = 0; i < 6; i++) {
    Serial.printf("%02X", packet.mac[i]);
    if (i < 5) Serial.print(":");
  }
  Serial.println();

  esp_now_register_send_cb(OnDataSent);
  esp_now_register_recv_cb(OnDataRecv);

  esp_now_peer_info_t peer = {};
  memcpy(peer.peer_addr, masterAddress, 6);
  peer.channel = 1;
  peer.encrypt = false;
  esp_now_add_peer(&peer);
}

// ------------------ Loop ---------------------------
void loop() {
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
