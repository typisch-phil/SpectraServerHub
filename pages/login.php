<?php
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: /dashboard');
    exit;
}

renderHeader('Anmelden - SpectraHost');
?>

<div class="min-h-screen bg-gray-50 dark:bg-gray-900 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
        <div class="flex justify-center">
            <div class="w-12 h-12 bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg flex items-center justify-center">
                <span class="text-white font-bold text-xl">S</span>
            </div>
        </div>
        <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900 dark:text-white">
            Bei Ihrem Konto anmelden
        </h2>
        <p class="mt-2 text-center text-sm text-gray-600 dark:text-gray-400">
            Oder
            <a href="/register" class="font-medium text-blue-600 hover:text-blue-500">
                erstellen Sie ein neues Konto
            </a>
        </p>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
        <div class="bg-white dark:bg-gray-800 py-8 px-4 shadow sm:rounded-lg sm:px-10">
            <form id="loginForm" class="space-y-6">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        E-Mail-Adresse
                    </label>
                    <div class="mt-1">
                        <input id="email" name="email" type="email" autocomplete="email" required
                               class="appearance-none block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white sm:text-sm">
                    </div>
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Passwort
                    </label>
                    <div class="mt-1">
                        <input id="password" name="password" type="password" autocomplete="current-password" required
                               class="appearance-none block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white sm:text-sm">
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <input id="remember-me" name="remember-me" type="checkbox"
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="remember-me" class="ml-2 block text-sm text-gray-900 dark:text-gray-300">
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
                    <button type="submit" id="loginBtn"
                            class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50">
                        Anmelden
                    </button>
                </div>
            </form>

            <div id="message" class="mt-4 hidden"></div>
        </div>
    </div>
</div>

<script>
document.getElementById('loginForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const btn = document.getElementById('loginBtn');
    const messageDiv = document.getElementById('message');
    
    btn.disabled = true;
    btn.textContent = 'Wird angemeldet...';
    messageDiv.className = 'mt-4 hidden';
    
    try {
        const formData = new FormData(this);
        const data = Object.fromEntries(formData);
        
        const response = await fetch('/api/login', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'Cache-Control': 'no-cache'
            },
            body: JSON.stringify(data)
        });
        
        if (!response.ok) {
            throw new Error(`Server error ${response.status}`);
        }
        
        // Get response as text first to debug
        const responseText = await response.text();
        
        // Clean response text (remove any BOM or whitespace)
        const cleanedText = responseText.trim().replace(/^\uFEFF/, '');
        
        if (!cleanedText) {
            throw new Error('Server returned empty response');
        }
        
        let result;
        try {
            result = JSON.parse(cleanedText);
        } catch (e) {
            console.error('JSON parse failed. Response text:', cleanedText);
            throw new Error('Invalid server response format');
        }
        
        if (result.success) {
            messageDiv.className = 'mt-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded';
            messageDiv.textContent = result.message;
            
            // Check user role and redirect appropriately
            const urlParams = new URLSearchParams(window.location.search);
            let redirect = urlParams.get('redirect');
            
            if (!redirect) {
                // Determine redirect based on user data from response
                if (result.user && result.user.email && result.user.email.includes('admin')) {
                    redirect = '/admin/dashboard';
                } else {
                    redirect = '/dashboard';
                }
            }
            
            setTimeout(() => {
                window.location.href = redirect;
            }, 500);
        } else {
            messageDiv.className = 'mt-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded';
            messageDiv.textContent = result.error;
        }
    } catch (error) {
        console.error('Login error:', error);
        messageDiv.className = 'mt-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded';
        
        if (error.message && error.message.includes('Failed to fetch')) {
            messageDiv.textContent = 'Verbindungsfehler. Bitte überprüfen Sie Ihre Internetverbindung.';
        } else if (error.message && error.message.includes('500')) {
            messageDiv.textContent = 'Server-Fehler. Bitte versuchen Sie es in einem Moment erneut.';
        } else {
            messageDiv.textContent = error.message || 'Ein Fehler ist aufgetreten. Bitte versuchen Sie es erneut.';
        }
    }
    
    btn.disabled = false;
    btn.textContent = 'Anmelden';
});
</script>

<?php renderFooter(); ?>