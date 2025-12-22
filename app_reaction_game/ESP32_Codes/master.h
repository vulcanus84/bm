#include <WiFi.h>
#include <esp_now.h>

// --------------------- Triggerbereich -------------------
const int TRIGGER_MIN = 50;  // minimaler Abstand für ACK
const int TRIGGER_MAX = 150; // maximaler Abstand für ACK

// --------------------- Slave-Daten -----------------------
typedef struct {
    uint8_t mac[6];
    uint32_t timestamp;
    int16_t distance;
} CubePacket;

// Master MAC (wird automatisch aus Serial ausgegeben)
uint8_t masterMac[6];

// --------------------- ESP-NOW Callback ------------------
void OnDataRecv(const esp_now_recv_info_t *info, const uint8_t *incomingData, int len) {
    if (len != sizeof(CubePacket)) return;

    CubePacket packet;
    memcpy(&packet, incomingData, sizeof(packet));

    Serial.print("Slave ");
    printMac(packet.mac);

    Serial.print(" - Distanz: ");
    Serial.print(packet.distance);

    Serial.print(" mm, Timestamp: ");
    Serial.println(packet.timestamp);

    // Prüfen Triggerbereich
    if (packet.distance >= TRIGGER_MIN && packet.distance <= TRIGGER_MAX) {
        // Peer hinzufügen, falls nicht vorhanden
        esp_now_peer_info_t peer = {};
        memcpy(peer.peer_addr, info->src_addr, 6);
        peer.channel = 1;
        peer.ifidx = WIFI_IF_STA;
        peer.encrypt = false;
        esp_err_t status = esp_now_add_peer(&peer);
        if (status != ESP_OK && status != ESP_ERR_ESPNOW_EXIST) {
            Serial.println("Fehler beim Hinzufügen des Peers");
            return;
        }

        // ACK an Slave senden
        uint8_t ackPayload[1] = {0x01};
        esp_now_send(info->src_addr, ackPayload, sizeof(ackPayload));
    }
}

// --------------------- Setup -----------------------------
void setup() {
    Serial.begin(115200);
    WiFi.mode(WIFI_STA);

    Serial.print("Master MAC: ");
    Serial.println(WiFi.macAddress());

    if (esp_now_init() != ESP_OK) {
        Serial.println("ESP-NOW init failed!");
        return;
    }

    esp_now_register_recv_cb(OnDataRecv);

    // Optional: Peer hinzufügen (Slave MACs können später dynamisch sein)
    // Bei bekannten Slaves:
    // uint8_t slaveMac[] = { ... };
    // esp_now_peer_info_t peer = {};
    // memcpy(peer.peer_addr, slaveMac, 6);
    // peer.channel = 1;
    // peer.ifidx = WIFI_IF_STA;
    // peer.encrypt = false;
    // esp_now_add_peer(&peer);
}

// --------------------- Loop ------------------------------
void loop() {
    // Hier nur Status-Ausgabe oder weitere Verarbeitung
    // Die ESP-NOW-Callback verarbeitet alles
}

void printMac(const uint8_t* mac) {
  for (int i = 0; i < 6; i++) {
    if (mac[i] < 0x10) Serial.print("0");
    Serial.print(mac[i], HEX);
    if (i < 5) Serial.print(":");
  }
}
