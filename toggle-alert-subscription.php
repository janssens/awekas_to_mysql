<?php
require_once 'config.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Method not allowed');
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['subscription_id']) || !isset($data['alert_id'])) {
    http_response_code(400);
    die('Missing required fields');
}

try {
    // Check if subscription exists
    $stmt = $db->prepare("SELECT 1 FROM push_subscription_alerts 
                         WHERE subscription_id = ? AND alert_id = ?");
    $stmt->execute([$data['subscription_id'], $data['alert_id']]);
    
    if ($stmt->fetch()) {
        // Unsubscribe
        $stmt = $db->prepare("DELETE FROM push_subscription_alerts 
                            WHERE subscription_id = ? AND alert_id = ?");
        $stmt->execute([$data['subscription_id'], $data['alert_id']]);
        
        echo json_encode(['status' => 'unsubscribed']);
    } else {
        // Subscribe
        $stmt = $db->prepare("INSERT INTO push_subscription_alerts 
                            (subscription_id, alert_id) VALUES (?, ?)");
        $stmt->execute([$data['subscription_id'], $data['alert_id']]);
        
        echo json_encode(['status' => 'subscribed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    die('Error managing subscription: ' . $e->getMessage());
} 