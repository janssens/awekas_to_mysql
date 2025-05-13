<?php
require_once 'config.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Method not allowed');
}

// Get POST body
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['endpoint']) || !isset($data['publicKey']) || !isset($data['authToken'])) {
    http_response_code(400);
    die('Missing required fields');
}

try {
    // Check if subscription already exists
    $stmt = $db->prepare("SELECT id FROM push_subscriptions WHERE endpoint = ?");
    $stmt->execute([$data['endpoint']]);
    $subscription = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($subscription) {
        // Update existing subscription
        $stmt = $db->prepare("UPDATE push_subscriptions SET 
            public_key = ?, 
            auth_token = ?,
            updated_at = NOW() 
            WHERE endpoint = ?");
        $stmt->execute([
            $data['publicKey'],
            $data['authToken'],
            $data['endpoint']
        ]);
        $subscriptionId = $subscription['id'];
    } else {
        // Insert new subscription
        $stmt = $db->prepare("INSERT INTO push_subscriptions 
            (endpoint, public_key, auth_token) 
            VALUES (?, ?, ?)");
        $stmt->execute([
            $data['endpoint'],
            $data['publicKey'],
            $data['authToken']
        ]);
        $subscriptionId = $db->lastInsertId();
    }

    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'subscription_id' => $subscriptionId
    ]);
} catch (Exception $e) {
    http_response_code(500);
    die('Error saving subscription: ' . $e->getMessage());
} 