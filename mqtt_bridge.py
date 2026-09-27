import paho.mqtt.client as mqtt
import json, urllib.request, ssl

# Konfigurasi HiveMQ
MQTT_BROKER = "d8aeaf4204e04ca49ee792d7dec5be25.s1.eu.hivemq.cloud"
MQTT_PORT = 8883
MQTT_USER = "BengkelIT"
MQTT_PASS = "BengkelIT@26"
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
        
        if msg.topic == TOPIC_2AXIS:
            api_data = {
                'panel_id': 'panel1',
                'azimuth': payload.get('azimuth', 0),
                'elevation': payload.get('elevation', 0),
                'voltage': payload.get('voltage', 0),
                'current': payload.get('current', 0),
                'power_output': payload.get('power', 0),
                'ldr_nw': payload.get('lightN', 0), 
                'ldr_ne': payload.get('lightE', 0),
                'ldr_sw': payload.get('lightW', 0),
                'ldr_se': payload.get('lightS', 0),
                'tracking_mode': "Automatic" if payload.get('mode') == "AUTO" else "Manual"
            }
        elif msg.topic == TOPIC_1AXIS:
            api_data = {
                'panel_id': 'panel2',
                'azimuth': payload.get('target_angle', 0),
                'elevation': payload.get('current_angle', 0),
                'voltage': payload.get('voltage', 0),
                'current': payload.get('current', 0),
                'power_output': payload.get('power', 0),
                'tracking_mode': "Automatic" if payload.get('mode') == "AUTO" else "Manual"
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
