<?php
session_start();
require_once 'includes/database.php';
require_once 'includes/auth.php';

// Authenticate user
$auth = new Auth($db);
$user = $auth->login('kunde@test.de', 'kunde123');

echo "Authenticated user: " . $user['email'] . "\n";

// Test ticket creation directly
$data = [
    'category' => 'technical',
    'priority' => 'medium',
    'subject' => 'Test Ticket from API',
    'message' => 'This is a test ticket message to verify the API functionality.'
];

// Simulate the API call
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['CONTENT_TYPE'] = 'application/json';

// Capture the API response
ob_start();

// Mock the JSON input
$GLOBALS['HTTP_RAW_POST_DATA'] = json_encode($data);

// Include the API endpoint
try {
    // Simulate POST data
    $json = json_encode($data);
    $temp = tmpfile();
    fwrite($temp, $json);
    rewind($temp);
    
    // Override php://input
    stream_wrapper_unregister("php");
    stream_wrapper_register("php", "MockInput");
    MockInput::$data = $json;
    
    include 'api/tickets.php';
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

$output = ob_get_clean();
echo "API Response: " . $output . "\n";

// Test fetching tickets
echo "\nFetching tickets:\n";
$_SERVER['REQUEST_METHOD'] = 'GET';
ob_start();
include 'api/tickets.php';
$ticketsOutput = ob_get_clean();
echo "Tickets Response: " . $ticketsOutput . "\n";

class MockInput {
    public static $data = '';
    
    public function stream_open($path, $mode, $options, &$opened_path) {
        return true;
    }
    
    public function stream_read($count) {
        $result = substr(self::$data, 0, $count);
        self::$data = substr(self::$data, $count);
        return $result;
    }
    
    public function stream_eof() {
        return empty(self::$data);
    }
    
    public function stream_stat() {
        return [];
    }
}
?>