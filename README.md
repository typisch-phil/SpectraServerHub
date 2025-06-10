# SpectraHost - Premium Hosting Management Platform

Eine professionelle Hosting-Management-Plattform mit integrierter Proxmox VE-Automatisierung und Mollie-Zahlungsabwicklung.

## 🚀 Deployment auf spectrahost.de

### Systemanforderungen
- PHP 8.1+ mit Extensions: PDO, PDO_MySQL, cURL, JSON, OpenSSL
- MySQL 5.7+ oder MariaDB 10.3+
- Apache mit mod_rewrite oder Nginx
- SSL-Zertifikat (empfohlen)

### 1. Dateien hochladen
```bash
# Alle Projektdateien in das Web-Root-Verzeichnis hochladen
# Stelle sicher, dass .htaccess mit hochgeladen wird
```

### 2. Umgebungsvariablen konfigurieren
```bash
# .env.production zu .env kopieren und anpassen
cp .env.production .env
```

**Wichtige Konfigurationen in .env:**
```env
# Datenbank
MYSQL_HOST=localhost
MYSQL_DATABASE=spectrahost
MYSQL_USER=dein_db_benutzer
MYSQL_PASSWORD=dein_db_passwort

# Mollie (Live-Modus)
MOLLIE_API_KEY=live_dein_mollie_schlüssel

# Proxmox VE
PROXMOX_HOST=45.137.68.202
PROXMOX_USERNAME=spectrahost@pve
PROXMOX_PASSWORD=dein_proxmox_passwort
PROXMOX_NODE=bl1-4
```

### 3. Datenbank einrichten
```sql
-- Datenbank erstellen
CREATE DATABASE spectrahost CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Benutzer erstellen (ersetze 'username' und 'password')
CREATE USER 'spectrahost'@'localhost' IDENTIFIED BY 'sicheres_passwort';
GRANT ALL PRIVILEGES ON spectrahost.* TO 'spectrahost'@'localhost';
FLUSH PRIVILEGES;
```

### 4. Deployment ausführen
```bash
# Deployment-Skript ausführen
php deploy.php
```

### 5. Verzeichnisberechtigungen setzen
```bash
chmod 755 logs uploads
chmod 644 .htaccess
chmod 600 .env
```

## 🔧 Konfiguration nach der Installation

### Admin-Zugang
- URL: `https://spectrahost.de/admin`
- Standard-Login: `admin@spectrahost.de` / `admin123`
- **Wichtig:** Passwort nach der ersten Anmeldung ändern!

### Integrationen konfigurieren

#### 1. Mollie Payment Gateway
1. Admin-Panel → Integrationen → Mollie
2. "Konfigurieren" klicken
3. "Speichern & Testen" ausführen
4. Webhook-URL automatisch konfiguriert: `https://spectrahost.de/api/webhooks/mollie.php`

#### 2. Proxmox VE Integration
1. Admin-Panel → Integrationen → Proxmox VE
2. "Konfigurieren" klicken
3. Produktionskonfiguration prüfen
4. "Speichern & Testen" ausführen

## 📁 Projektstruktur

```
spectrahost/
├── api/                    # API-Endpunkte
│   ├── webhooks/          # Payment-Webhooks
│   ├── admin/             # Admin-APIs
│   └── user/              # Benutzer-APIs
├── includes/              # PHP-Konfiguration
├── pages/                 # Frontend-Seiten
│   ├── admin/            # Admin-Panel
│   └── dashboard/        # Benutzer-Dashboard
├── assets/               # Statische Assets
├── logs/                 # Anwendungs-Logs
├── uploads/              # Datei-Uploads
├── .htaccess            # Apache-Konfiguration
├── .env                 # Umgebungsvariablen
└── deploy.php           # Deployment-Skript
```

## 🔒 Sicherheitsfeatures

- Session-Sicherheit mit HTTPOnly, Secure, SameSite
- CSRF-Schutz für alle Formulare
- SQL-Injection-Schutz durch Prepared Statements
- XSS-Schutz durch Content Security Policy
- Sichere Passwort-Hashing mit PASSWORD_DEFAULT
- HTTPS-Erzwingung auf Produktionsdomain

## 🌐 Domain-spezifische Anpassungen

Das System erkennt automatisch die Domain und passt sich entsprechend an:
- **Entwicklung:** `localhost:5000` (HTTP, relaxed security)
- **Produktion:** `spectrahost.de` (HTTPS, enhanced security)

### Automatische Konfigurationen:
- SSL-erzwungene Sessions auf HTTPS-Domains
- Domain-spezifische Cookie-Einstellungen
- Automatische Webhook-URL-Generierung
- Sichere Session-Parameter für Produktion

## 🎯 Funktionen

### Für Administratoren
- **Dashboard:** Übersicht über alle Services und Benutzer
- **Service-Management:** CRUD-Operationen für Hosting-Pakete
- **Benutzer-Verwaltung:** Vollständige Benutzerkontrolle
- **Integrationen:** Mollie & Proxmox VE-Konfiguration
- **Rechnungen:** Automatisierte Rechnungserstellung

### Für Kunden
- **Service-Bestellung:** Intuitive Paket-Auswahl
- **Dashboard:** Übersicht über aktive Services
- **Guthaben-Management:** Mollie-Integration für Aufladungen
- **Server-Kontrolle:** Start/Stop/Restart für VPS/Gameserver
- **Ticket-System:** Support-Anfragen (in Entwicklung)

## 🔧 Wartung

### Logs überwachen
```bash
tail -f logs/app.log
tail -f logs/error.log
```

### Datenbank-Backup
```bash
mysqldump -u spectrahost -p spectrahost > backup_$(date +%Y%m%d).sql
```

### Updates einspielen
1. Dateien sichern
2. Neue Dateien hochladen
3. `php deploy.php` ausführen
4. Funktionalität testen

## 🆘 Troubleshooting

### Login-Probleme
- Überprüfe Session-Konfiguration in `.env`
- Stelle sicher, dass Cookies aktiviert sind
- Prüfe HTTPS-Konfiguration

### API-Fehler 404
- Überprüfe `.htaccess`-Datei
- Aktiviere `mod_rewrite` in Apache
- Prüfe Verzeichnisberechtigungen

### Datenbank-Verbindungsfehler
- Überprüfe Datenbank-Credentials in `.env`
- Teste Verbindung mit `php deploy.php`
- Prüfe MySQL-Service-Status

## 📞 Support

Bei Problemen:
1. Logs in `logs/` prüfen
2. `php deploy.php` für Diagnose ausführen
3. Datenbank-Verbindung testen
4. Umgebungsvariablen überprüfen

**Entwickler:** SpectraHost Development Team  
**Version:** 1.0.0  
**Letzte Aktualisierung:** Juni 2025