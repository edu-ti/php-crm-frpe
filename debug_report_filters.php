<?php
// debug_report_filters.php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/api/handlers/report_handler.php';

// Mock $_GET and test handle_get_report_data
// Scenario 1: Sales Report, Default Date (simulating user changing date only)
// 2024-02-01 to 2024-02-29
$_GET = [
    'report_type' => 'sales',
    'start_date' => '2024-02-01',
    'end_date' => '2024-02-29',
    'supplier_id' => '', // Empty string simulating "All"
    'user_id' => '',
    'origem' => '',
    'uf' => ''
];

echo "Testing Scenario 1: Sales Report (Date only)...\n";
ob_start();
handle_get_report_data($pdo, $_GET);
$output = ob_get_clean();
echo "Response Length: " . strlen($output) . "\n";
// Decode JSON to check success/data count
$json = json_decode($output, true);
if ($json) {
    echo "Success: " . ($json['success'] ? 'Yes' : 'No') . "\n";
    if (isset($json['report_data'])) {
        if (is_array($json['report_data'])) {
            // Sales report returns object grouped by supplier_id?
            // get_sales_report returns array [ fid => [rows=>...], ... ]
            echo "Data Count: " . count($json['report_data']) . "\n";
        } else {
            echo "Data Type: " . gettype($json['report_data']) . "\n";
        }
    } else {
        echo "No 'report_data' key.\n";
    }
    if (isset($json['error']))
        echo "Error: " . $json['error'] . "\n";
} else {
    echo "Invalid JSON response.\n";
    echo substr($output, 0, 200) . "...\n";
}

// Scenario 2: Sales Report with Supplier Filter (ID 1)
$_GET['supplier_id'] = '1';
echo "\nTesting Scenario 2: Sales Report (Supplier ID 1)...\n";
ob_start();
handle_get_report_data($pdo, $_GET);
$output = ob_get_clean();
$json = json_decode($output, true);
if ($json) {
    echo "Success: " . ($json['success'] ? 'Yes' : 'No') . "\n";
    if (isset($json['report_data']))
        echo "Data Count: " . count($json['report_data']) . "\n";
}

// Scenario 3: Lost Reasons (should allow filters)
$_GET['report_type'] = 'lost_reasons';
echo "\nTesting Scenario 3: Lost Reasons...\n";
ob_start();
handle_get_report_data($pdo, $_GET);
$output = ob_get_clean();
$json = json_decode($output, true);
if ($json) {
    echo "Success: " . ($json['success'] ? 'Yes' : 'No') . "\n";
    if (isset($json['report_data'])) {
        echo "Summary Count: " . (isset($json['report_data']['summary']) ? count($json['report_data']['summary']) : 0) . "\n";
    }
}
?>