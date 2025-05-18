<?php
// Disable error display to prevent stray output
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(0);

// Clear any existing output buffers
while (ob_get_level() > 0) {
    ob_end_clean();
}
ob_start();

// Set response header to JSON
header('Content-Type: application/json; charset=UTF-8');

// Return a simple JSON response
$response = ['status' => 200, 'message' => 'Test Function is working'];
ob_end_clean();
echo json_encode($response);
exit;

// Clear any stray output
ob_end_clean();
?>