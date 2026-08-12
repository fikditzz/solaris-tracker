#include <WiFi.h>
#include <HTTPClient.h>
#include <ArduinoJson.h>

// ============================================
//   KONFIGURASI - Ubah sesuai kebutuhan Anda
// ============================================
const char* WIFI_SSID      = "LAPTOP-T5OF2UUV";
const char* WIFI_PASSWORD  = "aso12345678";
const char* API_URL        = "http://solaris.bengkelit.id/api/tracker/log";

const unsigned long SEND_INTERVAL = 3000; // Kirim data setiap 3 detik

// ============================================
//   STRUKTUR DATA PANEL
// ============================================
struct PanelData {
  String panelId;
  float azimuth;
  float elevation;
  float voltage;
  float current;
  float power;
  float irradiance;
  int ldr_nw;
  int ldr_ne;
  int ldr_sw;
  int ldr_se;
  float motor1_load;
  float motor2_load;
};

// Inisialisasi Objek Panel
PanelData panel1 = {"panel1", 180.0, 45.0, 350.0, 12.5, 4375.0, 800.0, 880, 900, 870, 890, 2.0, 1.8};
PanelData panel2 = {"panel2", 180.0, 45.0, 350.0, 12.5, 4375.0, 800.0, 880, 900, 870, 890, 2.0, 1.8};

// State Aplikasi
String currentMode = "Automatic"; // Bisa "Automatic" atau "Manual"
unsigned long lastSendTime = 0;

// ============================================
//   FUNGSI: Koneksi WiFi
// ============================================
void connectWiFi() {
  if (WiFi.status() == WL_CONNECTED) return;

  Serial.print("\nMenghubungkan ke WiFi: ");
  Serial.println(WIFI_SSID);

  WiFi.begin(WIFI_SSID, WIFI_PASSWORD);

  int attempts = 0;
  while (WiFi.status() != WL_CONNECTED && attempts < 30) {
    delay(500);
    Serial.print(".");
    attempts++;
  }

  if (WiFi.status() == WL_CONNECTED) {
    Serial.println();
    Serial.println("========================================");
    Serial.println("  WiFi TERHUBUNG!");
    Serial.print("  IP Address : ");
    Serial.println(WiFi.localIP());
    Serial.print("  Signal (RSSI): ");
    Serial.print(WiFi.RSSI());
    Serial.println(" dBm");
    Serial.println("========================================");
  } else {
    Serial.println();
    Serial.println("GAGAL terhubung ke WiFi! Restart ESP32...");
    delay(3000);
    ESP.restart();
  }
}

// ============================================
//   FUNGSI: Simulasi Data Panel
// ============================================
void simulatePanel(PanelData &panel) {
  if (currentMode == "Automatic") {
    panel.azimuth   += (random(-20, 21) / 10.0);
    panel.elevation += (random(-20, 21) / 10.0);
  }
  // Di mode Manual, azimuth dan elevation dikontrol via Serial/API, tidak di-random

  // Batasi nilai
  if (panel.azimuth > 360) panel.azimuth = 360;
  if (panel.azimuth < 0)   panel.azimuth = 0;
  if (panel.elevation > 180) panel.elevation = 180;
  if (panel.elevation < 0)   panel.elevation = 0;

  // Simulasi nilai sensor lainnya tetap berjalan di kedua mode
  panel.voltage    = 300.0 + (random(0, 10000) / 100.0);
  panel.current    = 10.0  + (random(0, 500) / 100.0);
  panel.power      = panel.voltage * panel.current;
  panel.irradiance = random(500, 1000) + (random(0, 99) / 100.0);

  panel.ldr_nw = random(800, 950);
  panel.ldr_ne = random(800, 950);
  panel.ldr_sw = random(800, 950);
  panel.ldr_se = random(800, 950);

  panel.motor1_load = 1.0 + (random(0, 200) / 100.0);
  panel.motor2_load = 1.0 + (random(0, 200) / 100.0);
}

