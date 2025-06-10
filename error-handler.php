<?php
// Error Handler für Plesk-Kompatibilität
set_error_handler('custom_error_handler');
set_exception_handler('custom_exception_handler');
register_shutdown_function('fatal_error_handler');

function custom_error_handler($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return;
    }
    
    $error_msg = "PHP Error: [$severity] $message in $file on line $line";
    error_log($error_msg);
    
    if ($severity === E_ERROR || $severity === E_USER_ERROR) {
        http_response_code(500);
        if (headers_sent()) return;
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => 'Internal server error',
            'debug' => $error_msg
        ]);
        exit;
    }
}

function custom_exception_handler($exception) {
    $error_msg = "Uncaught Exception: " . $exception->getMessage() . " in " . $exception->getFile() . " on line " . $exception->getLine();
    error_log($error_msg);
    
    http_response_code(500);
    if (!headers_sent()) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => 'Internal server error',
            'debug' => $error_msg
        ]);
    }
    exit;
}

function fatal_error_handler() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        $error_msg = "Fatal Error: {$error['message']} in {$error['file']} on line {$error['line']}";
        error_log($error_msg);
        
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'Fatal error occurred',
                'debug' => $error_msg
            ]);
        }
    }
}
?>