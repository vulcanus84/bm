#pragma once // Verhindert mehrfaches Einbinden
#include <Arduino.h>

// ESP Packet Strukturen
enum PacketType : uint8_t {
  PKT_HEARTBEAT = 1,
  PKT_DISTANCE_TO_MASTER = 2,
  PKT_GAMEMSG_TO_SENSOR = 3
};

struct __attribute__((packed)) PacketHeader {
  PacketType type;   // Was ist das für ein Paket?
  uint8_t sensorId;  // Wer sendet?
};

struct __attribute__((packed)) DistancePacket {
  PacketHeader header;
  int32_t distance;   // explizite Größe
};

struct __attribute__((packed)) HeartbeatPacket {
  PacketHeader header;
  uint8_t ok;         // 0 = false, 1 = true
};

enum GameState : uint8_t {
  IDLE = 0,
  RUNNING = 1
};

struct __attribute__((packed))  GameMsg {
  PacketHeader header;
  GameState state; // Dauerzustand
  uint8_t hit;        // Einmal-Event
};