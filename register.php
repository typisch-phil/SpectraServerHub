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
    <title>Registrieren - SpectraHost</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full space-y-8 p-8">
        <div class="text-center">
            <div class="w-16 h-16 bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg flex items-center justify-center mx-auto">
                <span class="text-white font-bold text-2xl">S</span>
            </div>
            <h2 class="mt-6 text-3xl font-extrabold text-gray-900">Neues Konto erstellen</h2>
            <p class="mt-2 text-sm text-gray-600">
                Bereits ein Konto? 
                <a href="/login.php" class="font-medium text-blue-600 hover:text-blue-500">Jetzt anmelden</a>
            </p>
        </div>

        <div class="bg-white rounded-lg shadow-md p-8">
            <form id="registerForm" class="space-y-6">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="firstName" class="block text-sm font-medium text-gray-700 mb-2">
                            Vorname *
                        </label>
                        <input 
                            id="firstName" 
                            name="firstName" 
                            type="text" 
                            required 
                            placeholder="Max"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        >
                    </div>
                    <div>
                        <label for="lastName" class="block text-sm font-medium text-gray-700 mb-2">
                            Nachname *
                        </label>
                        <input 
                            id="lastName" 
                            name="lastName" 
                            type="text" 
                            required 
                            placeholder="Mustermann"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        >
                    </div>
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                        E-Mail-Adresse *
                    </label>
                    <input 
                        id="email" 
                        name="email" 
                        type="email" 
                        required 
                        placeholder="max@beispiel.de"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    >
                </div>

                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">
                        Telefonnummer (optional)
                    </label>
                    <input 
                        id="phone" 
                        name="phone" 
                        type="tel" 
                        placeholder="+49 123 456789"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    >
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                        Passwort *
                    </label>
                    <input 
                        id="password" 
                        name="password" 
                        type="password" 
                        required 
                        placeholder="Mindestens 8 Zeichen"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    >
                    <p class="mt-1 text-xs text-gray-500">Mindestens 8 Zeichen</p>
                </div>

                <div>
                    <label for="confirmPassword" class="block text-sm font-medium text-gray-700 mb-2">
                        Passwort bestätigen *
                    </label>
                    <input 
                        id="confirmPassword" 
                        name="confirmPassword" 
                        type="password" 
                        required 
                        placeholder="Passwort wiederholen"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    >
                </div>

                <div class="flex items-center">
                    <input 
                        id="terms" 
                        name="terms" 
                        type="checkbox" 
                        required
                        class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                    >
                    <label for="terms" class="ml-2 block text-sm text-gray-900">
                        Ich akzeptiere die <a href="#" class="text-blue-600 hover:text-blue-500">AGB</a> und 
                        <a href="#" class="text-blue-600 hover:text-blue-500">Datenschutzerklärung</a> *
                    </label>
                </div>

                <div>
                    <button 
                        type="submit" 
                        id="registerButton"
                        class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50"
                    >
                        <i class="fas fa-user-plus mr-2"></i>
                        Konto erstellen
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
    document.getElementById('registerForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const button = document.getElementById('registerButton');
        const messageDiv = document.getElementById('message');
        
        // Get form data
        const firstName = document.getElementById('firstName').value;
        const lastName = document.getElementById('lastName').value;
        const email = document.getElementById('email').value;
        const phone = document.getElementById('phone').value;
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirmPassword').value;
        const terms = document.getElementById('terms').checked;
        
        // Validation
        if (!firstName || !lastName || !email || !password || !confirmPassword) {
            showMessage('Bitte füllen Sie alle Pflichtfelder aus.', 'error');
            return;
        }
        
        if (password !== confirmPassword) {
            showMessage('Die Passwörter stimmen nicht überein.', 'error');
            return;
        }
        
        if (password.length < 8) {
            showMessage('Das Passwort muss mindestens 8 Zeichen lang sein.', 'error');
            return;
        }
        
        if (!terms) {
            showMessage('Bitte akzeptieren Sie die AGB und Datenschutzerklärung.', 'error');
            return;
        }
        
        // Loading state
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Wird registriert...';
        messageDiv.className = 'mt-4 hidden';
        
        try {
            const response = await fetch('/api/register-new', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    firstName: firstName,
                    lastName: lastName,
                    email: email,
                    phone: phone || null,
                    password: password
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                showMessage('Registrierung erfolgreich! Sie werden zur Anmeldung weitergeleitet...', 'success');
                setTimeout(() => {
                    window.location.href = '/login.php';
                }, 2000);
            } else {
                showMessage(result.error || 'Registrierung fehlgeschlagen', 'error');
            }
        } catch (error) {
            console.error('Registration error:', error);
            showMessage('Verbindungsfehler. Bitte versuchen Sie es erneut.', 'error');
        }
        
        // Reset button
        button.disabled = false;
        button.innerHTML = '<i class="fas fa-user-plus mr-2"></i>Konto erstellen';
    });
    
    function showMessage(text, type) {
        const messageDiv = document.getElementById('message');
        messageDiv.className = `mt-4 p-4 rounded-md ${type === 'success' ? 'bg-green-100 border border-green-400 text-green-700' : 'bg-red-100 border border-red-400 text-red-700'}`;
        messageDiv.textContent = text;
    }
    </script>
</body>
</html>