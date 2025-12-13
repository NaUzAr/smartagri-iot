# 🌱 SmartAgri - IoT Agriculture Monitoring System

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-12.x-red?style=for-the-badge&logo=laravel" />
  <img src="https://img.shields.io/badge/PHP-8.2+-blue?style=for-the-badge&logo=php" />
  <img src="https://img.shields.io/badge/PostgreSQL-15-blue?style=for-the-badge&logo=postgresql" />
  <img src="https://img.shields.io/badge/MQTT-Supported-green?style=for-the-badge" />
</p>

Sistem monitoring pertanian cerdas berbasis IoT dengan Laravel. Pantau kondisi lahan, cuaca, dan sensor tanaman secara real-time.

## ✨ Fitur Utama

- 🔐 **Authentication** - Login & Register dengan role Admin/User
- 📡 **Device Management** - Admin bisa membuat device dengan sensor dinamis
- 🌡️ **Dynamic Sensors** - Tambah sensor tanpa batas, termasuk beberapa sensor jenis sama
- 📊 **Real-time Monitoring** - Grafik Chart.js dan tabel data dengan pagination
- 🔌 **MQTT Listener** - Background process untuk terima data sensor
- 🌐 **REST API** - Alternatif HTTP untuk device yang tidak support MQTT
- 🎨 **Modern UI** - Glassmorphism design dengan tema pertanian (hijau & biru langit)

## 📸 Screenshots

| Beranda | Monitoring |
|---------|------------|
| Halaman utama dengan tema pertanian | Dashboard monitoring sensor |

## 🚀 Instalasi

### 1. Clone Repository

```bash
git clone https://github.com/YOUR_USERNAME/smartagri-iot.git
cd smartagri-iot
```

### 2. Install Dependencies

```bash
composer install
npm install
```

### 3. Setup Environment

```bash
cp .env.example .env
php artisan key:generate
```

### 4. Konfigurasi Database

Edit file `.env`:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=smartagri
DB_USERNAME=postgres
DB_PASSWORD=your_password
```

### 5. Jalankan Migration

```bash
php artisan migrate
```

### 6. Jalankan Server

```bash
php artisan serve
```

Akses di: `http://localhost:8000`

## 👤 Akun Default

Tidak ada akun default. Register akun baru, akun pertama bisa dijadikan admin dengan mengubah field `role` di database menjadi `admin`.

```sql
UPDATE users SET role = 'admin' WHERE id = 1;
```

## 📡 Menerima Data Sensor

### Opsi 1: MQTT Listener

Jalankan di terminal terpisah:

```bash
# Basic (localhost:1883)
php artisan mqtt:listen

# Custom broker
php artisan mqtt:listen --host=broker.emqx.io --port=1883

# Dengan authentication
php artisan mqtt:listen --host=your-broker.com --username=user --password=pass
```

**Format JSON dari Device:**

```json
{
  "token": "YOUR_16_CHAR_TOKEN",
  "temperature_1": 25.5,
  "humidity_1": 80,
  "soil_moisture": 45
}
```

### Opsi 2: HTTP API

```bash
POST /api/sensor-data
Content-Type: application/json

{
  "token": "XXXXXXXXXXXXXXXX",
  "temperature_1": 25.5,
  "humidity_1": 80
}
```

**Response:**

```json
{
  "success": true,
  "message": "Data saved successfully.",
  "device": "Sensor Kebun 01",
  "sensors_received": ["temperature_1", "humidity_1"]
}
```

### Mendapatkan Data Terbaru

```bash
GET /api/sensor-data/{token}
```

## 🏗️ Struktur Project

```
├── app/
│   ├── Console/Commands/
│   │   └── MqttListener.php      # MQTT listener command
│   ├── Http/Controllers/
│   │   ├── AdminDeviceController.php
│   │   ├── AuthController.php
│   │   ├── MonitoringController.php
│   │   └── Api/SensorDataController.php
│   └── Models/
│       ├── Device.php
│       ├── DeviceSensor.php
│       ├── User.php
│       └── UserDevice.php
├── resources/views/
│   ├── admin/                    # Halaman admin
│   ├── auth/                     # Login & Register
│   ├── monitoring/               # Halaman monitoring user
│   └── page/beranda.blade.php    # Landing page
└── routes/
    ├── web.php                   # Web routes
    └── api.php                   # API routes
```

## 🔧 Tipe Device

Sistem mendukung 2 tipe device:

| Tipe | Nama | Default Sensors |
|------|------|-----------------|
| `aws` | AWS (Automatic Weather Station) | Suhu, Kelembaban, Curah Hujan, Kecepatan Angin, Arah Angin |
| `smart_gh` | Smart GH (Smart Greenhouse) | 2x Suhu, 2x Kelembaban, Kelembaban Tanah, Intensitas Cahaya |

### Menambah Tipe Device Baru

Edit `app/Models/Device.php`:

```php
public static function getDeviceTypes(): array
{
    return [
        'aws' => 'AWS (Automatic Weather Station)',
        'smart_gh' => 'Smart GH (Smart Greenhouse)',
        'new_type' => 'Nama Tipe Baru',  // Tambah disini
    ];
}
```

### Menambah Sensor Baru

Edit `app/Models/Device.php`:

```php
public static function getAvailableSensors(): array
{
    return [
        'temperature' => ['label' => 'Suhu (Temperature)', 'unit' => '°C', 'icon' => '🌡️'],
        'humidity' => ['label' => 'Kelembaban (Humidity)', 'unit' => '%', 'icon' => '💧'],
        // Tambah sensor baru:
        'new_sensor' => ['label' => 'Sensor Baru', 'unit' => 'unit', 'icon' => '📊'],
    ];
}
```

## 📱 Contoh Kode ESP32/Arduino

```cpp
#include <WiFi.h>
#include <HTTPClient.h>
#include <ArduinoJson.h>

const char* ssid = "YOUR_WIFI_SSID";
const char* password = "YOUR_WIFI_PASSWORD";
const char* serverUrl = "http://YOUR_SERVER_IP/api/sensor-data";
const char* token = "YOUR_16_CHAR_TOKEN";

void sendSensorData(float temp, float humidity) {
  HTTPClient http;
  http.begin(serverUrl);
  http.addHeader("Content-Type", "application/json");

  StaticJsonDocument<200> doc;
  doc["token"] = token;
  doc["temperature_1"] = temp;
  doc["humidity_1"] = humidity;

  String jsonString;
  serializeJson(doc, jsonString);

  int httpCode = http.POST(jsonString);
  
  if (httpCode > 0) {
    Serial.println("Data sent successfully!");
  } else {
    Serial.println("Error sending data");
  }
  
  http.end();
}
```

## 🛠️ Tech Stack

- **Backend**: Laravel 12
- **Database**: PostgreSQL
- **Frontend**: Blade Templates, Bootstrap 5, Chart.js
- **MQTT**: php-mqtt/client
- **Styling**: Custom CSS dengan Glassmorphism

## 📄 License

MIT License - Bebas digunakan untuk keperluan pribadi maupun komersial.

## 🤝 Contributing

Pull requests are welcome! Untuk perubahan besar, silakan buka issue terlebih dahulu.

---

<p align="center">
  Made with ❤️ for Smart Agriculture
</p>
