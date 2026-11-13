import paho.mqtt.client as mqtt
import json, math, os, re, sys, urllib.error, urllib.request, ssl
from pathlib import Path

TOPIC_1AXIS = "solartracker/1axis/data"
TOPIC_2AXIS = "solartracker/2axis/data"

API_URL = "http://127.0.0.1:8000/api/tracker/log"

# ESP32 menulis float yang gagal dibaca sebagai nan/inf lewat snprintf("%.2f"), dan itu bukan JSON sah.
# Tanpa ini satu sensor yang error membuat seluruh pesan dibuang tanpa jejak.
_NON_FINITE = re.compile(r'(?<=[:\[,])(\s*)-?(?:nan|inf)\b', re.IGNORECASE)


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


def parse_payload(raw):
    text = raw.decode("utf-8", errors="replace")
    return json.loads(_NON_FINITE.sub(r"\1null", text))


def num(value, default=0.0):
    """Angka berhingga, atau default bila kosong, bukan angka, atau nan/inf."""
    try:
        number = float(value)
    except (TypeError, ValueError):
        return default
    return number if math.isfinite(number) else default


def to_api_data(topic, payload):
    """Ubah pesan MQTT menjadi body /api/tracker/log, atau None untuk topik lain."""
    # Firmware 1 sumbu mengirim 'firmware_version', firmware 2 sumbu mengirim 'firmware'.
    firmware = payload.get('firmware_version') or payload.get('firmware')
    firmware = str(firmware).strip()[:32] if firmware else None

    v = num(payload.get('voltage'))
    i = num(payload.get('current'))
    p = num(payload.get('power'), v * i)
    mode = "Automatic" if str(payload.get('mode', '')).upper() == "AUTO" else "Manual"

    if topic == TOPIC_2AXIS:
        return {
            'panel_id': 'panel1',
            'azimuth': num(payload.get('azimuth')),
            'elevation': num(payload.get('elevation')),
            # Firmware 2 sumbu yang lebih lama tidak mengirim target; None tersimpan sebagai kosong.
            'target_azimuth': num(payload.get('target_azimuth'), None),
            'target_elevation': num(payload.get('target_elevation'), None),
            'voltage': v,
            'current': i,
            'power_output': p,
            'ldr_nw': num(payload.get('lightN')),
            'ldr_ne': num(payload.get('lightE')),
            'ldr_sw': num(payload.get('lightW')),
            'ldr_se': num(payload.get('lightS')),
            'tracking_mode': mode,
            'firmware_version': firmware,
        }
    if topic == TOPIC_1AXIS:
        return {
            'panel_id': 'panel2',
            'azimuth': num(payload.get('target_angle')),
            'elevation': num(payload.get('current_angle')),
            'voltage': v,
            'current': i,
            'power_output': p,
            'tracking_mode': mode,
            'firmware_version': firmware,
        }
    return None


def post_to_laravel(data):
    try:
        req = urllib.request.Request(API_URL, data=json.dumps(data).encode('utf-8'), headers={'Content-Type': 'application/json', 'Accept': 'application/json'}, method='POST')
        urllib.request.urlopen(req, timeout=30)
        print(f"Data masuk ke Database: {data['panel_id']} (firmware {data.get('firmware_version') or '-'})", flush=True)
    except urllib.error.HTTPError as e:
        # 422 berarti Laravel menolak isinya; tampilkan alasannya supaya tidak hilang diam-diam.
        body = e.read().decode('utf-8', errors='replace')[:300]
        print(f"Gagal simpan data {data['panel_id']}: HTTP {e.code} {body}", flush=True)
    except Exception as e:
        print(f"Gagal simpan data {data['panel_id']}: {e}", flush=True)


def on_connect(client, userdata, flags, rc):
    if rc != 0:
        # 4 atau 5 = user/password ditolak HiveMQ.
        print(f"Gagal terhubung ke HiveMQ (kode {rc}: {mqtt.connack_string(rc)}). Cek MQTT_USER dan MQTT_PASS di .env.", flush=True)
        return
    print("Berhasil terhubung ke HiveMQ Cloud!", flush=True)
    client.subscribe(TOPIC_1AXIS)
    client.subscribe(TOPIC_2AXIS)


def on_disconnect(client, userdata, rc):
    if rc != 0:
        print(f"Koneksi MQTT terputus (kode {rc}), mencoba lagi...", flush=True)


def on_message(client, userdata, msg):
    try:
        payload = parse_payload(msg.payload)
    except ValueError as e:
        print(f"Pesan {msg.topic} bukan JSON yang bisa dibaca ({e}): {msg.payload[:200]!r}", flush=True)
        return

    api_data = to_api_data(msg.topic, payload)
    if api_data is None:
        print(f"Topik tidak dikenal, dilewati: {msg.topic}", flush=True)
        return
    post_to_laravel(api_data)


def main():
    load_env_file(Path(__file__).resolve().parent / ".env")

    missing = [k for k in ("MQTT_BROKER", "MQTT_USER", "MQTT_PASS") if not os.environ.get(k)]
    if missing:
        sys.exit(f"Konfigurasi belum lengkap, isi di .env: {', '.join(missing)}")

    # paho-mqtt 2.x mewajibkan versi API callback; 1.x tidak mengenalnya.
    try:
        client = mqtt.Client(mqtt.CallbackAPIVersion.VERSION1)
    except AttributeError:
        client = mqtt.Client()
    client.username_pw_set(os.environ["MQTT_USER"], os.environ["MQTT_PASS"])
    client.tls_set(tls_version=ssl.PROTOCOL_TLS)
    client.on_connect = on_connect
    client.on_disconnect = on_disconnect
    client.on_message = on_message

    print("Menyambungkan ke MQTT...", flush=True)
    client.connect(os.environ["MQTT_BROKER"], int(os.environ.get("MQTT_PORT") or 8883), 60)
    client.loop_forever()


if __name__ == "__main__":
    main()
