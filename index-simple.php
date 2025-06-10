<?php
// Vereinfachte Index-Datei für Plesk-Debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>SpectraHost - Server Test</title>";
echo "<style>body{font-family:Arial;margin:20px;} .success{color:green;} .error{color:red;}</style></head><body>";

echo "<h1>SpectraHost Server Test</h1>";

// Basis-PHP-Test
echo "<h2>PHP Basis-Test</h2>";
echo "<div class='success'>PHP Version: " . phpversion() . "</div>";
echo "<div class='success'>Server: " . $_SERVER['SERVER_SOFTWARE'] . "</div>";

// PDO-Test
echo "<h2>Datenbankverbindung</h2>";
try {
    $dsn = "mysql:host=37.114.32.205;dbname=s9281_spectrahost;port=3306;charset=utf8mb4";
    $username = "s9281_spectrahost";
    $password = getenv('MYSQL_PASSWORD') ?: '';
    
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "<div class='success'>✓ Datenbankverbindung erfolgreich</div>";
    
    // Test-Query
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch();
    echo "<div class='success'>✓ Benutzer in Datenbank: " . $result['count'] . "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>✗ Datenbankfehler: " . $e->getMessage() . "</div>";
}

// Session-Test
echo "<h2>Session-Test</h2>";
if (session_start()) {
    echo "<div class='success'>✓ Session gestartet</div>";
    $_SESSION['test'] = 'working';
    echo "<div class='success'>✓ Session-Schreibtest erfolgreich</div>";
} else {
    echo "<div class='error'>✗ Session-Start fehlgeschlagen</div>";
}

// URL-Routing-Test
echo "<h2>URL-Routing</h2>";
$request_uri = $_SERVER['REQUEST_URI'] ?? '/';
$parsed_url = parse_url($request_uri);
$path = $parsed_url['path'] ?? '/';

echo "<div class='success'>Request URI: $request_uri</div>";
echo "<div class='success'>Parsed Path: $path</div>";

// Einfaches Routing ohne .htaccess
$route = $_GET['route'] ?? trim($path, '/');
if (empty($route)) $route = 'home';

echo "<div class='success'>Aktive Route: $route</div>";

// Minimal-Website
echo "<h2>SpectraHost Navigation</h2>";
echo "<nav style='background:#f0f0f0;padding:10px;margin:20px 0;'>";
echo "<a href='?route=home' style='margin-right:15px;'>Home</a>";
echo "<a href='?route=services' style='margin-right:15px;'>Services</a>";
echo "<a href='?route=webspace' style='margin-right:15px;'>Webspace</a>";
echo "<a href='?route=vserver' style='margin-right:15px;'>VServer</a>";
echo "<a href='?route=login' style='margin-right:15px;'>Login</a>";
echo "</nav>";

// Content basierend auf Route
echo "<div style='background:white;padding:20px;border:1px solid #ddd;'>";

