import paho.mqtt.client as mqtt
import json, os, sys, urllib.request, ssl
from pathlib import Path

def load_env_file(path):
    """Muat KEY=VALUE dari .env; variabel environment yang sudah ada tidak ditimpa."""
    if not path.exists():
        return
    for line in path.read_text(encoding="utf-8").splitlines():
        line = line.strip()
        if not line or line.startswith("#") or "=" not in line:
            continue
        key, value = line.split("=", 1)
        os.environ.setdefault(key.strip(), value.strip().strip('"').strip("'"))

load_env_file(Path(__file__).resolve().parent / ".env")

missing = [k for k in ("MQTT_BROKER", "MQTT_USER", "MQTT_PASS") if not os.environ.get(k)]
if missing:
    sys.exit(f"Konfigurasi belum lengkap, isi di .env: {', '.join(missing)}")

# Konfigurasi HiveMQ
MQTT_BROKER = os.environ["MQTT_BROKER"]
MQTT_PORT = int(os.environ.get("MQTT_PORT") or 8883)
MQTT_USER = os.environ["MQTT_USER"]
MQTT_PASS = os.environ["MQTT_PASS"]
TOPIC_1AXIS = "solartracker/1axis/data"
TOPIC_2AXIS = "solartracker/2axis/data"

API_URL = "http://127.0.0.1:8000/api/tracker/log"

def post_to_laravel(data):
    try:
        req = urllib.request.Request(API_URL, data=json.dumps(data).encode('utf-8'), headers={'Content-Type': 'application/json'}, method='POST')
        urllib.request.urlopen(req)
        print(f"Data masuk ke Database: {data['panel_id']}")
    except Exception as e:
        print(f"Gagal simpan data: {e}")

def on_connect(client, userdata, flags, rc):
    print("Berhasil terhubung ke HiveMQ Cloud!")
    client.subscribe(TOPIC_1AXIS)
    client.subscribe(TOPIC_2AXIS)

def on_message(client, userdata, msg):
    try:
        payload = json.loads(msg.payload.decode("utf-8"))
        api_data = {}
        
        # Firmware 1 sumbu mengirim 'firmware_version', firmware 2 sumbu mengirim 'firmware'.
        firmware = payload.get('firmware_version') or payload.get('firmware')

        v = float(payload.get('voltage', 0))
        i = float(payload.get('current', 0))
        p = float(payload.get('power', v * i))
        
        if msg.topic == TOPIC_2AXIS:
            api_data = {
                'panel_id': 'panel1',
                'azimuth': payload.get('azimuth', 0),
                'elevation': payload.get('elevation', 0),
                'voltage': v,
                'current': i,
                'power_output': p,
                'ldr_nw': payload.get('lightN', 0), 
                'ldr_ne': payload.get('lightE', 0),
                'ldr_sw': payload.get('lightW', 0),
                'ldr_se': payload.get('lightS', 0),
                'tracking_mode': "Automatic" if payload.get('mode') == "AUTO" else "Manual",
                'firmware_version': firmware
            }
        elif msg.topic == TOPIC_1AXIS:
            api_data = {
                'panel_id': 'panel2',
                'azimuth': payload.get('target_angle', 0),
                'elevation': payload.get('current_angle', 0),
                'voltage': v,
                'current': i,
                'power_output': p,
                'tracking_mode': "Automatic" if payload.get('mode') == "AUTO" else "Manual",
                'firmware_version': firmware
            }
            
        post_to_laravel(api_data)
        
    except Exception as e:
        print(f"Error parsing data: {e}")

client = mqtt.Client()
client.username_pw_set(MQTT_USER, MQTT_PASS)
client.tls_set(tls_version=ssl.PROTOCOL_TLS)
client.on_connect = on_connect
client.on_message = on_message

print("Menyambungkan ke MQTT...")
client.connect(MQTT_BROKER, MQTT_PORT, 60)
client.loop_forever()
