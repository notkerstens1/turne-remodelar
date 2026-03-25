<?php
/**
 * Meta Conversions API Endpoint
 * Receives events from the frontend and forwards to Meta's Graph API
 */

// CORS headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Configuration
$PIXEL_ID = '3834617633503016';
$ACCESS_TOKEN = 'EAAJvI6ZALgXoBRMDa7dME7YiUDMSdPdmSX85YNeQH8RBoqMrwhMEAcAtre3ZBHEktAgb7neUoxtWgPulozLFSsuowyLoYh5vt8j49FwAxFFQYEKXn1ATSIiBVrdOzXZA601bbL1bxEdnG8vcRJrF1a5nCdN7wO9VQF7IsO382HXAr4MhLzyvobn8x8A3wZDZD';

// Read request body
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['event_name'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

// Build user_data
$user_data = [
    'client_user_agent' => $input['user_agent'] ?? $_SERVER['HTTP_USER_AGENT'] ?? '',
    'client_ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
];

if (!empty($input['fbc'])) {
    $user_data['fbc'] = $input['fbc'];
}
if (!empty($input['fbp'])) {
    $user_data['fbp'] = $input['fbp'];
}

// Build event
$event = [
    'event_name' => $input['event_name'],
    'event_time' => time(),
    'event_id' => $input['event_id'] ?? uniqid('evt_'),
    'event_source_url' => $input['event_source_url'] ?? '',
    'action_source' => 'website',
    'user_data' => $user_data,
];

if (!empty($input['custom_data'])) {
    $event['custom_data'] = $input['custom_data'];
}

// Send to Meta Graph API
$payload = json_encode([
    'data' => [$event],
    'access_token' => $ACCESS_TOKEN,
]);

$url = "https://graph.facebook.com/v21.0/{$PIXEL_ID}/events";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

http_response_code($httpCode >= 200 && $httpCode < 300 ? 200 : 502);
echo $response;