// ============================================
//   FUNGSI: Eksekusi Perintah Manual (Dari API)
// ============================================
void executeManualCommand(PanelData &panel, const char* cmd) {
  if (strcmp(cmd, "Up") == 0)    panel.elevation += 10;
  else if (strcmp(cmd, "Down") == 0)  panel.elevation -= 10;
  else if (strcmp(cmd, "Left") == 0)  panel.azimuth -= 10;
  else if (strcmp(cmd, "Right") == 0) panel.azimuth += 10;
  else if (strcmp(cmd, "Reset") == 0) { panel.azimuth = 180; panel.elevation = 45; }
  
  // Pastikan nilai tetap dalam batas setelah command manual dari API
  if (panel.azimuth > 360) panel.azimuth = 360;
  if (panel.azimuth < 0)   panel.azimuth = 0;
  if (panel.elevation > 180) panel.elevation = 180;
  if (panel.elevation < 0)   panel.elevation = 0;
}

// ============================================
//   FUNGSI: Proses Respons dari Server
// ============================================
void receiveCommand(PanelData &panel, String response) {
  JsonDocument resDoc;
  DeserializationError error = deserializeJson(resDoc, response);
  
  if (!error) {
    if (resDoc.containsKey("mode")) {
      Serial.print("  Mode dari server ("); Serial.print(panel.panelId); Serial.print("): ");
      Serial.println(resDoc["mode"].as<const char*>());
    }
    
    if (resDoc.containsKey("command") && !resDoc["command"].isNull()) {
      const char* cmd = resDoc["command"];
      Serial.print("  Perintah manual dari server ("); Serial.print(panel.panelId); Serial.print("): ");
      Serial.println(cmd);
      executeManualCommand(panel, cmd);
    }
  } else {
    Serial.println("  Gagal parsing JSON dari server.");
  }
}

// ============================================
//   FUNGSI: Kirim Data Panel ke API VPS
// ============================================
void sendPanel(PanelData &panel) {
  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("WiFi terputus! Tidak bisa mengirim data.");
    return;
  }

  HTTPClient http;
  http.begin(API_URL);
  http.addHeader("Content-Type", "application/json");
  http.setTimeout(5000);

  // Buat JSON payload
  JsonDocument doc;
  doc["panel_id"]       = panel.panelId;
  doc["azimuth"]        = round(panel.azimuth * 10) / 10.0;
  doc["elevation"]      = round(panel.elevation * 10) / 10.0;
  doc["voltage"]        = round(panel.voltage * 100) / 100.0;
  doc["current"]        = round(panel.current * 100) / 100.0;
  doc["power_output"]   = round(panel.power * 100) / 100.0;
  doc["irradiance"]     = panel.irradiance;
  doc["ldr_nw"]         = panel.ldr_nw;
  doc["ldr_ne"]         = panel.ldr_ne;
  doc["ldr_sw"]         = panel.ldr_sw;
  doc["ldr_se"]         = panel.ldr_se;
  doc["motor1_load"]    = panel.motor1_load;
  doc["motor2_load"]    = panel.motor2_load;
  doc["tracking_mode"]  = currentMode;
  doc["weather_status"] = "Clear Sky";

  String jsonPayload;
  serializeJson(doc, jsonPayload);

  Serial.println("-----------------------------------");
  Serial.print("Mengirim data "); Serial.print(panel.panelId); Serial.println(" ke API...");

  int httpCode = http.POST(jsonPayload);

  if (httpCode > 0) {
    String response = http.getString();

    Serial.print("HTTP Code : ");
    Serial.println(httpCode);
    
    if (httpCode == 200 || httpCode == 201) {
      Serial.println("SUCCESS | Data berhasil dikirim!");
      receiveCommand(panel, response); // Proses respons server
    } else {
      Serial.print("GAGAL | Server merespons kode: ");
      Serial.println(httpCode);
      Serial.print("Response  : ");
      Serial.println(response);
    }
  } else {
    Serial.print("GAGAL | Error koneksi: ");
    Serial.println(http.errorToString(httpCode));
  }

  http.end();
}

