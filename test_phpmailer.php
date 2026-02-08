<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/vendor/autoload.php';

echo "Test PHPMailer Load<br>";

if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    echo "Class PHPMailer\PHPMailer\PHPMailer exists.<br>";
    $mail = new PHPMailer\PHPMailer\PHPMailer();
    echo "Instance created.<br>";
} else {
    echo "Class PHPMailer\PHPMailer\PHPMailer DOES NOT exist.<br>";
}
?>