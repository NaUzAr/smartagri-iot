"""
SmartAgri IoT - Dummy Data Sender (AWS Device)
=============================================
Script Python untuk mengirim data dummy sensor ke server SmartAgri.
Mensimulasikan device AWS (Automatic Weather Station).

Cara pakai:
1. Install dependencies: pip install requests
2. Ganti TOKEN dengan token device kamu
3. Jalankan: python dummy_sender.py
"""

import requests
import random
import time
from datetime import datetime

# ============================================
# KONFIGURASI - GANTI SESUAI KEBUTUHAN
# ============================================

# URL API Server (ganti jika server di hosting)
API_URL = "http://127.0.0.1:8000/api/sensor-data"

# Token device AWS (dapatkan dari admin panel)
# GANTI INI dengan token device kamu!
TOKEN = "XXXXXXXXXXXXXXXX"

# Interval pengiriman data (dalam detik)
SEND_INTERVAL = 5

# ============================================
# SENSOR DATA RANGES (untuk generate random)
# ============================================

# Sesuai default sensor AWS:
# temperature, humidity, rainfall, wind_speed, wind_direction

SENSOR_RANGES = {
    "temperature": (20.0, 35.0),      # Suhu: 20-35°C
    "humidity": (40.0, 90.0),          # Kelembaban: 40-90%
    "rainfall": (0.0, 50.0),           # Curah hujan: 0-50 mm
    "wind_speed": (0.0, 20.0),         # Kecepatan angin: 0-20 m/s
    "wind_direction": (0.0, 360.0),    # Arah angin: 0-360°
}

# ============================================
# FUNGSI UTAMA
# ============================================

def generate_sensor_data():
    """Generate data sensor random dalam range yang realistis"""
    data = {"token": TOKEN}
    
    for sensor, (min_val, max_val) in SENSOR_RANGES.items():
        value = round(random.uniform(min_val, max_val), 2)
        data[sensor] = value
    
    return data

def send_data(data):
    """Kirim data ke server via HTTP POST"""
    try:
        response = requests.post(
            API_URL,
            json=data,
            headers={"Content-Type": "application/json"},
            timeout=10
        )
        return response.json(), response.status_code
    except requests.exceptions.RequestException as e:
        return {"error": str(e)}, 0

def print_header():
    """Print header program"""
    print("=" * 60)
    print("🌱 SmartAgri IoT - Dummy Data Sender (AWS Device)")
    print("=" * 60)
    print(f"📡 Server: {API_URL}")
    print(f"🔑 Token: {TOKEN[:4]}...{TOKEN[-4:]}" if len(TOKEN) == 16 else f"⚠️  Token: {TOKEN}")
    print(f"⏱️  Interval: {SEND_INTERVAL} detik")
    print("-" * 60)
    print("Tekan Ctrl+C untuk berhenti\n")

def main():
    """Fungsi utama - loop pengiriman data"""
    print_header()
    
    if TOKEN == "XXXXXXXXXXXXXXXX":
        print("⚠️  WARNING: Token masih default!")
        print("   Ganti variabel TOKEN dengan token device kamu.")
        print("   Dapatkan token dari Admin Panel > Device Management\n")
    
    counter = 0
    
    while True:
        counter += 1
        timestamp = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
        
        # Generate data dummy
        data = generate_sensor_data()
        
        print(f"[{timestamp}] Mengirim data #{counter}...")
        print(f"   Data: {data}")
        
        # Kirim ke server
        response, status_code = send_data(data)
        
        if status_code == 201:
            print(f"   ✅ Sukses! Sensors: {response.get('sensors_received', [])}")
        elif status_code == 0:
            print(f"   ❌ Gagal koneksi: {response.get('error', 'Unknown error')}")
        else:
            print(f"   ❌ Error {status_code}: {response.get('message', 'Unknown error')}")
        
        print()
        
        # Tunggu sebelum kirim lagi
        time.sleep(SEND_INTERVAL)

if __name__ == "__main__":
    try:
        main()
    except KeyboardInterrupt:
        print("\n\n👋 Program dihentikan oleh user.")
        print("   Total data terkirim dalam sesi ini.")
