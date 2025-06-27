<?php
// functions.php - Common helper functions

require_once 'config.php';

// Send email using PHP mail() function or SMTP (simplified example)
function sendEmail($to, $subject, $message) {
    $headers = "From: " . EMAIL_FROM . "\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    return mail($to, $subject, $message, $headers);
}

// Send SMS using textsms.co.ke API implementation
function sendSMS($to, $message) {
    // textsms.co.ke API credentials - update these in config.php
    $apiKey = TEXTSMS_API_KEY; // Define this constant in config.php
    $senderId = TEXTSMS_SENDER_ID; // Define this constant in config.php

    $url = "https://www.textsms.co.ke/api/send";

    $postData = [
        'apikey' => $apiKey,
        'sender' => $senderId,
        'to' => $to,
        'message' => $message,
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);

    if ($err) {
        error_log("SMS sending error: " . $err);
        return false;
    } else {
        // Optionally, parse $response to check for success status
        return true;
    }
}

// Sanitize input data
function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Log errors to a file
function logError($message) {
    error_log(date('[Y-m-d H:i:s] ') . $message . PHP_EOL, 3, __DIR__ . '/error.log');
}
?>