switch ($route) {
    case 'home':
    case '':
        echo "<h3>Willkommen bei SpectraHost</h3>";
        echo "<p>Professionelle Hosting-Lösungen für jeden Bedarf.</p>";
        echo "<div style='display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:20px;margin:20px 0;'>";
        echo "<div style='border:1px solid #ddd;padding:15px;'>
                <h4>Webspace</h4>
                <p>Zuverlässiges Webhosting ab 2,99€/Monat</p>
                <a href='?route=webspace'>Mehr erfahren</a>
              </div>";
        echo "<div style='border:1px solid #ddd;padding:15px;'>
                <h4>VServer</h4>
                <p>Virtuelle Server ab 9,99€/Monat</p>
                <a href='?route=vserver'>Mehr erfahren</a>
              </div>";
        echo "<div style='border:1px solid #ddd;padding:15px;'>
                <h4>Gameserver</h4>
                <p>Gaming-Server ab 4,99€/Monat</p>
                <a href='?route=gameserver'>Mehr erfahren</a>
              </div>";
        echo "</div>";
        break;
        
    case 'services':
        echo "<h3>Unsere Services</h3>";
        if (isset($pdo)) {
            try {
                $stmt = $pdo->query("SELECT * FROM services WHERE active = 1 ORDER BY type, price");
                $services = $stmt->fetchAll();
                
                $grouped = [];
                foreach ($services as $service) {
                    $grouped[$service['type']][] = $service;
                }
                
                foreach ($grouped as $type => $typeServices) {
                    echo "<h4>" . ucfirst($type) . "</h4>";
                    echo "<div style='display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:15px;'>";
                    foreach ($typeServices as $service) {
                        echo "<div style='border:1px solid #ddd;padding:15px;'>
                                <h5>{$service['name']}</h5>
                                <p>{$service['description']}</p>
                                <div><strong>{$service['price']}€/Monat</strong></div>
                              </div>";
                    }
                    echo "</div>";
                }
            } catch (Exception $e) {
                echo "<div class='error'>Fehler beim Laden der Services: " . $e->getMessage() . "</div>";
            }
        }
        break;
        
    case 'webspace':
        echo "<h3>Webspace-Hosting</h3>";
        echo "<p>Professionelles Webhosting mit allem was Sie brauchen.</p>";
        echo "<div style='display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:20px;'>";
        
        $webspace_plans = [
            ['name' => 'Starter', 'price' => '2.99', 'space' => '5GB', 'domains' => '1'],
            ['name' => 'Business', 'price' => '5.99', 'space' => '25GB', 'domains' => '5'],
            ['name' => 'Premium', 'price' => '9.99', 'space' => '100GB', 'domains' => 'Unlimited']
        ];
        
        foreach ($webspace_plans as $plan) {
            echo "<div style='border:1px solid #ddd;padding:20px;text-align:center;'>
                    <h4>{$plan['name']}</h4>
                    <div style='font-size:24px;color:#007cba;font-weight:bold;'>{$plan['price']}€</div>
                    <div style='color:#666;'>pro Monat</div>
                    <hr>
                    <div>Speicherplatz: {$plan['space']}</div>
                    <div>Domains: {$plan['domains']}</div>
                    <div>SSL-Zertifikat inklusive</div>
                    <div>24/7 Support</div>
                    <br>
                    <button style='background:#007cba;color:white;border:none;padding:10px 20px;cursor:pointer;'>Jetzt bestellen</button>
                  </div>";
        }
        echo "</div>";
        break;
        
    case 'vserver':
        echo "<h3>Virtuelle Server</h3>";
        echo "<p>Leistungsstarke VServer für anspruchsvolle Projekte.</p>";
        break;
        
    case 'gameserver':
        echo "<h3>Gameserver</h3>";
        echo "<p>Optimierte Gaming-Server für verschiedene Spiele.</p>";
        break;
        
    case 'login':
        echo "<h3>Login</h3>";
        echo "<form method='post' style='max-width:400px;'>
                <div style='margin:10px 0;'>
                    <label>E-Mail:</label><br>
                    <input type='email' name='email' style='width:100%;padding:8px;'>
                </div>
                <div style='margin:10px 0;'>
                    <label>Passwort:</label><br>
                    <input type='password' name='password' style='width:100%;padding:8px;'>
                </div>
                <button type='submit' style='background:#007cba;color:white;border:none;padding:10px 20px;'>Login</button>
              </form>";
        
        if ($_POST && isset($_POST['email']) && isset($_POST['password'])) {
            if (isset($pdo)) {
                try {
                    $stmt = $pdo->prepare("SELECT id, email, password, first_name FROM users WHERE email = ?");
                    $stmt->execute([$_POST['email']]);
                    $user = $stmt->fetch();
                    
                    if ($user && password_verify($_POST['password'], $user['password'])) {
                        $_SESSION['user_id'] = $user['id'];
                        echo "<div class='success'>Login erfolgreich! Willkommen " . $user['first_name'] . "</div>";
                    } else {
                        echo "<div class='error'>Ungültige Anmeldedaten</div>";
                    }
                } catch (Exception $e) {
                    echo "<div class='error'>Login-Fehler: " . $e->getMessage() . "</div>";
                }
            }
        }
        break;
        
    default:
        echo "<h3>Seite nicht gefunden</h3>";
        echo "<p>Die angeforderte Seite existiert nicht.</p>";
        break;
}

echo "</div>";

// Footer
echo "<footer style='margin-top:40px;padding:20px;background:#f0f0f0;text-align:center;'>";
echo "<p>&copy; 2025 SpectraHost - Professionelle Hosting-Lösungen</p>";
echo "<p><a href='?route=contact'>Kontakt</a> | <a href='?route=impressum'>Impressum</a> | <a href='/debug-plesk.php'>Debug</a></p>";
echo "</footer>";

echo "</body></html>";
?>