<?php
require_once '../../includes/session.php';
requireLogin();

$amount = $_GET['amount'] ?? '10.00';
$description = $_GET['description'] ?? 'SpectraHost Service';
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demo Zahlung - SpectraHost</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div>
                <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                    Demo Zahlung
                </h2>
                <p class="mt-2 text-center text-sm text-gray-600">
                    Simulierte Mollie-Zahlung für Entwicklung
                </p>
            </div>
            
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="space-y-4">
                    <div class="border-b pb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Zahlungsdetails</h3>
                    </div>
                    
                    <div class="flex justify-between">
                        <span class="text-gray-600">Beschreibung:</span>
                        <span class="font-medium"><?= htmlspecialchars($description) ?></span>
                    </div>
                    
                    <div class="flex justify-between">
                        <span class="text-gray-600">Betrag:</span>
                        <span class="font-medium text-green-600">€<?= htmlspecialchars($amount) ?></span>
                    </div>
                    
                    <div class="border-t pt-4">
                        <h4 class="text-md font-semibold text-gray-900 mb-3">Zahlungsmethode wählen</h4>
                        
                        <div class="space-y-2">
                            <button onclick="processPayment('ideal')" class="w-full flex items-center justify-between p-3 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                                <div class="flex items-center">
                                    <i class="fas fa-university text-blue-600 mr-3"></i>
                                    <span>iDEAL</span>
                                </div>
                                <i class="fas fa-chevron-right text-gray-400"></i>
                            </button>
                            
                            <button onclick="processPayment('creditcard')" class="w-full flex items-center justify-between p-3 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                                <div class="flex items-center">
                                    <i class="fas fa-credit-card text-green-600 mr-3"></i>
                                    <span>Kreditkarte</span>
                                </div>
                                <i class="fas fa-chevron-right text-gray-400"></i>
                            </button>
                            
                            <button onclick="processPayment('paypal')" class="w-full flex items-center justify-between p-3 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                                <div class="flex items-center">
                                    <i class="fab fa-paypal text-blue-500 mr-3"></i>
                                    <span>PayPal</span>
                                </div>
                                <i class="fas fa-chevron-right text-gray-400"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mt-6">
                        <div class="flex">
                            <i class="fas fa-info-circle text-yellow-600 mr-2 mt-0.5"></i>
                            <div class="text-sm text-yellow-800">
                                <strong>Demo-Modus:</strong> Dies ist eine Simulation. Es wird keine echte Zahlung verarbeitet.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function processPayment(method) {
            // Simuliere Zahlungsverarbeitung
            const loadingBtn = event.target;
            const originalContent = loadingBtn.innerHTML;
            
            loadingBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Verarbeitung...';
            loadingBtn.disabled = true;
            
            setTimeout(() => {
                // Simuliere erfolgreiche Zahlung
                alert(`Demo-Zahlung von €<?= $amount ?> über ${method} erfolgreich!`);
                
                // Weiterleitung zurück zum Dashboard
                window.location.href = '/dashboard';
            }, 2000);
        }
    </script>
</body>
</html>