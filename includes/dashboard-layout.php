<?php
function renderDashboardHeader($title = 'Dashboard - SpectraHost', $description = 'SpectraHost Dashboard - Verwalten Sie Ihre Services') {
    // Start session if not already started
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
?>
<!DOCTYPE html>
<html lang="de" class="scroll-smooth dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($description); ?>">
    <meta name="robots" content="noindex, nofollow">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'><defs><linearGradient id='g' x1='0' y1='0' x2='1' y2='1'><stop offset='0%' stop-color='%233b82f6'/><stop offset='100%' stop-color='%236366f1'/></linearGradient></defs><rect width='32' height='32' rx='6' fill='url(%23g)'/><text x='16' y='22' text-anchor='middle' fill='white' font-family='Arial' font-size='18' font-weight='bold'>S</text></svg>">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#eff6ff',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                        }
                    }
                }
            }
        }
    </script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-900 text-white min-h-screen">
<?php
}

function renderDashboardFooter() {
?>
    <script>
        // Mobile menu and navigation
        document.addEventListener('DOMContentLoaded', function() {
            // Update navigation based on login status
            updateNavigation();
            
            // Logout function
            window.logout = function() {
                fetch('/api/logout.php', {
                    method: 'POST',
                    credentials: 'same-origin'
                }).then(() => {
                    window.location.href = '/';
                }).catch(() => {
                    window.location.href = '/api/logout.php';
                });
            };
        });
        
        // Check login status and update navigation
        async function updateNavigation() {
            try {
                const response = await fetch('/api/user/status.php');
                if (!response.ok) return;
                
                const data = await response.json();
                // Navigation updates for dashboard could go here
            } catch (error) {
                console.log('Could not check login status');
            }
        }

        // Mobile services dropdown
        function toggleMobileServices() {
            const menu = document.getElementById('mobile-services-menu');
            const icon = document.getElementById('mobile-services-icon');
            
            if (menu && icon) {
                menu.classList.toggle('hidden');
                icon.classList.toggle('rotate-180');
            }
        }
    </script>
</body>
</html>
<?php
}
?>