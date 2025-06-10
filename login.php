<?php
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/auth-system.php';

// Wenn bereits angemeldet, weiterleiten
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: /dashboard');
    exit;
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Anmelden - SpectraHost</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full space-y-8 p-8">
        <div class="text-center">
            <div class="w-16 h-16 bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg flex items-center justify-center mx-auto">
                <span class="text-white font-bold text-2xl">S</span>
            </div>
            <h2 class="mt-6 text-3xl font-extrabold text-gray-900">Bei SpectraHost anmelden</h2>
            <p class="mt-2 text-sm text-gray-600">
                Noch kein Konto? 
                <a href="/register.php" class="font-medium text-blue-600 hover:text-blue-500">Jetzt registrieren</a>
            </p>
        </div>

        <div class="bg-white rounded-lg shadow-md p-8">
            <form id="loginForm" class="space-y-6">
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                        E-Mail-Adresse
                    </label>
                    <input 
                        id="email" 
                        name="email" 
                        type="email" 
                        required 
                        placeholder="ihre@email.de"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    >
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                        Passwort
                    </label>
                    <input 
                        id="password" 
                        name="password" 
                        type="password" 
                        required 
                        placeholder="Ihr Passwort"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    >
                </div>

                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <input 
                            id="remember" 
                            name="remember" 
                            type="checkbox" 
                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                        >
                        <label for="remember" class="ml-2 block text-sm text-gray-900">
                            Angemeldet bleiben
                        </label>
                    </div>
                    <div class="text-sm">
                        <a href="#" class="font-medium text-blue-600 hover:text-blue-500">
                            Passwort vergessen?
                        </a>
                    </div>
                </div>

                <div>
                    <button 
                        type="submit" 
                        id="loginButton"
                        class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50"
                    >
                        <i class="fas fa-sign-in-alt mr-2"></i>
                        Anmelden
                    </button>
                </div>
            </form>

            <div id="message" class="mt-4 hidden p-4 rounded-md"></div>
        </div>

        <div class="text-center">
            <a href="/" class="text-sm text-gray-600 hover:text-gray-900">
                <i class="fas fa-arrow-left mr-1"></i>
                Zurück zur Startseite
            </a>
        </div>
    </div>

    <script>
    document.getElementById('loginForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const button = document.getElementById('loginButton');
        const messageDiv = document.getElementById('message');
        const email = document.getElementById('email').value;
        const password = document.getElementById('password').value;
        
        // Validation
        if (!email || !password) {
            showMessage('Bitte füllen Sie alle Felder aus.', 'error');
            return;
        }
        
        // Loading state
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Wird angemeldet...';
        messageDiv.className = 'mt-4 hidden';
        
        try {
            const response = await fetch('/api/login-new', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    email: email,
                    password: password
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                showMessage('Anmeldung erfolgreich! Sie werden weitergeleitet...', 'success');
                setTimeout(() => {
                    window.location.href = '/dashboard';
                }, 1500);
            } else {
                showMessage(result.error || 'Anmeldung fehlgeschlagen', 'error');
            }
        } catch (error) {
            console.error('Login error:', error);
            showMessage('Verbindungsfehler. Bitte versuchen Sie es erneut.', 'error');
        }
        
        // Reset button
        button.disabled = false;
        button.innerHTML = '<i class="fas fa-sign-in-alt mr-2"></i>Anmelden';
    });
    
    function showMessage(text, type) {
        const messageDiv = document.getElementById('message');
        messageDiv.className = `mt-4 p-4 rounded-md ${type === 'success' ? 'bg-green-100 border border-green-400 text-green-700' : 'bg-red-100 border border-red-400 text-red-700'}`;
        messageDiv.textContent = text;
    }
    </script>
</body>
</html>