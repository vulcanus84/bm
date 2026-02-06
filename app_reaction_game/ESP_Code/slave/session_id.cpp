#include "session_id.h"
#include <Preferences.h>

static Preferences prefs;
static bool initialized = false;

String generateSessionId() {
    if (!initialized) {
        prefs.begin("session", false);
        initialized = true;
    }

    // Persistenter Counter
    uint32_t counter = prefs.getUInt("counter", 0);
    counter++;
    prefs.putUInt("counter", counter);

    // Device-ID (hardware-eindeutig)
    uint64_t deviceId = ESP.getEfuseMac();

    // Schöne, serverfreundliche ID
    char buf[32];
    snprintf(buf, sizeof(buf),
             "%012llX-%06lu",
             deviceId,
             counter);

    return String(buf);
}
