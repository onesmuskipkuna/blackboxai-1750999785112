<?php
// config.php - Global configuration settings

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'gym_supervision');
define('DB_USER', 'root');
define('DB_PASS', '');

// Email configuration
define('EMAIL_FROM', 'no-reply@gym.com');
define('SMTP_HOST', 'smtp.example.com');
define('SMTP_USER', 'smtp_user');
define('SMTP_PASS', 'smtp_password');
define('SMTP_PORT', 587);

// SMS API configuration for textsms.co.ke
define('TEXTSMS_API_KEY', 'your_textsms_api_key_here');
define('TEXTSMS_SENDER_ID', 'GymSupervision');

// Other global settings
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
