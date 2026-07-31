# PANTAU — Pusat ANalitik Transaksi dan Aktivitas User

Platform monitoring terpadu untuk **audit trail**, **aktivitas pengguna**, dan **analitik sistem SIMGOS** berbasis web.

---

## Fitur

- **Audit Trail** — monitoring perubahan data (Create, Update, Delete) secara real-time
- **Aktivitas User** — rekap aktivitas pengguna per sesi
- **Login Monitor** — pemantauan percobaan login dan status autentikasi
- **Monitoring Modul** — statistik penggunaan modul SIMGOS
- **Analitik** — grafik dan dashboard interaktif
- **Pencarian & Export** — filter data dan ekspor laporan
- **Dark / Light Mode** — tema adaptif

---

## Requirement

### Sistem Operasi

- Ubuntu Server 22.04 LTS atau lebih baru

### Software

- Docker Engine 24+
- Docker Compose Plugin 2+

### Minimal Hardware

| Komponen | Minimal |
| -------- | ------- |
| CPU      | 1 Core  |
| RAM      | 512 MB  |
| Storage  | 4 GB    |

---

## Komponen

| Komponen   | Versi | Fungsi                   |
| ---------- | ----- | ------------------------ |
| PHP-Apache | 8.2+  | Web Server & PHP Runtime |
| MySQL      | 8.0   | Database SIMGOS          |

> Aplikasi ini berkomunikasi dengan **webservice SIMGOS** melalui REST API. Koneksi database digunakan sebagai fallback untuk query audit trail langsung.

---

# 1. Instalasi Docker

## 1.1 Hapus Docker Lama

```bash
sudo apt remove $(dpkg --get-selections | grep -E "docker.io|docker-compose|docker-compose-v2|docker-doc|podman-docker|containerd|runc" | cut -f1)
```

## 1.2 Tambahkan Repository Docker

```bash
sudo apt update
sudo apt install -y ca-certificates curl

sudo install -m 0755 -d /etc/apt/keyrings

sudo curl -fsSL https://download.docker.com/linux/ubuntu/gpg \
  -o /etc/apt/keyrings/docker.asc

sudo chmod a+r /etc/apt/keyrings/docker.asc

sudo tee /etc/apt/sources.list.d/docker.sources <<EOF
Types: deb
URIs: https://download.docker.com/linux/ubuntu
Suites: $(. /etc/os-release && echo "${UBUNTU_CODENAME:-$VERSION_CODENAME}")
Components: stable
Signed-By: /etc/apt/keyrings/docker.asc
EOF

sudo apt update
```

## 1.3 Install Docker

```bash
sudo apt install -y \
  docker-ce \
  docker-ce-cli \
  containerd.io \
  docker-buildx-plugin \
  docker-compose-plugin
```

Aktifkan Docker.

```bash
sudo systemctl enable --now docker
```

Verifikasi.

```bash
docker --version
docker compose version
```

Opsional — agar user tidak perlu `sudo`.

```bash
sudo usermod -aG docker $USER
newgrp docker
```

---

# 2. Download Source Code

Clone repository dari GitHub.

```bash
cd /opt

sudo git clone https://github.com/krisnadwiki/Pantau-Audit-Trail-SIMGos.git pantau

cd /opt/pantau
```

---

# 3. Struktur Folder

```text
pantau/
├── docker-compose.yml
├── Dockerfile
├── .dockerignore
├── README.md
└── app/
    ├── .env
    ├── .env.example
    ├── Dockerfile
    ├── apache.conf
    ├── api/
    │   ├── audit.php
    │   └── auth.php
    ├── config/
    │   ├── config.php
    │   └── env.php
    ├── public/
    │   ├── index.php
    │   ├── login.php
    │   ├── dashboard.php
    │   ├── audit.php
    │   ├── user-activity.php
    │   ├── login-monitor.php
    │   ├── modules.php
    │   ├── analytics.php
    │   ├── search.php
    │   ├── export.php
    │   └── settings.php
    └── templates/
        ├── header.php
        ├── navbar.php
        ├── sidebar.php
        ├── footer.php
        └── modal-rincian.php
```

---

# 4. Konfigurasi

Salin file konfigurasi.

```bash
cp app/.env.example app/.env
```

Edit konfigurasi.

```bash
nano app/.env
```

Contoh isi konfigurasi.

```env
APP_NAME=PANTAU - Pusat Analitik Transaksi dan Aktivitas User

# URL webservice SIMGOS
API_BASE_URL=http://192.168.1.100/webservice

TIMEZONE=Asia/Jakarta
SESSION_TIMEOUT=3600

# Koneksi database SIMGOS (untuk query audit trail langsung)
DB_HOST=192.168.1.100
DB_PORT=3306
DB_NAME=medicalrecord
DB_USER=admin
DB_PASS=password
```

Sesuaikan `API_BASE_URL`, `DB_HOST`, `DB_USER`, dan `DB_PASS` dengan environment SIMGOS yang digunakan.

---

# 5. Menjalankan Aplikasi

Build image.

```bash
docker compose build
```

Jalankan container.

```bash
docker compose up -d
```

Periksa status.

```bash
docker ps
```

Contoh output.

```text
NAME      STATUS
pantau    Up
```

---

# 6. Mengakses Aplikasi

```
http://SERVER_IP
```

atau jika dijalankan lokal.

```
http://localhost
```

Login menggunakan akun SIMGOS yang terdaftar. Pastikan akun memiliki akses ke modul yang sesuai.

---

# 7. Konfigurasi Firewall

## Ubuntu (UFW)

```bash
sudo ufw allow 80/tcp
sudo ufw enable
sudo ufw status
```

## Rocky Linux / AlmaLinux (firewalld)

```bash
sudo firewall-cmd --permanent --add-port=80/tcp
sudo firewall-cmd --reload
```

---

# 8. Operasional Docker

| Perintah | Fungsi |
| -------- | ------ |
| `docker compose up -d` | Jalankan container |
| `docker compose stop` | Hentikan container |
| `docker compose start` | Nyalakan kembali |
| `docker compose restart` | Restart container |
| `docker compose down` | Hapus container |
| `docker compose down -v` | Hapus container dan volume |

---

# 9. Monitoring & Log

Status container.

```bash
docker ps
```

Lihat log aplikasi.

```bash
docker compose logs -f pantau
```

Resource usage.

```bash
docker stats
```

---

# 10. Update Aplikasi

```bash
cd /opt/pantau

git pull

docker compose build --no-cache

docker compose up -d
```

---

# Troubleshooting

## Container tidak berjalan

```bash
docker ps -a
docker compose logs
docker compose restart
```

## Tidak dapat membuka aplikasi

```bash
ss -tulpn | grep 80
docker ps
sudo ufw allow 80/tcp
```

## Login gagal — tidak dapat terhubung ke SIMGOS

Pastikan `API_BASE_URL` di `.env` dapat dijangkau dari dalam container.

```bash
docker exec -it pantau curl -v http://IP_SIMGOS/webservice/authentication/captcha
```

## Perubahan source code tidak muncul

```bash
docker compose restart pantau
```

Jika masih belum muncul.

```bash
docker compose build --no-cache
docker compose up -d
```

---

# Acknowledgement

Developed by **Krisna Dwiki Aldi** © 2022–2026  
Information, Communication and Technology — RSUD Kilisuci
