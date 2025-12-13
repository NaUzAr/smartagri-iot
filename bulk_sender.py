"""
SmartAgri IoT - Bulk Data Sender (Test Pagination)
===================================================
Mengirim 50 data dummy sekaligus untuk test pagination.
Menggunakan HTTP API untuk kecepatan dan reliabilitas.

Cara pakai:
1. Pastikan server Laravel berjalan (php artisan serve)
2. Ganti TOKEN dengan token device kamu
3. Jalankan: python bulk_sender.py
"""

import requests
import random
import time
from datetime import datetime, timedelta

# ============================================
# KONFIGURASI - GANTI SESUAI KEBUTUHAN
# ============================================

# URL API Server
API_URL = "http://127.0.0.1:8000/api/sensor-data"

# Token device (dapatkan dari admin panel)
# GANTI INI dengan token device kamu!
TOKEN = "hNdkptRyxrZZHKv0"

# Jumlah data yang akan dikirim
TOTAL_DATA = 50

# Delay antar pengiriman (detik) - lebih lama untuk server dev
DELAY = 0.5

# ============================================
# SENSOR DATA RANGES (AWS Device)
# ============================================

SENSOR_RANGES = {
    "temperature": (20.0, 35.0),
    "humidity": (40.0, 90.0),
    "rainfall": (0.0, 50.0),
    "wind_speed": (0.0, 20.0),
    "wind_direction": (0.0, 360.0),
}

# ============================================
# FUNGSI
# ============================================

def generate_sensor_data():
    """Generate data sensor random"""
    data = {"token": TOKEN}
    for sensor, (min_val, max_val) in SENSOR_RANGES.items():
        data[sensor] = round(random.uniform(min_val, max_val), 2)
    return data

def send_data(data):
    """Kirim data ke server"""
    try:
        response = requests.post(API_URL, json=data, timeout=10)
        return response.status_code == 201, response.json()
    except Exception as e:
        return False, {"error": str(e)}

def main():
    print("=" * 60)
    print("🌱 SmartAgri IoT - Bulk Data Sender")
    print("=" * 60)
    print(f"📡 Server: {API_URL}")
    print(f"🔑 Token: {TOKEN}")
    print(f"📊 Total data: {TOTAL_DATA}")
    print("-" * 60)
    
    success_count = 0
    fail_count = 0
    
    print(f"\n🚀 Memulai pengiriman {TOTAL_DATA} data...\n")
    
    for i in range(1, TOTAL_DATA + 1):
        data = generate_sensor_data()
        success, response = send_data(data)
        
        if success:
            success_count += 1
            print(f"   [{i}/{TOTAL_DATA}] ✅ Sukses - Temp: {data['temperature']}°C, Humid: {data['humidity']}%")
        else:
            fail_count += 1
            print(f"   [{i}/{TOTAL_DATA}] ❌ Gagal - {response.get('message', response.get('error', 'Unknown'))}")
        
        time.sleep(DELAY)
    
    print("\n" + "=" * 60)
    print("📊 HASIL:")
    print(f"   ✅ Sukses: {success_count}")
    print(f"   ❌ Gagal: {fail_count}")
    print("=" * 60)
    
    if success_count > 0:
        print(f"\n🎉 {success_count} data berhasil dikirim!")
        print("   Buka halaman Monitoring → Lihat Data → Tab 'Tabel Data'")
        print("   untuk melihat pagination bekerja!")

if __name__ == "__main__":
    main()
