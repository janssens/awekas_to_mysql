<?php
require_once 'config.php';

// Only accept GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    die('Method not allowed');
}

try {
    // Get subscription ID from endpoint
    $endpoint = $_GET['endpoint'] ?? '';
    if (empty($endpoint)) {
        throw new Exception('Endpoint parameter is required');
    }

    // Get subscription details
    $stmt = $db->prepare("SELECT id, endpoint FROM push_subscriptions WHERE endpoint = ?");
    $stmt->execute([$endpoint]);
    $subscription = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$subscription) {
        echo json_encode(['status' => 'not_found']);
        exit;
    }

    // Get alert subscriptions
    $stmt = $db->prepare("SELECT alert_id FROM push_subscription_alerts WHERE subscription_id = ?");
    $stmt->execute([$subscription['id']]);
    $alertSubscriptions = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode([
        'status' => 'success',
        'subscription_id' => $subscription['id'],
        'subscribed_alerts' => $alertSubscriptions
    ]);

} catch (Exception $e) {
    http_response_code(500);
    die('Error: ' . $e->getMessage());
} 