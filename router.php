<?php
// Simple router for development server
$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$request_method = $_SERVER['REQUEST_METHOD'];

// Route API requests
if (strpos($request_uri, '/api/') === 0) {
    $api_file = '.' . $request_uri;
    if (file_exists($api_file)) {
        include $api_file;
        exit;
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'API endpoint not found']);
        exit;
    }
}

// Route static files (CSS, JS, images)
if (preg_match('/\.(css|js|png|jpg|jpeg|gif|ico|svg)$/', $request_uri)) {
    $file_path = '.' . $request_uri;
    if (file_exists($file_path)) {
        $mime_types = [
            'css' => 'text/css',
            'js' => 'application/javascript',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'ico' => 'image/x-icon',
            'svg' => 'image/svg+xml'
        ];
        
        $extension = pathinfo($file_path, PATHINFO_EXTENSION);
        $mime_type = $mime_types[$extension] ?? 'application/octet-stream';
        
        header('Content-Type: ' . $mime_type);
        readfile($file_path);
        exit;
    }
}

// Route page requests
$routes = [
    '/' => 'public/index.php',
    '/login' => 'pages/login.php',
    '/register' => 'pages/register.php',
    '/dashboard' => 'pages/dashboard/index.php',
    '/dashboard/services' => 'pages/dashboard/services.php',
    '/dashboard/billing' => 'pages/dashboard/billing.php',
    '/dashboard/support' => 'pages/dashboard/support.php',
    '/admin' => 'pages/admin/dashboard.php',
    '/admin/users' => 'pages/admin/users.php',
    '/admin/services' => 'pages/admin/services.php',
    '/admin/tickets' => 'pages/admin/tickets.php',
    '/admin/invoices' => 'pages/admin/invoices.php',
    '/admin/integrations' => 'pages/admin/integrations.php',
    '/admin/statistics' => 'pages/admin/statistics.php',
    '/ticket-detail' => 'pages/ticket-detail.php',
    '/logout' => 'pages/logout.php'
];

// Check for exact route match
if (isset($routes[$request_uri])) {
    $file_path = $routes[$request_uri];
    if (file_exists($file_path)) {
        // Change working directory to match the file location for proper includes
        $original_cwd = getcwd();
        $file_dir = dirname($file_path);
        if ($file_dir !== '.') {
            chdir($file_dir);
        }
        
        include basename($file_path);
        
        // Restore original working directory
        chdir($original_cwd);
        exit;
    }
}

// Handle 404
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Seite nicht gefunden | SpectraHost</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center">
        <div class="text-center">
            <h1 class="text-6xl font-bold text-gray-900 mb-4">404</h1>
            <p class="text-xl text-gray-600 mb-8">Die angeforderte Seite wurde nicht gefunden.</p>
            <a href="/" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors">
                Zur Startseite
            </a>
        </div>
    </div>
</body>
</html>