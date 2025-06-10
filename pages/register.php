<?php
require_once __DIR__ . '/../includes/layout.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: /dashboard');
    exit;
}

renderHeader('Registrieren - SpectraHost');
?>

<div class="min-h-screen bg-gray-50 dark:bg-gray-900 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
        <div class="flex justify-center">
            <div class="w-12 h-12 bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg flex items-center justify-center">
                <span class="text-white font-bold text-xl">S</span>
            </div>
        </div>
        <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900 dark:text-white">
            Neues Konto erstellen
        </h2>
        <p class="mt-2 text-center text-sm text-gray-600 dark:text-gray-400">
            Oder
            <a href="/login" class="font-medium text-blue-600 hover:text-blue-500">
                melden Sie sich mit Ihrem bestehenden Konto an
            </a>
        </p>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
        <div class="bg-white dark:bg-gray-800 py-8 px-4 shadow sm:rounded-lg sm:px-10">
            <form id="registerForm" class="space-y-6">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="firstName" class="form-label">Vorname</label>
                        <input id="firstName" name="firstName" type="text" required class="form-input">
                    </div>
                    <div>
                        <label for="lastName" class="form-label">Nachname</label>
                        <input id="lastName" name="lastName" type="text" required class="form-input">
                    </div>
                </div>

                <div>
                    <label for="email" class="form-label">E-Mail-Adresse</label>
                    <input id="email" name="email" type="email" autocomplete="email" required class="form-input">
                </div>

                <div>
                    <label for="password" class="form-label">Passwort</label>
                    <input id="password" name="password" type="password" autocomplete="new-password" required class="form-input">
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Mindestens 8 Zeichen</p>
                </div>

                <div>
                    <label for="confirmPassword" class="form-label">Passwort bestätigen</label>
                    <input id="confirmPassword" name="confirmPassword" type="password" required class="form-input">
                </div>

                <div class="flex items-center">
                    <input id="terms" name="terms" type="checkbox" required
                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                    <label for="terms" class="ml-2 block text-sm text-gray-900 dark:text-gray-300">
                        Ich akzeptiere die <a href="#" class="text-blue-600 hover:text-blue-500">AGB</a> und 
                        <a href="#" class="text-blue-600 hover:text-blue-500">Datenschutzerklärung</a>
                    </label>
                </div>

                <div>
                    <button type="submit" id="registerBtn" class="w-full btn-primary">
                        Registrieren
                    </button>
                </div>
            </form>

            <div id="message" class="mt-4 hidden"></div>
        </div>
    </div>
</div>

<script>
document.getElementById('registerForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const btn = document.getElementById('registerBtn');
    const messageDiv = document.getElementById('message');
    
    // Validation
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirmPassword').value;
    
    if (password !== confirmPassword) {
        messageDiv.className = 'mt-4 alert alert-error';
        messageDiv.textContent = 'Passwörter stimmen nicht überein';
        return;
    }
    
    if (password.length < 8) {
        messageDiv.className = 'mt-4 alert alert-error';
        messageDiv.textContent = 'Passwort muss mindestens 8 Zeichen lang sein';
        return;
    }
    
    setLoading(btn, true);
    messageDiv.className = 'mt-4 hidden';
    
    try {
        const formData = new FormData(this);
        const data = Object.fromEntries(formData);
        delete data.confirmPassword; // Remove confirm password from data
        
        const result = await apiRequest('/api/register', 'POST', data);
        
        messageDiv.className = 'mt-4 alert alert-success';
        messageDiv.textContent = result.message;
        
        // Redirect after success
        setTimeout(() => {
            window.location.href = '/dashboard';
        }, 1000);
        
    } catch (error) {
        messageDiv.className = 'mt-4 alert alert-error';
        messageDiv.textContent = error.message;
    }
    
    setLoading(btn, false);
});
</script>

<?php renderFooter(); ?>