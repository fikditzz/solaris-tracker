<?php



$url = "http://solaris.bengkelit.id/api/tracker/log";

echo "=================================================\n";
echo " Simulator ESP32 - Helios Tracker Pro (Multi-Panel)\n";
echo "=================================================\n";
echo "Mulai mengirim data random ke $url...\n";
echo "Tekan Ctrl+C untuk berhenti.\n\n";

$panel1 = [
    "azimuth" => 180.0,
    "elevation" => 45.0,
    "currentMode" => "Automatic"
];

$panel2 = [
    "azimuth" => 180.0, 
    "elevation" => 45.0,
    "currentMode" => "Automatic"
];

function sendData($panelId, &$state, $url) {
    if ($state['currentMode'] === "Automatic") {
        if ($panelId === 'panel1') {
            $state['azimuth'] += (rand(-20, 20) / 10);
            $state['elevation'] += (rand(-20, 20) / 10);
        } else if ($panelId === 'panel2') {
            $state['elevation'] += (rand(-20, 20) / 10);
        }
    }
    
    if ($state['azimuth'] > 360) $state['azimuth'] = 360;
    if ($state['azimuth'] < 0) $state['azimuth'] = 0;
    if ($state['elevation'] > 180) $state['elevation'] = 180;
    if ($state['elevation'] < 0) $state['elevation'] = 0;

    if ($panelId === 'panel1') {
        $data = [
            "panel_id" => "panel1",
            "irradiance" => rand(500, 1000) + (rand(0, 99) / 100),
            "power_output" => rand(3000, 5000) + (rand(0, 99) / 100),
            "voltage" => rand(300, 400) + (rand(0, 99) / 100),
            "current" => rand(10, 15) + (rand(0, 99) / 100),
            "azimuth" => $state['azimuth'],
            "elevation" => $state['elevation'],
            "ldr_nw" => rand(800, 950),
            "ldr_ne" => rand(800, 950),
            "ldr_sw" => rand(800, 950),
            "ldr_se" => rand(800, 950),
            "motor1_load" => rand(1, 3) + (rand(0, 99) / 100),
            "motor2_load" => rand(1, 3) + (rand(0, 99) / 100),
            "tracking_mode" => $state['currentMode'],
            "weather_status" => "Clear Sky"
        ];
    } else {
        $data = [
            "panel_id" => "panel2",
            "irradiance" => rand(500, 1000) + (rand(0, 99) / 100),
            "power_output" => rand(2000, 4000) + (rand(0, 99) / 100),
            "voltage" => rand(300, 400) + (rand(0, 99) / 100),
            "current" => rand(8, 12) + (rand(0, 99) / 100),
            "azimuth" => $state['azimuth'],
            "elevation" => $state['elevation'],
            "ldr_nw" => rand(800, 950), 
            "ldr_ne" => rand(800, 950), 
            "ldr_sw" => rand(800, 950), 
            "ldr_se" => rand(800, 950), 
            "motor1_load" => 0, 
            "motor2_load" => rand(1, 3) + (rand(0, 99) / 100),
            "tracking_mode" => $state['currentMode'],
            "weather_status" => "Clear Sky"
        ];
    }

    $payload = json_encode($data);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($payload)
    ]);

    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpcode == 201 || $httpcode == 200) {
        $prefix = ($panelId == 'panel1') ? "[Alpha - Dual]" : "[Beta  - Sngl]";
        echo "[" . date('H:i:s') . "] $prefix SUCCESS | Mode: {$state['currentMode']} | Azimuth: " . number_format($state['azimuth'], 1) . " | Elevation: " . number_format($state['elevation'], 1) . "\n";
        
        $json = json_decode($response, true);
        if (isset($json['mode'])) {
            if ($state['currentMode'] !== $json['mode']) {
                echo " >>> $panelId MODE BERUBAH MENJADI: " . strtoupper($json['mode']) . " <<<\n";
            }
            $state['currentMode'] = $json['mode'];
        }
        
        if (isset($json['command']) && $json['command']) {
            $cmd = $json['command'];
            echo " >>> $panelId PERINTAH MANUAL DITERIMA: $cmd <<<\n";
            
            if ($cmd == 'Up') $state['elevation'] += 10;
            if ($cmd == 'Down') $state['elevation'] -= 10;
            if ($panelId == 'panel1') {
                if ($cmd == 'Left') $state['azimuth'] -= 10;
                if ($cmd == 'Right') $state['azimuth'] += 10;
            }
            if ($cmd == 'Reset') {
                if ($panelId == 'panel1') $state['azimuth'] = 180.0;
                $state['elevation'] = 45.0;
            }
        }
        return true;
    } else {
        if ($httpcode == 0) {
            echo "[" . date('H:i:s') . "] OFFLINE | Server Laravel belum aktif.\n";
            sleep(5);
            return false;
        } else {
            echo "[" . date('H:i:s') . "] $panelId FAILED  | Kode: $httpcode | Response: $response\n";
            return true;
        }
    }
}

while (true) {
    $success = sendData('panel1', $panel1, $url);
    if (!$success) continue;
    usleep(500000); 
    sendData('panel2', $panel2, $url);
    sleep(2); 
}
