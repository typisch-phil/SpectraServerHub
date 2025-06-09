<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/layout.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}

$database = Database::getInstance();
$stmt = $database->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user || $user['role'] !== 'admin') {
    header('Location: /dashboard');
    exit;
}

// Get all users
$stmt = $database->prepare("SELECT * FROM users ORDER BY created_at DESC");
$stmt->execute();
$users = $stmt->fetchAll();

$title = 'Benutzerverwaltung - SpectraHost Admin';
$description = 'Verwalten Sie Benutzerkonten und Zugriffsrechte';
renderHeader($title, $description);
?>

<div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <!-- Admin Navigation -->
    <nav class="bg-white dark:bg-gray-800 shadow">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="/" class="text-xl font-bold text-blue-600 dark:text-blue-400">SpectraHost</a>
                    <div class="ml-8 flex space-x-4">
                        <a href="/admin" class="text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">Admin Panel</a>
                        <a href="/admin/users" class="text-blue-600 dark:text-blue-400 font-medium border-b-2 border-blue-600 pb-1">Benutzer</a>
                        <a href="/admin/tickets" class="text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">Tickets</a>
                        <a href="/admin/services" class="text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">Services</a>
                        <a href="/admin/invoices" class="text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">Rechnungen</a>
                        <a href="/admin/integrations" class="text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">Integrationen</a>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="/dashboard" class="text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">
                        <i class="fas fa-user mr-2"></i>Zum Dashboard
                    </a>
                    <button onclick="logout()" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg transition-colors">
                        <i class="fas fa-sign-out-alt mr-1"></i>Abmelden
                    </button>
                    
                    <!-- Theme Toggle -->
                    <button id="theme-toggle" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                        <i class="fas fa-moon dark:hidden"></i>
                        <i class="fas fa-sun hidden dark:inline"></i>
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="mb-8 flex justify-between items-start">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Benutzerverwaltung</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-2">Verwalten Sie Benutzerkonten und Zugriffsrechte</p>
            </div>
            <button onclick="openCreateUserModal()" class="btn-primary">
                <i class="fas fa-plus mr-2"></i>Neuen Benutzer erstellen
            </button>
        </div>

        <!-- Search and Filter Bar -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow mb-6 p-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Suchen</label>
                    <input type="text" id="userSearch" placeholder="Name oder E-Mail..." 
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Rolle filtern</label>
                    <select id="roleFilter" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                        <option value="">Alle Rollen</option>
                        <option value="customer">Kunde</option>
                        <option value="admin">Administrator</option>
                    </select>
                </div>
                <div class="flex items-end">
                    <button onclick="filterUsers()" class="btn-primary mr-2">
                        <i class="fas fa-search mr-1"></i>Filtern
                    </button>
                    <button onclick="resetFilters()" class="btn-outline">
                        <i class="fas fa-times mr-1"></i>Zurücksetzen
                    </button>
                </div>
            </div>
        </div>

        <!-- Users Table -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Alle Benutzer</h2>
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    <span id="usersCount">Lade...</span> Benutzer gefunden
                </div>
            </div>
                
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">E-Mail</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Rolle</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Guthaben</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Erstellt</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Aktionen</th>
                        </tr>
                    </thead>
                    <tbody id="usersTableBody" class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center">
                                <div class="loading mx-auto mb-4"></div>
                                <p class="text-gray-500 dark:text-gray-400">Benutzer werden geladen...</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Create/Edit User Modal -->
