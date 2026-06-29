#include <NimBLEDevice.h>

#define SERVICE_UUID        "12345678-1234-1234-1234-1234567890ab"
#define CHAR_UUID_NOTIFY    "12345678-1234-1234-1234-1234567890ac"

NimBLECharacteristic *notifyCharacteristic;

int counter = 0;

class ServerCallbacks : public NimBLEServerCallbacks {

    void onConnect(NimBLEServer* pServer, NimBLEConnInfo& connInfo) {
        Serial.println("🔗 Client connected");
    }

    void onDisconnect(NimBLEServer* pServer, NimBLEConnInfo& connInfo, int reason) {
        Serial.println("❌ Client disconnected");

        NimBLEDevice::getAdvertising()->start();
        Serial.println("📡 Advertising restarted");
    }
};

void setup() {

    Serial.begin(115200);

    NimBLEDevice::init("MoveBox Orange");

    NimBLEServer *server = NimBLEDevice::createServer();
    server->setCallbacks(new ServerCallbacks(),false);
    NimBLEService *service = server->createService(SERVICE_UUID);

    // 🔵 CHARACTERISTIC (DAS FEHLTE!)
    notifyCharacteristic = service->createCharacteristic(
        CHAR_UUID_NOTIFY,
        NIMBLE_PROPERTY::NOTIFY
    );

    service->start();

    // 🔵 ADVERTISING
    NimBLEAdvertising *advertising = NimBLEDevice::getAdvertising();
    advertising->addServiceUUID(SERVICE_UUID);
    advertising->setName("MoveBox Orange");

    advertising->start();

    Serial.println("Advertising started");
}

void loop() {

    counter++;

    String msg = "Counter: " + String(counter);

    notifyCharacteristic->setValue(msg.c_str());
    notifyCharacteristic->notify();

    Serial.println(msg);

    delay(2000);
}