// ============================================
//   FUNGSI: Tampilkan Status Panel ke Serial
// ============================================
void printPanelStatus(PanelData &panel) {
  Serial.println("==========================");
  if (panel.panelId == "panel1") {
    Serial.println("PANEL 1");
  } else if (panel.panelId == "panel2") {
    Serial.println("PANEL 2");
  } else {
    Serial.println(panel.panelId);
  }
  Serial.println("==========================");
  
  Serial.print("Azimuth   : "); Serial.println(panel.azimuth, 1);
  Serial.print("Elevation : "); Serial.println(panel.elevation, 1);
  Serial.print("Voltage   : "); Serial.print(panel.voltage, 1); Serial.println(" V");
  Serial.print("Current   : "); Serial.print(panel.current, 1); Serial.println(" A");
  Serial.print("Power     : "); Serial.print(panel.power, 1); Serial.println(" W");
  Serial.println();
}

// ============================================
//   FUNGSI: Baca Perintah dari Serial Monitor
// ============================================
void readSerialManual() {
  if (Serial.available() > 0) {
    String input = Serial.readStringUntil('\n');
    input.trim();
    
    if (input.length() == 0) return;

    input.toLowerCase(); // Permudah pengecekan

    if (input == "auto" || input == "automatic") {
      currentMode = "Automatic";
      Serial.println(">>> Mode diubah ke: AUTOMATIC");
    } 
    else if (input == "manual") {
      currentMode = "Manual";
      Serial.println(">>> Mode diubah ke: MANUAL");
    }
    else if (currentMode == "Manual") {
      // Format manual: "panel1 180 45" atau "panel2 270 60"
      if (input.startsWith("panel1") || input.startsWith("panel2")) {
        int firstSpace = input.indexOf(' ');
        int secondSpace = input.indexOf(' ', firstSpace + 1);

        if (firstSpace > 0 && secondSpace > 0) {
          String pId = input.substring(0, firstSpace);
          float newAzimuth = input.substring(firstSpace + 1, secondSpace).toFloat();
          float newElevation = input.substring(secondSpace + 1).toFloat();

          if (pId == "panel1") {
            panel1.azimuth = newAzimuth;
            panel1.elevation = newElevation;
            Serial.println(">>> Manual update Panel 1 berhasil!");
          } else if (pId == "panel2") {
            panel2.azimuth = newAzimuth;
            panel2.elevation = newElevation;
            Serial.println(">>> Manual update Panel 2 berhasil!");
          }
        } else {
          Serial.println(">>> Format salah! Gunakan: panel1 <azimuth> <elevation>");
        }
      } else {
        Serial.println(">>> Perintah tidak dikenal atau format salah.");
      }
    } 
    else {
      Serial.println(">>> Silakan ubah ke mode 'manual' terlebih dahulu untuk mengatur posisi.");
    }
  }
}

// ============================================
//   SETUP (Dijalankan 1x saat ESP32 nyala)
// ============================================
void setup() {
  Serial.begin(115200);
  delay(1000);

  Serial.println();
  Serial.println("========================================");
  Serial.println("  Helios Tracker Pro - ESP32 Client");
  Serial.println("  Versi: 2.0 (Dual Panel & Modular)");
  Serial.println("========================================");

  connectWiFi();

  Serial.println("Memulai pengiriman data ke API...");
  Serial.print("Interval: setiap ");
  Serial.print(SEND_INTERVAL / 1000);
  Serial.println(" detik");
  Serial.print("Mode Awal: ");
  Serial.println(currentMode);
  Serial.println("Ketik 'manual' atau 'auto' untuk mengganti mode.");
}

// ============================================
//   LOOP (Dijalankan berulang-ulang)
// ============================================
void loop() {
  // 1. Cek koneksi WiFi
  if (WiFi.status() != WL_CONNECTED) {
    connectWiFi();
  }

  // 2. Baca perintah dari Serial Monitor
  readSerialManual();

  // 3. Proses pengiriman berdasarkan interval
  unsigned long currentTime = millis();
  if (currentTime - lastSendTime >= SEND_INTERVAL) {
    lastSendTime = currentTime;

    // Update data panel (Simulasi atau tetap untuk manual)
    simulatePanel(panel1);
    simulatePanel(panel2);

    // Kirim data panel 1
    sendPanel(panel1);
    
    // Kirim data panel 2
    sendPanel(panel2);

    // Tampilkan status di Serial Monitor
    Serial.println("\n--- STATUS UPDATE ---");
    printPanelStatus(panel1);
    printPanelStatus(panel2);
  }
}
