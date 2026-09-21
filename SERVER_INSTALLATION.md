# Panduan Instalasi dan Deployment Server

Berikut adalah panduan langkah demi langkah untuk melakukan instalasi dan *deployment* aplikasi ini di server (misalnya Ubuntu/Debian), lengkap dengan konfigurasi memori PHP dan dependensi khusus seperti FFmpeg (untuk konversi video).

## 1. Persiapan Server & Instalasi Dependensi
Pastikan server Anda sudah *up-to-date* dan install dependensi dasar yang dibutuhkan, termasuk PHP 8.1+ (sesuai *requirement* di `composer.json`).

```bash
sudo apt update && sudo apt upgrade -y

# Install Nginx, MySQL/MariaDB, dan dependensi lainnya
sudo apt install nginx mariadb-server curl git unzip -y

# Install PHP 8.1/8.2 beserta ekstensi yang dibutuhkan Laravel
sudo apt install php8.2 php8.2-fpm php8.2-mysql php8.2-xml php8.2-mbstring php8.2-curl php8.2-zip php8.2-gd php8.2-bcmath -y

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install Node.js & NPM (untuk build aset Vite/Tailwind)
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install nodejs -y
```

## 2. Instalasi FFmpeg (Sangat Penting)
Aplikasi ini menggunakan modul `php-ffmpeg/php-ffmpeg` untuk memproses video recording. Di server, aplikasi utama `ffmpeg` harus terinstal agar modul PHP tersebut bisa bekerja.

```bash
sudo apt install ffmpeg -y

# Verifikasi instalasi (pastikan muncul versi ffmpeg)
ffmpeg -version
```

## 3. Konfigurasi Memori PHP & Upload Limit
Karena aplikasi ini menerima *upload* file berupa rekaman video dan memprosesnya secara langsung (*synchronous*), **sangat wajib** menaikkan batas memori, ukuran *upload*, dan durasi maksimal eksekusi agar server tidak memutus proses *upload* & konversi video di tengah jalan.

Buka file `php.ini` untuk FPM (biasanya di `/etc/php/8.2/fpm/php.ini` tergantung versi PHP Anda) menggunakan nano:

```bash
sudo nano /etc/php/8.2/fpm/php.ini
```

Cari dan ubah konfigurasi berikut:
```ini
; Tingkatkan batas memori agar proses konversi video tidak kehabisan RAM
memory_limit = 512M   ; atau 1G jika server memiliki RAM yang cukup

; Tingkatkan ukuran maksimal file yang di-upload
upload_max_filesize = 100M  
post_max_size = 100M  

; Tingkatkan waktu maksimal eksekusi (karena proses konversi bisa memakan waktu lama)
max_execution_time = 300    ; 5 menit (atau lebih)
max_input_time = 300
```
> **Catatan:** Lakukan hal yang sama pada `/etc/php/8.2/cli/php.ini` jika menjalankan perintah melalui terminal.

Setelah selesai diubah, *restart* PHP-FPM:
```bash
sudo systemctl restart php8.2-fpm
```

## 4. Setup Database
Buat database dan *user* baru untuk aplikasi:

```bash
sudo mysql -u root

# Jalankan perintah berikut di dalam console MySQL:
CREATE DATABASE db_packingin;
CREATE USER 'user_packingin'@'localhost' IDENTIFIED BY 'password_yang_kuat';
GRANT ALL PRIVILEGES ON db_packingin.* TO 'user_packingin'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

## 5. Deployment Aplikasi Laravel
Arahkan direktori ke *document root* server Anda (contoh di `/var/www/`) lalu *clone* *source code* (atau gunakan FTP/SFTP untuk mentransfer kode ke server).

```bash
cd /var/www/
# Contoh jika clone dari repo
# git clone https://github.com/username/packingin.git packingin

cd packingin

# Install dependensi PHP
composer install --optimize-autoloader --no-dev

# Install dependensi Node.js & Build aset frontend
npm install
npm run build

# Setup .env file
cp .env.example .env
nano .env
```

Di dalam file `.env`, sesuaikan baris-baris berikut:
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://domain-anda.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=db_packingin
DB_USERNAME=user_packingin
DB_PASSWORD=password_yang_kuat

# Karena antrean diproses synchronous saat ini
QUEUE_CONNECTION=sync 
```

Selanjutnya, *generate application key*, jalankan migrasi, dan tautkan *storage*:
```bash
php artisan key:generate
php artisan migrate --force
php artisan storage:link

# Cache konfigurasi untuk performa lebih baik
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Pastikan *permissions* *folder* sudah benar agar web server (biasanya `www-data`) dapat menulis file video:
```bash
sudo chown -R www-data:www-data /var/www/packingin
sudo chmod -R 775 /var/www/packingin/storage
sudo chmod -R 775 /var/www/packingin/bootstrap/cache
```

## 6. Konfigurasi Web Server (Contoh: Nginx)
Buat konfigurasi *virtual host* Nginx untuk aplikasi Anda:

```bash
sudo nano /etc/nginx/sites-available/packingin
```

Isikan konfigurasi standar Laravel berikut:
```nginx
server {
    listen 80;
    server_name domain-anda.com;
    root /var/www/packingin/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-XSS-Protection "1; mode=block";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    # PENTING: Izinkan upload file besar di Nginx
    client_max_body_size 100M;
    
    # PENTING: Tingkatkan timeout agar tidak 504 Gateway Timeout saat proses konversi
    proxy_read_timeout 300;
    proxy_connect_timeout 300;
    proxy_send_timeout 300;
    fastcgi_read_timeout 300;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Aktifkan konfigurasi dan *restart* Nginx:
```bash
sudo ln -s /etc/nginx/sites-available/packingin /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```
