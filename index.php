<?php
// SpectraHost - Plesk-optimierte Live-Version
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ERROR | E_WARNING | E_PARSE);

// Session starten
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Datenbankverbindung
$pdo = null;
try {
    $dsn = "mysql:host=37.114.32.205;dbname=s9281_spectrahost;port=3306;charset=utf8mb4";
    $pdo = new PDO($dsn, "s9281_spectrahost", getenv('MYSQL_PASSWORD') ?: '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (Exception $e) {
    error_log("DB Error: " . $e->getMessage());
}

// Routing
$route = $_GET['route'] ?? trim($_SERVER['REQUEST_URI'] ?? '', '/');
if (empty($route) || $route === 'index.php') $route = 'home';

// Login-Verarbeitung
if ($_POST && isset($_POST['email']) && isset($_POST['password']) && $pdo) {
    try {
        $stmt = $pdo->prepare("SELECT id, email, password, first_name, last_name, role FROM users WHERE email = ?");
        $stmt->execute([$_POST['email']]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($_POST['password'], $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user'] = $user;
            header('Location: ?route=dashboard');
            exit;
        } else {
            $login_error = "Ungültige Anmeldedaten";
        }
    } catch (Exception $e) {
        $login_error = "Login-Fehler";
    }
}

// Logout
if ($route === 'logout') {
    session_destroy();
    header('Location: ?route=home');
    exit;
}

// Benutzer-Info
$user = $_SESSION['user'] ?? null;
$isLoggedIn = !empty($user);

// HTML-Header
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SpectraHost - Professionelle Hosting-Lösungen</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .gradient-bg { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .card { transition: transform 0.3s ease; }
        .card:hover { transform: translateY(-5px); }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center">
                    <h1 class="text-2xl font-bold text-blue-600">SpectraHost</h1>
                </div>
                <div class="hidden md:flex space-x-6">
                    <a href="?route=home" class="text-gray-700 hover:text-blue-600">Home</a>
                    <a href="?route=services" class="text-gray-700 hover:text-blue-600">Services</a>
                    <a href="?route=webspace" class="text-gray-700 hover:text-blue-600">Webspace</a>
                    <a href="?route=vserver" class="text-gray-700 hover:text-blue-600">VServer</a>
                    <a href="?route=gameserver" class="text-gray-700 hover:text-blue-600">Gameserver</a>
                    <a href="?route=domain" class="text-gray-700 hover:text-blue-600">Domains</a>
                </div>
                <div class="flex items-center space-x-4">
                    <?php if ($isLoggedIn): ?>
                        <span class="text-gray-700">Hallo, <?= htmlspecialchars($user['first_name']) ?></span>
                        <a href="?route=dashboard" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Dashboard</a>
                        <a href="?route=logout" class="text-gray-700 hover:text-blue-600">Logout</a>
                    <?php else: ?>
                        <a href="?route=login" class="text-gray-700 hover:text-blue-600">Login</a>
                        <a href="?route=register" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Registrieren</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Content -->
    <main class="min-h-screen">
        <?php
        switch ($route) {
            case 'home':
                ?>
                <!-- Hero Section -->
                <section class="gradient-bg text-white py-20">
                    <div class="max-w-7xl mx-auto px-4 text-center">
                        <h1 class="text-5xl font-bold mb-6">Professionelle Hosting-Lösungen</h1>
                        <p class="text-xl mb-8">Zuverlässig, schnell und sicher - Ihr Partner für Web-Hosting</p>
                        <a href="?route=services" class="bg-white text-blue-600 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100">
                            Alle Services ansehen
                        </a>
                    </div>
                </section>

                <!-- Services Overview -->
                <section class="py-16">
                    <div class="max-w-7xl mx-auto px-4">
                        <h2 class="text-3xl font-bold text-center mb-12">Unsere Services</h2>
                        <div class="grid md:grid-cols-4 gap-8">
                            <?php
                            $services = [
                                ['name' => 'Webspace', 'desc' => 'Zuverlässiges Webhosting', 'price' => 'ab 2,99€', 'route' => 'webspace'],
                                ['name' => 'VServer', 'desc' => 'Virtuelle Dedicated Server', 'price' => 'ab 9,99€', 'route' => 'vserver'],
                                ['name' => 'Gameserver', 'desc' => 'Gaming-Server', 'price' => 'ab 4,99€', 'route' => 'gameserver'],
                                ['name' => 'Domains', 'desc' => 'Domain-Registration', 'price' => 'ab 0,99€', 'route' => 'domain']
                            ];
                            
                            foreach ($services as $service): ?>
                                <div class="card bg-white p-6 rounded-lg shadow-lg text-center">
                                    <h3 class="text-xl font-bold mb-3"><?= $service['name'] ?></h3>
                                    <p class="text-gray-600 mb-4"><?= $service['desc'] ?></p>
                                    <div class="text-2xl font-bold text-blue-600 mb-4"><?= $service['price'] ?></div>
                                    <a href="?route=<?= $service['route'] ?>" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                                        Details
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>
                <?php
                break;

            case 'services':
                ?>
                <div class="max-w-7xl mx-auto px-4 py-12">
                    <h1 class="text-4xl font-bold mb-8">Alle Services</h1>
                    
                    <?php if ($pdo): ?>
                        <?php
                        try {
                            $stmt = $pdo->query("SELECT * FROM services WHERE active = 1 ORDER BY type, price");
                            $services = $stmt->fetchAll();
                            
                            $grouped = [];
                            foreach ($services as $service) {
                                $grouped[$service['type']][] = $service;
                            }
                            
                            foreach ($grouped as $type => $typeServices): ?>
                                <div class="mb-12">
                                    <h2 class="text-2xl font-bold mb-6 capitalize"><?= htmlspecialchars($type) ?></h2>
                                    <div class="grid md:grid-cols-3 gap-6">
                                        <?php foreach ($typeServices as $service): ?>
                                            <div class="bg-white p-6 rounded-lg shadow-lg">
                                                <h3 class="text-xl font-bold mb-3"><?= htmlspecialchars($service['name']) ?></h3>
                                                <p class="text-gray-600 mb-4"><?= htmlspecialchars($service['description']) ?></p>
                                                <div class="text-2xl font-bold text-blue-600 mb-4">
                                                    <?= number_format($service['price'], 2) ?>€/Monat
                                                </div>
                                                <button class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700">
                                                    Jetzt bestellen
                                                </button>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach;
                        } catch (Exception $e) {
                            echo "<p class='text-red-600'>Fehler beim Laden der Services</p>";
                        }
                        ?>
                    <?php else: ?>
                        <p class="text-red-600">Datenbankverbindung nicht verfügbar</p>
                    <?php endif; ?>
                </div>
                <?php
                break;

            case 'login':
                ?>
                <div class="max-w-md mx-auto mt-16 bg-white p-8 rounded-lg shadow-lg">
                    <h1 class="text-2xl font-bold mb-6 text-center">Login</h1>
                    
                    <?php if (isset($login_error)): ?>
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                            <?= htmlspecialchars($login_error) ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="post">
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">E-Mail</label>
                            <input type="email" name="email" required 
                                   class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:border-blue-500">
                        </div>
                        <div class="mb-6">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Passwort</label>
                            <input type="password" name="password" required 
                                   class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:border-blue-500">
                        </div>
                        <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700">
                            Anmelden
                        </button>
                    </form>
                    
                    <p class="text-center mt-4">
                        <a href="?route=register" class="text-blue-600 hover:underline">Noch kein Konto? Registrieren</a>
                    </p>
                </div>
                <?php
                break;

            case 'dashboard':
                if (!$isLoggedIn) {
                    header('Location: ?route=login');
                    exit;
                }
                ?>
                <div class="max-w-7xl mx-auto px-4 py-12">
                    <h1 class="text-3xl font-bold mb-8">Dashboard</h1>
                    
                    <div class="grid md:grid-cols-3 gap-6">
                        <div class="bg-white p-6 rounded-lg shadow">
                            <h3 class="text-lg font-semibold mb-2">Meine Services</h3>
                            <p class="text-3xl font-bold text-blue-600">0</p>
                        </div>
                        <div class="bg-white p-6 rounded-lg shadow">
                            <h3 class="text-lg font-semibold mb-2">Guthaben</h3>
                            <p class="text-3xl font-bold text-green-600">0,00€</p>
                        </div>
                        <div class="bg-white p-6 rounded-lg shadow">
                            <h3 class="text-lg font-semibold mb-2">Offene Tickets</h3>
                            <p class="text-3xl font-bold text-orange-600">0</p>
                        </div>
                    </div>
                </div>
                <?php
                break;

            case 'webspace':
                ?>
                <div class="max-w-7xl mx-auto px-4 py-12">
                    <h1 class="text-4xl font-bold mb-8">Webspace Hosting</h1>
                    <p class="text-lg text-gray-600 mb-12">Professionelles Webhosting für Ihre Website</p>
                    
                    <div class="grid md:grid-cols-3 gap-8">
                        <?php
                        $plans = [
                            ['name' => 'Starter', 'price' => 2.99, 'space' => '5GB', 'domains' => 1, 'email' => '10'],
                            ['name' => 'Business', 'price' => 5.99, 'space' => '25GB', 'domains' => 5, 'email' => '50'],
                            ['name' => 'Premium', 'price' => 9.99, 'space' => '100GB', 'domains' => 'Unlimited', 'email' => 'Unlimited']
                        ];
                        
                        foreach ($plans as $plan): ?>
                            <div class="bg-white p-8 rounded-lg shadow-lg text-center">
                                <h3 class="text-2xl font-bold mb-4"><?= $plan['name'] ?></h3>
                                <div class="text-4xl font-bold text-blue-600 mb-2"><?= number_format($plan['price'], 2) ?>€</div>
                                <div class="text-gray-600 mb-6">pro Monat</div>
                                
                                <div class="space-y-3 mb-8">
                                    <div><?= $plan['space'] ?> Speicherplatz</div>
                                    <div><?= $plan['domains'] ?> Domain(s)</div>
                                    <div><?= $plan['email'] ?> E-Mail Konten</div>
                                    <div>SSL-Zertifikat inklusive</div>
                                    <div>24/7 Support</div>
                                </div>
                                
                                <button class="w-full bg-blue-600 text-white py-3 rounded-lg hover:bg-blue-700 font-semibold">
                                    Jetzt bestellen
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php
                break;

            default:
                ?>
                <div class="max-w-4xl mx-auto px-4 py-16 text-center">
                    <h1 class="text-4xl font-bold mb-4">404 - Seite nicht gefunden</h1>
                    <p class="text-gray-600 mb-8">Die angeforderte Seite existiert nicht.</p>
                    <a href="?route=home" class="bg-blue-600 text-white px-6 py-3 rounded hover:bg-blue-700">
                        Zur Startseite
                    </a>
                </div>
                <?php
                break;
        }
        ?>
    </main>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-12">
        <div class="max-w-7xl mx-auto px-4">
            <div class="grid md:grid-cols-4 gap-8">
                <div>
                    <h3 class="text-lg font-semibold mb-4">SpectraHost</h3>
                    <p class="text-gray-400">Professionelle Hosting-Lösungen seit 2025</p>
                </div>
                <div>
                    <h4 class="font-semibold mb-4">Services</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="?route=webspace" class="hover:text-white">Webspace</a></li>
                        <li><a href="?route=vserver" class="hover:text-white">VServer</a></li>
                        <li><a href="?route=gameserver" class="hover:text-white">Gameserver</a></li>
                        <li><a href="?route=domain" class="hover:text-white">Domains</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-semibold mb-4">Support</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="?route=contact" class="hover:text-white">Kontakt</a></li>
                        <li><a href="?route=tickets" class="hover:text-white">Tickets</a></li>
                        <li><a href="?route=status" class="hover:text-white">Server Status</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-semibold mb-4">Rechtliches</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="?route=impressum" class="hover:text-white">Impressum</a></li>
                        <li><a href="?route=privacy" class="hover:text-white">Datenschutz</a></li>
                        <li><a href="?route=terms" class="hover:text-white">AGB</a></li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-gray-700 mt-8 pt-8 text-center text-gray-400">
                <p>&copy; 2025 SpectraHost. Alle Rechte vorbehalten.</p>
            </div>
        </div>
    </footer>
</body>
</html>