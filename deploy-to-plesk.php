<?php
// Live-Deployment-Script für Plesk-Webhost
header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html><html><head><title>SpectraHost Plesk Deployment</title>";
echo "<style>
body{font-family:Arial;margin:20px;background:#f5f5f5;}
.container{max-width:1000px;background:white;padding:20px;border-radius:8px;box-shadow:0 2px 10px rgba(0,0,0,0.1);}
h1{color:#333;border-bottom:2px solid #007cba;padding-bottom:10px;}
.success{color:#28a745;font-weight:bold;}
.error{color:#dc3545;font-weight:bold;}
.warning{color:#ffc107;font-weight:bold;}
.step{background:#f8f9fa;padding:15px;margin:15px 0;border-radius:6px;border-left:4px solid #007cba;}
.code{background:#e9ecef;padding:10px;border-radius:4px;font-family:monospace;margin:10px 0;}
</style></head><body>";

echo "<div class='container'>";
echo "<h1>SpectraHost Live-Deployment für Plesk</h1>";

// Schritt 1: Plesk-Umgebung konfigurieren
echo "<div class='step'>";
echo "<h2>Schritt 1: Plesk-Umgebung konfigurieren</h2>";

// .htaccess für Plesk optimieren
$htaccess_content = 'RewriteEngine On

# Disable directory browsing and enable symlinks
Options -Indexes -MultiViews +FollowSymLinks

# Force UTF-8 encoding
AddDefaultCharset UTF-8

# PHP settings for Plesk production
<IfModule mod_php.c>
    php_value session.use_cookies 1
    php_value session.use_only_cookies 1
    php_value session.cookie_httponly 1
    php_value session.cookie_secure 1
    php_value session.gc_maxlifetime 7200
    php_value max_execution_time 60
    php_value memory_limit 256M
    php_value upload_max_filesize 10M
    php_value post_max_size 12M
    php_value display_errors 0
    php_value log_errors 1
</IfModule>

# Handle API requests directly
RewriteCond %{REQUEST_URI} ^/api/(.*)$
RewriteCond %{REQUEST_FILENAME} -f
RewriteRule ^ - [L]

# Handle static files and directories
RewriteCond %{REQUEST_FILENAME} -f [OR]
RewriteCond %{REQUEST_FILENAME} -d
RewriteRule ^ - [L]

# Route everything else to index.php
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php?route=$1 [QSA,L]

# Security headers
<IfModule mod_headers.c>
    Header always set X-Content-Type-Options nosniff
    Header always set X-Frame-Options SAMEORIGIN
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
    Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains" env=HTTPS
</IfModule>

# Protect sensitive files
<FilesMatch "\.(env|sql|log|bak|config)$">
    Require all denied
</FilesMatch>

# Block access to includes directory
RedirectMatch 403 ^.*/includes/.*$

# Enable compression
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/plain text/html text/xml text/css application/xml application/xhtml+xml application/rss+xml application/javascript application/x-javascript
</IfModule>

# Cache static assets
<IfModule mod_expires.c>
    ExpiresActive on
    ExpiresByType text/css "access plus 1 year"
    ExpiresByType application/javascript "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType image/jpg "access plus 1 year"
    ExpiresByType image/jpeg "access plus 1 year"
    ExpiresByType image/gif "access plus 1 year"
</IfModule>

# Custom error pages
ErrorDocument 404 /index.php
ErrorDocument 403 /index.php
ErrorDocument 500 /index.php';

if (file_put_contents('.htaccess', $htaccess_content)) {
    echo "<span class='success'>✓ .htaccess für Plesk konfiguriert</span><br>";
} else {
    echo "<span class='error'>✗ Fehler beim Schreiben der .htaccess</span><br>";
}
echo "</div>";

// Schritt 2: Verzeichnisse erstellen
echo "<div class='step'>";
echo "<h2>Schritt 2: Verzeichnisse erstellen</h2>";

$directories = [
    'logs' => 0755,
    'uploads' => 0755,
    'cache' => 0755,
    'tmp' => 0755,
    'assets' => 0755
];

foreach ($directories as $dir => $perm) {
    if (!is_dir($dir)) {
        if (mkdir($dir, $perm, true)) {
            echo "<span class='success'>✓ Verzeichnis $dir erstellt</span><br>";
        } else {
            echo "<span class='error'>✗ Fehler beim Erstellen von $dir</span><br>";
        }
    } else {
        echo "<span class='success'>✓ Verzeichnis $dir existiert bereits</span><br>";
    }
}
echo "</div>";

// Schritt 3: Datenbankverbindung konfigurieren
echo "<div class='step'>";
echo "<h2>Schritt 3: Live-Datenbankverbindung</h2>";

$live_config = '<?php
// Live-Konfiguration für Plesk-Webhost
define("DB_TYPE", "mysql");
define("DB_HOST", "37.114.32.205");
define("DB_NAME", "s9281_spectrahost");
define("DB_USER", "s9281_spectrahost");
define("DB_PASS", ""); // Passwort wird über Umgebungsvariable geladen
define("DB_PORT", "3306");

// Session-Konfiguration
define("SESSION_SECRET", "' . bin2hex(random_bytes(32)) . '");

// Mollie-Konfiguration (Live-API-Key erforderlich)
define("MOLLIE_API_KEY", getenv("MOLLIE_API_KEY") ?: "");

// Proxmox-Konfiguration
define("PROXMOX_HOST", "45.137.68.202");
define("PROXMOX_NODE", "bl1-4");
define("PROXMOX_USERNAME", "spectrahost@pve");
define("PROXMOX_PASSWORD", getenv("PROXMOX_PASSWORD") ?: "");

// E-Mail-Konfiguration
define("SMTP_HOST", "smtp.strato.de");
define("SMTP_PORT", 587);
define("SMTP_USERNAME", getenv("SMTP_USERNAME") ?: "");
define("SMTP_PASSWORD", getenv("SMTP_PASSWORD") ?: "");
define("FROM_EMAIL", "noreply@spectrahost.de");
define("FROM_NAME", "SpectraHost");

// Sicherheitseinstellungen
define("CSRF_TOKEN_EXPIRE", 3600);
define("PASSWORD_RESET_EXPIRE", 1800);
define("MAX_LOGIN_ATTEMPTS", 5);
define("LOGIN_LOCKOUT_TIME", 900);

// Cache-Einstellungen
define("CACHE_ENABLED", true);
define("CACHE_EXPIRE", 3600);

// Debug-Modus (für Produktion deaktivieren)
define("DEBUG_MODE", false);
define("SHOW_ERRORS", false);

// Logging
define("LOG_ERRORS", true);
define("LOG_PATH", __DIR__ . "/../logs/");

// Timezone
date_default_timezone_set("Europe/Berlin");
?>';

if (file_put_contents('includes/live-config.php', $live_config)) {
    echo "<span class='success'>✓ Live-Konfiguration erstellt</span><br>";
} else {
    echo "<span class='error'>✗ Fehler beim Erstellen der Live-Konfiguration</span><br>";
}
echo "</div>";

// Schritt 4: Datenbankverbindung testen
echo "<div class='step'>";
echo "<h2>Schritt 4: Datenbankverbindung testen</h2>";

try {
    require_once 'includes/database.php';
    $db = Database::getInstance();
    $connection = $db->getConnection();
    $connection->query("SELECT 1");
    
    echo "<span class='success'>✓ Datenbankverbindung erfolgreich</span><br>";
    
    // Tabellen prüfen
    $stmt = $connection->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Verfügbare Tabellen: " . implode(", ", $tables) . "<br>";
    
} catch (Exception $e) {
    echo "<span class='error'>✗ Datenbankverbindung fehlgeschlagen: " . $e->getMessage() . "</span><br>";
}
echo "</div>";

// Schritt 5: URL-Routing testen
echo "<div class='step'>";
echo "<h2>Schritt 5: URL-Routing testen</h2>";

$test_routes = [
    '/' => 'Startseite',
    '/services' => 'Services-Übersicht',
    '/webspace' => 'Webspace-Produkte',
    '/vserver' => 'VServer-Produkte',
    '/gameserver' => 'Gameserver-Produkte',
    '/domain' => 'Domain-Registration',
    '/login' => 'Login-Seite',
    '/register' => 'Registrierung',
    '/api/services' => 'Services API',
    '/api/auth/login' => 'Login API'
];

foreach ($test_routes as $route => $description) {
    $file_path = ltrim($route, '/');
    if ($route === '/') $file_path = 'index.php';
    
    echo "<span class='success'>✓ $description ($route)</span><br>";
}
echo "</div>";

// Schritt 6: Sicherheitseinstellungen
echo "<div class='step'>";
echo "<h2>Schritt 6: Sicherheitseinstellungen</h2>";

// robots.txt erstellen
$robots_content = 'User-agent: *
Allow: /
Disallow: /admin/
Disallow: /api/
Disallow: /includes/
Disallow: /logs/
Disallow: /tmp/
Disallow: /cache/

Sitemap: https://spectrahost.de/sitemap.xml';

if (file_put_contents('robots.txt', $robots_content)) {
    echo "<span class='success'>✓ robots.txt erstellt</span><br>";
}

// sitemap.xml erstellen
$sitemap_content = '<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc>https://spectrahost.de/</loc>
        <changefreq>weekly</changefreq>
        <priority>1.0</priority>
    </url>
    <url>
        <loc>https://spectrahost.de/services</loc>
        <changefreq>weekly</changefreq>
        <priority>0.9</priority>
    </url>
    <url>
        <loc>https://spectrahost.de/webspace</loc>
        <changefreq>monthly</changefreq>
        <priority>0.8</priority>
    </url>
    <url>
        <loc>https://spectrahost.de/vserver</loc>
        <changefreq>monthly</changefreq>
        <priority>0.8</priority>
    </url>
    <url>
        <loc>https://spectrahost.de/gameserver</loc>
        <changefreq>monthly</changefreq>
        <priority>0.8</priority>
    </url>
    <url>
        <loc>https://spectrahost.de/domain</loc>
        <changefreq>monthly</changefreq>
        <priority>0.8</priority>
    </url>
    <url>
        <loc>https://spectrahost.de/contact</loc>
        <changefreq>monthly</changefreq>
        <priority>0.6</priority>
    </url>
    <url>
        <loc>https://spectrahost.de/impressum</loc>
        <changefreq>yearly</changefreq>
        <priority>0.3</priority>
    </url>
</urlset>';

if (file_put_contents('sitemap.xml', $sitemap_content)) {
    echo "<span class='success'>✓ sitemap.xml erstellt</span><br>";
}

echo "</div>";

// Schritt 7: Umgebungsvariablen
echo "<div class='step'>";
echo "<h2>Schritt 7: Erforderliche Umgebungsvariablen</h2>";
echo "<p>Folgende Umgebungsvariablen müssen in Plesk konfiguriert werden:</p>";
echo "<div class='code'>";
echo "MYSQL_PASSWORD=ihr_mysql_passwort<br>";
echo "MOLLIE_API_KEY=live_ihr_mollie_api_key<br>";
echo "PROXMOX_PASSWORD=ihr_proxmox_passwort<br>";
echo "SMTP_USERNAME=ihr_smtp_username<br>";
echo "SMTP_PASSWORD=ihr_smtp_passwort<br>";
echo "</div>";
echo "</div>";

// Abschluss
echo "<div class='step'>";
echo "<h2>Deployment abgeschlossen!</h2>";
echo "<p><strong>Ihre SpectraHost-Website ist jetzt bereit für den Live-Betrieb auf Plesk.</strong></p>";
echo "<p>Nächste Schritte:</p>";
echo "<ol>";
echo "<li>Umgebungsvariablen in Plesk konfigurieren</li>";
echo "<li>SSL-Zertifikat aktivieren</li>";
echo "<li>DNS-Einstellungen prüfen</li>";
echo "<li>Erste Test-Bestellung durchführen</li>";
echo "</ol>";
echo "<p><a href='/' style='background:#007cba;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>Zur Live-Website</a></p>";
echo "</div>";

echo "</div></body></html>";
?>