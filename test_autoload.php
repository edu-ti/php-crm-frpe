<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "Test Start<br>";
echo "Current Dir: " . __DIR__ . "<br>";

$autoloadPath = __DIR__ . '/vendor/autoload.php';
echo "Autoload Path: " . $autoloadPath . "<br>";

if (file_exists($autoloadPath)) {
    echo "Autoload file exists.<br>";
    require_once $autoloadPath;
    echo "Autoload required.<br>";
} else {
    echo "Autoload file NOT FOUND.<br>";
}

if (class_exists('\SendGrid')) {
    echo "Class \SendGrid exists.<br>";
} else {
    echo "Class \SendGrid DOES NOT exist.<br>";
}

if (class_exists('\SendGrid\Mail\Mail')) {
    echo "Class \SendGrid\Mail\Mail exists.<br>";
} else {
    echo "Class \SendGrid\Mail\Mail DOES NOT exist.<br>";
}

echo "Test End<br>";
?>