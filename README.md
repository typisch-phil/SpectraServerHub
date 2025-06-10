# SpectraHost - PHP Hosting Platform

Eine professionelle Hosting-Plattform entwickelt mit PHP, MySQL, und modernen Web-Technologien.

## 🚀 Features

- **Backend**: PHP 8.0+ mit PDO für sichere Datenbankverbindungen
- **Datenbank**: MySQL 5.7+ mit optimierten Abfragen und Indizes
- **Frontend**: HTML5, Tailwind CSS, Vanilla JavaScript
- **Zahlungen**: Mollie API Integration für sichere Payments
- **Server Management**: Proxmox VE API Integration
- **Security**: Prepared Statements, Password Hashing, Session Management
- **Design**: Responsive Design mit Dark/Light Mode

## 📋 Anforderungen

- **Webserver**: Apache 2.4+ oder Nginx 1.18+
- **PHP**: Version 8.0 oder höher
- **Datenbank**: MySQL 5.7+ oder MariaDB 10.3+
- **Extensions**: PDO, JSON, cURL, mbstring
- **Optional**: Proxmox VE Server für automatische VM-Erstellung

## 🛠 Installation

### 1. Dateien hochladen
```bash
# Projekt-Dateien in Webroot kopieren
cp -r * /var/www/html/spectrahost/
```

### 2. Datenbank einrichten
```bash
# MySQL-Datenbank erstellen
mysql -u root -p
```

```sql
-- Datenbank erstellen
CREATE DATABASE spectrahost CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Benutzer erstellen (optional)
CREATE USER 'spectrahost'@'localhost' IDENTIFIED BY 'sicheres_passwort';
GRANT ALL PRIVILEGES ON spectrahost.* TO 'spectrahost'@'localhost';
FLUSH PRIVILEGES;

-- Schema importieren
source database/schema.sql;
```

### 3. Umgebungsvariablen konfigurieren
```bash
# .env Datei erstellen
cp .env.example .env
```

Bearbeiten Sie die `.env` Datei:
```env
# Datenbank
DB_HOST=localhost
DB_NAME=spectrahost
DB_USER=spectrahost
DB_PASS=sicheres_passwort

# Mollie Payment
MOLLIE_API_KEY=test_xxxxx
MOLLIE_TEST_MODE=true

# Proxmox (optional)
PROXMOX_HOST=your-proxmox-server.com
PROXMOX_USER=api-user@pve
PROXMOX_PASS=api-password

# Site
SITE_URL=https://spectrahost.de
ADMIN_EMAIL=admin@spectrahost.de
```

### 4. Apache/Nginx konfigurieren

#### Apache
```apache
<VirtualHost *:80>
    ServerName spectrahost.de
    DocumentRoot /var/www/html/spectrahost/public
    
    <Directory /var/www/html/spectrahost/public>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/spectrahost_error.log
    CustomLog ${APACHE_LOG_DIR}/spectrahost_access.log combined
</VirtualHost>
```

#### Nginx
```nginx
server {
    listen 80;
    server_name spectrahost.de;
    root /var/www/html/spectrahost/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### 5. Berechtigungen setzen
```bash
# Ordner-Berechtigungen
chmod 755 /var/www/html/spectrahost
chmod 644 /var/www/html/spectrahost/.htaccess

# Logs-Ordner erstellen (falls benötigt)
mkdir /var/www/html/spectrahost/logs
chmod 755 /var/www/html/spectrahost/logs
```

## 🔧 Konfiguration

### Mollie Zahlungen einrichten
1. Account bei [Mollie](https://mollie.com) erstellen
2. API-Keys aus dem Dashboard kopieren
3. Webhook-URL in Mollie konfigurieren: `https://yourdomain.com/api/payment/webhook`

### Proxmox Integration (optional)
1. API-Benutzer in Proxmox erstellen
2. Entsprechende Berechtigungen vergeben
3. Credentials in `.env` eintragen

## 📁 Projektstruktur

```
spectrahost/
├── index.php           # Haupt-Router
├── .htaccess           # Apache-Konfiguration
├── includes/           # Core-Dateien
│   ├── config.php      # Konfiguration
│   ├── database.php    # Datenbankklasse
│   ├── auth.php        # Authentifizierung
│   ├── mollie.php      # Payment-Integration
│   ├── proxmox.php     # Server-Management
│   └── layout.php      # HTML-Layout
├── pages/              # Seiten-Templates
│   ├── home.php        # Startseite
│   ├── login.php       # Anmeldung
│   ├── register.php    # Registrierung
│   ├── dashboard.php   # Kundendashboard
│   ├── order.php       # Bestellseite
│   ├── contact.php     # Kontakt
│   └── impressum.php   # Impressum
├── api/                # API-Endpunkte
│   ├── login.php       # Login-API
│   ├── register.php    # Registrierungs-API
│   ├── order.php       # Bestellungs-API
│   ├── services.php    # Services-API
│   └── payment/        # Payment-Webhooks
├── css/                # Stylesheets
├── js/                 # JavaScript
└── database/           # Datenbank-Schema
```

## 🛡 Sicherheit

- **SQL Injection**: Schutz durch Prepared Statements
- **CSRF**: Token-basierter Schutz
- **XSS**: Output-Sanitization
- **Session**: Sichere Session-Konfiguration
- **Password**: Bcrypt-Hashing
- **Headers**: Security Headers via .htaccess

## 🚀 Deployment

### Produktions-Checkliste
- [ ] SSL-Zertifikat installiert
- [ ] .env Datei mit Produktions-Werten
- [ ] Error-Reporting deaktiviert
- [ ] Logs-Monitoring eingerichtet
- [ ] Backup-Strategie implementiert
- [ ] Security Headers konfiguriert

### Auto-Deployment
```bash
# Deploy-Script
#!/bin/bash
git pull origin main
composer install --no-dev --optimize-autoloader
php database/migrate.php
sudo systemctl reload apache2
```

## 📊 Performance

- **Caching**: Datei-basiertes Caching für statische Inhalte
- **Compression**: Gzip-Komprimierung via .htaccess
- **Optimierung**: Minifizierte CSS/JS-Dateien
- **CDN**: Integration für statische Assets

## 🔍 Monitoring

### Logs
- Apache/Nginx Access & Error Logs
- PHP Error Logs
- Application Logs in `/logs/`

### Health Checks
```bash
# Status-Check
curl -I https://spectrahost.de/
```

## 🤝 Support

- **Documentation**: Vollständige API-Dokumentation
- **Support**: E-Mail support@spectrahost.de
- **Updates**: Regelmäßige Security-Updates

## 📄 Lizenz

Proprietary - Alle Rechte vorbehalten

---

**SpectraHost** - Professionelles Hosting mit modernster Technologie