<div id="userModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full max-h-screen overflow-y-auto">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h2 id="modalTitle" class="text-xl font-semibold text-gray-900 dark:text-white">Benutzer erstellen</h2>
            </div>
            
            <form id="userForm" class="p-6 space-y-4">
                <input type="hidden" id="userId" name="id">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Vorname</label>
                        <input type="text" id="firstName" name="first_name" 
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Nachname</label>
                        <input type="text" id="lastName" name="last_name" 
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">E-Mail-Adresse *</label>
                    <input type="email" id="email" name="email" required 
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Passwort <span id="passwordNote" class="text-sm text-gray-500">(leer lassen um nicht zu ändern)</span></label>
                    <input type="password" id="password" name="password" 
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Rolle</label>
                        <select id="role" name="role" 
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                            <option value="customer">Kunde</option>
                            <option value="admin">Administrator</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Guthaben (€)</label>
                        <input type="number" id="balance" name="balance" step="0.01" min="0" value="0.00"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200 dark:border-gray-700">
                    <button type="button" onclick="closeUserModal()" class="btn-outline">Abbrechen</button>
                    <button type="submit" id="submitButton" class="btn-primary">Erstellen</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Confirmation Modal -->
<div id="confirmModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full">
            <div class="p-6">
                <div class="flex items-center mb-4">
                    <div class="flex-shrink-0 w-10 h-10 bg-red-100 dark:bg-red-900 rounded-full flex items-center justify-center">
                        <i class="fas fa-exclamation-triangle text-red-600 dark:text-red-400"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Benutzer löschen</h3>
                    </div>
                </div>
                <div class="mb-6">
                    <p class="text-sm text-gray-600 dark:text-gray-400" id="confirmMessage">
                        Sind Sie sicher, dass Sie diesen Benutzer löschen möchten? Diese Aktion kann nicht rückgängig gemacht werden.
                    </p>
                </div>
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeConfirmModal()" class="btn-outline">Abbrechen</button>
                    <button type="button" onclick="confirmDelete()" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg transition-colors">Löschen</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let currentUserId = null;
let users = [];

// Load users on page load
document.addEventListener('DOMContentLoaded', function() {
    loadUsers();
});

// API request helper
async function apiRequest(url, method = 'GET', data = null) {
    const options = {
        method: method,
        credentials: 'include',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        }
    };
    
    if (data && method !== 'GET') {
        options.body = JSON.stringify(data);
    }
    
    const response = await fetch(url, options);
    
    if (!response.ok) {
        const errorText = await response.text();
        throw new Error(`HTTP ${response.status}: ${errorText}`);
    }
    
    return await response.json();
}

// Load all users
async function loadUsers() {
    try {
        const response = await apiRequest('/api/admin/users.php');
        users = response.users || [];
        renderUsers(users);
        updateUsersCount(users.length);
    } catch (error) {
        console.error('Error loading users:', error);
        showError('Fehler beim Laden der Benutzer: ' + error.message);
    }
}

