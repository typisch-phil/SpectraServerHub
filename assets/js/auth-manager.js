/**
 * Moderne Authentication Manager für SpectraHost
 * Verwaltet Login, Logout und Benutzerstatus
 */
class AuthManager {
    constructor() {
        this.baseUrl = '/api';
        this.currentUser = null;
        this.isAuthenticated = false;
        this.init();
    }
    
    async init() {
        await this.checkAuthStatus();
        this.setupEventListeners();
    }
    
    /**
     * Aktuellen Authentication-Status prüfen
     */
    async checkAuthStatus() {
        try {
            const response = await fetch(`${this.baseUrl}/user-status`);
            const data = await response.json();
            
            if (data.success && data.isLoggedIn) {
                this.currentUser = data.user;
                this.isAuthenticated = true;
                this.updateUI(true);
            } else {
                this.currentUser = null;
                this.isAuthenticated = false;
                this.updateUI(false);
            }
        } catch (error) {
            console.error('Auth status check failed:', error);
            this.isAuthenticated = false;
            this.updateUI(false);
        }
    }
    
    /**
     * Benutzer anmelden
     */
    async login(email, password) {
        try {
            const response = await fetch(`${this.baseUrl}/login-new`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ email, password })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.currentUser = data.user;
                this.isAuthenticated = true;
                this.updateUI(true);
                this.showMessage('Anmeldung erfolgreich', 'success');
                
                // Redirect to dashboard
                setTimeout(() => {
                    window.location.href = '/dashboard';
                }, 1000);
                
                return { success: true, user: data.user };
            } else {
                this.showMessage(data.error || 'Anmeldung fehlgeschlagen', 'error');
                return { success: false, error: data.error };
            }
        } catch (error) {
            console.error('Login error:', error);
            this.showMessage('Verbindungsfehler', 'error');
            return { success: false, error: 'Verbindungsfehler' };
        }
    }
    
    /**
     * Benutzer registrieren
     */
    async register(email, password, firstName, lastName, phone = '') {
        try {
            const response = await fetch(`${this.baseUrl}/register-new`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    email,
                    password,
                    firstName,
                    lastName,
                    phone: phone || null
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showMessage('Registrierung erfolgreich! Sie können sich jetzt anmelden.', 'success');
                
                // Redirect to login
                setTimeout(() => {
                    window.location.href = '/login';
                }, 2000);
                
                return { success: true, userId: data.user_id };
            } else {
                this.showMessage(data.error || 'Registrierung fehlgeschlagen', 'error');
                return { success: false, error: data.error };
            }
        } catch (error) {
            console.error('Registration error:', error);
            this.showMessage('Verbindungsfehler', 'error');
            return { success: false, error: 'Verbindungsfehler' };
        }
    }
    
    /**
     * Benutzer abmelden
     */
    async logout() {
        try {
            await fetch(`${this.baseUrl}/logout`, {
                method: 'POST'
            });
            
            this.currentUser = null;
            this.isAuthenticated = false;
            this.updateUI(false);
            this.showMessage('Erfolgreich abgemeldet', 'success');
            
            // Redirect to home
            setTimeout(() => {
                window.location.href = '/';
            }, 1000);
            
        } catch (error) {
            console.error('Logout error:', error);
        }
    }
    
    /**
     * UI basierend auf Authentication-Status aktualisieren
     */
    updateUI(isLoggedIn) {
        // Navigation aktualisieren
        const loginBtn = document.getElementById('loginBtn');
        const registerBtn = document.getElementById('registerBtn');
        const userMenu = document.getElementById('userMenu');
        const dashboardBtn = document.getElementById('dashboardBtn');
        
        if (isLoggedIn && this.currentUser) {
            // Benutzer ist angemeldet
            if (loginBtn) loginBtn.style.display = 'none';
            if (registerBtn) registerBtn.style.display = 'none';
            
            if (userMenu) {
                userMenu.style.display = 'block';
                const userName = userMenu.querySelector('.user-name');
                const userBalance = userMenu.querySelector('.user-balance');
                
                if (userName) userName.textContent = this.currentUser.name;
                if (userBalance) userBalance.textContent = `${this.currentUser.balance.toFixed(2)} €`;
            }
            
            if (dashboardBtn) dashboardBtn.style.display = 'block';
            
        } else {
            // Benutzer ist nicht angemeldet
            if (loginBtn) loginBtn.style.display = 'block';
            if (registerBtn) registerBtn.style.display = 'block';
            if (userMenu) userMenu.style.display = 'none';
            if (dashboardBtn) dashboardBtn.style.display = 'none';
        }
        
        // Protected content zeigen/verstecken
        const protectedElements = document.querySelectorAll('[data-auth-required]');
        protectedElements.forEach(element => {
            element.style.display = isLoggedIn ? 'block' : 'none';
        });
        
        const publicElements = document.querySelectorAll('[data-auth-public]');
        publicElements.forEach(element => {
            element.style.display = isLoggedIn ? 'none' : 'block';
        });
    }
    
    /**
     * Event Listeners einrichten
     */
    setupEventListeners() {
        // Login Form
        const loginForm = document.getElementById('loginForm');
        if (loginForm) {
            loginForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const formData = new FormData(loginForm);
                await this.login(formData.get('email'), formData.get('password'));
            });
        }
        
        // Register Form
        const registerForm = document.getElementById('registerForm');
        if (registerForm) {
            registerForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const formData = new FormData(registerForm);
                await this.register(
                    formData.get('email'),
                    formData.get('password'),
                    formData.get('firstName'),
                    formData.get('lastName'),
                    formData.get('phone')
                );
            });
        }
        
        // Logout Button
        const logoutBtn = document.getElementById('logoutBtn');
        if (logoutBtn) {
            logoutBtn.addEventListener('click', async (e) => {
                e.preventDefault();
                await this.logout();
            });
        }
    }
    
    /**
     * Nachricht anzeigen
     */
    showMessage(message, type = 'info') {
        // Entferne bestehende Nachrichten
        const existingMessages = document.querySelectorAll('.auth-message');
        existingMessages.forEach(msg => msg.remove());
        
        // Erstelle neue Nachricht
        const messageDiv = document.createElement('div');
        messageDiv.className = `auth-message alert alert-${type} fixed top-4 right-4 z-50 max-w-md`;
        messageDiv.innerHTML = `
            <div class="flex items-center justify-between">
                <span>${message}</span>
                <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-xl">&times;</button>
            </div>
        `;
        
        document.body.appendChild(messageDiv);
        
        // Automatisch entfernen nach 5 Sekunden
        setTimeout(() => {
            if (messageDiv.parentNode) {
                messageDiv.remove();
            }
        }, 5000);
    }
    
    /**
     * Prüfen ob Benutzer bestimmte Rolle hat
     */
    hasRole(role) {
        return this.isAuthenticated && this.currentUser && this.currentUser.role === role;
    }
    
    /**
     * Geschützte Seite überprüfen
     */
    requireAuth() {
        if (!this.isAuthenticated) {
            this.showMessage('Sie müssen angemeldet sein, um diese Seite zu besuchen', 'error');
            setTimeout(() => {
                window.location.href = '/login';
            }, 2000);
            return false;
        }
        return true;
    }
}

// Globale Instanz erstellen
window.authManager = new AuthManager();

// CSS für Nachrichten hinzufügen
const alertStyles = `
<style>
.alert {
    padding: 12px 16px;
    border-radius: 6px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    animation: slideIn 0.3s ease-out;
}

.alert-success {
    background-color: #10b981;
    color: white;
}

.alert-error {
    background-color: #ef4444;
    color: white;
}

.alert-info {
    background-color: #3b82f6;
    color: white;
}

@keyframes slideIn {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}
</style>
`;

document.head.insertAdjacentHTML('beforeend', alertStyles);