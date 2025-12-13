# 🐳 Deploy dengan Docker di Ubuntu Server

## Prasyarat
- Ubuntu Server 20.04+ 
- Docker & Docker Compose terinstall

## 1. Install Docker

```bash
# Update system
sudo apt update

# Install Docker
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh

# Install Docker Compose
sudo apt install docker-compose-plugin -y

# Tambah user ke group docker (agar tidak perlu sudo)
sudo usermod -aG docker $USER
newgrp docker
```

## 2. Clone Project

```bash
cd /home/$USER
git clone https://github.com/NaUzAr/smartagri-iot.git
cd smartagri-iot
```

## 3. Setup Environment

```bash
cp .env.example .env
nano .env
```

**Edit .env (sesuaikan dengan docker-compose.yml):**
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=http://your-server-ip

DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=smartagri
DB_USERNAME=smartagri
DB_PASSWORD=smartagri_secret
```

## 4. Build & Run

```bash
# Build dan jalankan semua container
docker compose up -d --build

# Tunggu sampai selesai, lalu jalankan migration
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --force
docker compose exec app php artisan config:cache
docker compose exec app php artisan route:cache
```

## 5. Akses Aplikasi

Buka browser: `http://your-server-ip`

## 6. Useful Commands

```bash
# Lihat status container
docker compose ps

# Lihat logs
docker compose logs -f

# Lihat logs MQTT listener
docker compose logs -f app | grep mqtt

# Restart semua
docker compose restart

# Stop semua
docker compose down

# Rebuild setelah update code
git pull
docker compose up -d --build
docker compose exec app php artisan migrate --force
```

## 7. Database Access

```bash
# Masuk ke PostgreSQL
docker compose exec db psql -U smartagri -d smartagri

# Backup database
docker compose exec db pg_dump -U smartagri smartagri > backup.sql

# Restore database
cat backup.sql | docker compose exec -T db psql -U smartagri -d smartagri
```

## 8. Auto-Start saat Reboot

Docker dengan `restart: unless-stopped` sudah auto-start. Pastikan Docker service enabled:

```bash
sudo systemctl enable docker
```

## 9. Update MQTT Broker

Edit `docker/supervisord.conf`:
```ini
command=/usr/local/bin/php /var/www/artisan mqtt:listen --host=YOUR_BROKER_HOST
```

Lalu rebuild:
```bash
docker compose up -d --build
```

---

## Struktur Docker

```
smartagri-iot/
├── Dockerfile              # PHP-FPM + Laravel
├── docker-compose.yml      # Orchestration
├── docker/
│   ├── nginx.conf          # Nginx config
│   └── supervisord.conf    # PHP-FPM + MQTT listener
```

## Troubleshooting

```bash
# Permission error
docker compose exec app chown -R www-data:www-data storage bootstrap/cache

# Clear cache
docker compose exec app php artisan cache:clear
docker compose exec app php artisan config:clear

# Rebuild from scratch
docker compose down -v
docker compose up -d --build
```