// Render users table
function renderUsers(usersToRender) {
    const tbody = document.getElementById('usersTableBody');
    
    if (usersToRender.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="px-6 py-8 text-center">
                    <div class="text-gray-500 dark:text-gray-400">
                        <i class="fas fa-users text-4xl mb-4"></i>
                        <p>Keine Benutzer gefunden</p>
                    </div>
                </td>
            </tr>
        `;
        return;
    }
    
    tbody.innerHTML = usersToRender.map(user => {
        const fullName = [user.first_name, user.last_name].filter(n => n && n.trim()).join(' ') || 'Unbekannt';
        const roleClass = user.role === 'admin' 
            ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' 
            : 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
        
        return `
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-300">${user.id}</td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-medium text-gray-900 dark:text-white">${escapeHtml(fullName)}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-300">${escapeHtml(user.email)}</td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${roleClass}">
                        ${user.role === 'admin' ? 'Administrator' : 'Kunde'}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-300">€${parseFloat(user.balance || 0).toFixed(2)}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-300">${formatDate(user.created_at)}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                    <div class="flex space-x-2">
                        <button onclick="editUser(${user.id})" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 p-1" title="Bearbeiten">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button onclick="adjustBalance(${user.id})" class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300 p-1" title="Guthaben anpassen">
                            <i class="fas fa-wallet"></i>
                        </button>
                        ${user.role !== 'admin' ? `
                        <button onclick="deleteUser(${user.id})" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 p-1" title="Löschen">
                            <i class="fas fa-trash"></i>
                        </button>
                        ` : ''}
                    </div>
                </td>
            </tr>
        `;
    }).join('');
}

// Update users count
function updateUsersCount(count) {
    document.getElementById('usersCount').textContent = count;
}

// Open create user modal
function openCreateUserModal() {
    document.getElementById('modalTitle').textContent = 'Neuen Benutzer erstellen';
    document.getElementById('submitButton').textContent = 'Erstellen';
    document.getElementById('passwordNote').style.display = 'none';
    document.getElementById('userForm').reset();
    document.getElementById('userId').value = '';
    document.getElementById('password').required = true;
    document.getElementById('userModal').classList.remove('hidden');
}

// Edit user
async function editUser(userId) {
    try {
        const response = await apiRequest(`/api/admin/users.php?id=${userId}`);
        const user = response.user;
        
        document.getElementById('modalTitle').textContent = 'Benutzer bearbeiten';
        document.getElementById('submitButton').textContent = 'Aktualisieren';
        document.getElementById('passwordNote').style.display = 'inline';
        document.getElementById('password').required = false;
        
        // Fill form
        document.getElementById('userId').value = user.id;
        document.getElementById('firstName').value = user.first_name || '';
        document.getElementById('lastName').value = user.last_name || '';
        document.getElementById('email').value = user.email;
        document.getElementById('role').value = user.role;
        document.getElementById('balance').value = parseFloat(user.balance || 0).toFixed(2);
        document.getElementById('password').value = '';
        
        document.getElementById('userModal').classList.remove('hidden');
    } catch (error) {
        console.error('Error loading user:', error);
        showError('Fehler beim Laden des Benutzers: ' + error.message);
    }
}

// Close user modal
function closeUserModal() {
    document.getElementById('userModal').classList.add('hidden');
}

// Handle user form submission
document.getElementById('userForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const userData = {};
    
    for (let [key, value] of formData.entries()) {
        if (value.trim() !== '') {
            userData[key] = value;
        }
    }
    
    try {
        const isEdit = userData.id && userData.id !== '';
        const method = isEdit ? 'PUT' : 'POST';
        const url = '/api/admin/users.php';
        
        const response = await apiRequest(url, method, userData);
        
        closeUserModal();
        showSuccess(response.message || 'Benutzer erfolgreich gespeichert');
        await loadUsers();
        
    } catch (error) {
        console.error('Error saving user:', error);
        showError('Fehler beim Speichern: ' + error.message);
    }
});

// Delete user
function deleteUser(userId) {
    currentUserId = userId;
    const user = users.find(u => u.id === userId);
    const userName = [user.first_name, user.last_name].filter(n => n && n.trim()).join(' ') || user.email;
    
    document.getElementById('confirmMessage').textContent = 
        `Sind Sie sicher, dass Sie den Benutzer "${userName}" löschen möchten? Diese Aktion kann nicht rückgängig gemacht werden.`;
    
    document.getElementById('confirmModal').classList.remove('hidden');
}

// Confirm delete
async function confirmDelete() {
    if (!currentUserId) return;
    
    try {
        await apiRequest('/api/admin/users.php', 'DELETE', { id: currentUserId });
        closeConfirmModal();
        showSuccess('Benutzer erfolgreich gelöscht');
        await loadUsers();
    } catch (error) {
        console.error('Error deleting user:', error);
        showError('Fehler beim Löschen: ' + error.message);
    }
}

// Close confirm modal
function closeConfirmModal() {
    document.getElementById('confirmModal').classList.add('hidden');
    currentUserId = null;
}

// Adjust balance (simple implementation)
function adjustBalance(userId) {
    const user = users.find(u => u.id === userId);
    const newBalance = prompt(`Neues Guthaben für ${user.email}:`, user.balance || '0.00');
    
    if (newBalance !== null && !isNaN(parseFloat(newBalance))) {
        updateUser(userId, { balance: parseFloat(newBalance).toFixed(2) });
    }
}

// Update user helper
async function updateUser(userId, data) {
    try {
        data.id = userId;
        await apiRequest('/api/admin/users.php', 'PUT', data);
        showSuccess('Benutzer erfolgreich aktualisiert');
        await loadUsers();
    } catch (error) {
        console.error('Error updating user:', error);
        showError('Fehler beim Aktualisieren: ' + error.message);
    }
}

// Filter users
async function filterUsers() {
    const search = document.getElementById('userSearch').value.trim();
    const role = document.getElementById('roleFilter').value;
    
    try {
        const params = new URLSearchParams();
        if (search) params.append('search', search);
        if (role) params.append('role', role);
        
        const url = '/api/admin/users.php' + (params.toString() ? '?' + params.toString() : '');
        const response = await apiRequest(url);
        
        users = response.users || [];
        renderUsers(users);
        updateUsersCount(users.length);
    } catch (error) {
        console.error('Error filtering users:', error);
        showError('Fehler beim Filtern: ' + error.message);
    }
}

// Reset filters
function resetFilters() {
    document.getElementById('userSearch').value = '';
    document.getElementById('roleFilter').value = '';
    loadUsers();
}

// Utility functions
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('de-DE');
}

function showSuccess(message) {
    // Simple implementation - can be enhanced with a proper toast system
    alert('Erfolg: ' + message);
}

function showError(message) {
    // Simple implementation - can be enhanced with a proper toast system
    alert('Fehler: ' + message);
}

// Add real-time search
document.getElementById('userSearch').addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase().trim();
    
    if (searchTerm === '') {
        renderUsers(users);
        updateUsersCount(users.length);
        return;
    }
    
    const filteredUsers = users.filter(user => {
        const fullName = [user.first_name, user.last_name].filter(n => n && n.trim()).join(' ').toLowerCase();
        const email = user.email.toLowerCase();
        
        return fullName.includes(searchTerm) || email.includes(searchTerm);
    });
    
    renderUsers(filteredUsers);
    updateUsersCount(filteredUsers.length);
});

</script>

<?php renderFooter(); ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            </div>
        </div>
    </div>

    <script>
        function editUser(userId) {
            // TODO: Implement user editing
            alert('Benutzer bearbeiten: ' + userId);
        }

        function deleteUser(userId) {
            if (confirm('Sind Sie sicher, dass Sie diesen Benutzer löschen möchten?')) {
                // TODO: Implement user deletion
                alert('Benutzer löschen: ' + userId);
            }
        }

        function logout() {
            window.location.href = '/api/logout';
        }

        // Theme toggle functionality
        function toggleTheme() {
            const html = document.documentElement;
            const currentTheme = html.classList.contains('dark') ? 'dark' : 'light';
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            html.classList.remove('dark', 'light');
            html.classList.add(newTheme);
            
            localStorage.setItem('theme', newTheme);
            updateThemeIcon();
        }

        function updateThemeIcon() {
            const themeToggle = document.getElementById('theme-toggle');
            const isDark = document.documentElement.classList.contains('dark');
            themeToggle.innerHTML = isDark 
                ? '<i class="fas fa-sun text-yellow-500"></i>'
                : '<i class="fas fa-moon text-gray-600"></i>';
        }

        // Initialize theme on page load
        document.addEventListener('DOMContentLoaded', function() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.classList.add(savedTheme);
            updateThemeIcon();
            
            // Add event listener to theme toggle button
            const themeToggle = document.getElementById('theme-toggle');
            if (themeToggle) {
                themeToggle.addEventListener('click', toggleTheme);
            }
        });
    </script>
</div>

<?php renderFooter(); ?>
</body>
</html>