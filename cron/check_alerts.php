<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/TelegramNotifier.php';

use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

// Function to check if we should send notification (respect cooldown)
function shouldSendNotification($lastTriggered, $cooldown) {
    if ($lastTriggered === null) return true;
    $lastTime = strtotime($lastTriggered);
    return (time() - $lastTime) >= $cooldown;
}

// Function to check if weather data is recent enough
function isWeatherDataRecent($timestamp, $maxAge) {
    $age = time() - $timestamp;
    return $age <= $maxAge;
}

try {
    // Initialize Telegram notifier
    $telegram = new TelegramNotifier($db);

    // Get latest weather data
    $stmt = $db->query("SELECT *, datatimestamp as timestamp FROM weather_data ORDER BY datatimestamp DESC LIMIT 1");
    $weatherData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$weatherData) {
        die("No weather data available\n");
    }

    // Get active alerts
    $stmt = $db->query("SELECT * FROM weather_alerts WHERE is_active = 1");
    $alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Check each alert
    $triggeredAlerts = [];
    foreach ($alerts as $alert) {
        $key = $alert['alert_key'];
        if (!isset($weatherData[$key])) continue;

        // Vérifier que les données sont plus récentes que le cooldown
        if (!isWeatherDataRecent($weatherData['timestamp'], $alert['notification_cooldown'])) {
            $dataTime = new DateTime(); $dataTime->setTimestamp($weatherData['timestamp']);
            error_log("Skipping alert check for {$key}: weather data is too old (last update: " . $dataTime->date_format('d/m/Y H:i:s') . ")");
            continue;
        }

        $currentValue = floatval($weatherData[$key]);
        $threshold = floatval($alert['threshold_value']);
        $isTriggered = false;

        if ($alert['alert_type'] === 'goes_above' && $currentValue > $threshold) {
            $isTriggered = true;
        } elseif ($alert['alert_type'] === 'goes_below' && $currentValue < $threshold) {
            $isTriggered = true;
        }

        if ($isTriggered && shouldSendNotification($alert['last_triggered_at'], $alert['notification_cooldown'])) {
            $triggeredAlerts[] = $alert;
            
            // Update last_triggered_at
            $stmt = $db->prepare("UPDATE weather_alerts SET last_triggered_at = NOW() WHERE id = ?");
            $stmt->execute([$alert['id']]);

            // Send Telegram notification
            if ($telegram->isConfigured()) {
                $message = $telegram->formatAlertMessage($alert, $currentValue);
                $telegram->sendMessage($message);
            }
        }
    }

    // If we have triggered alerts, send web push notifications
    if (!empty($triggeredAlerts)) {
        // Get all subscriptions
        $stmt = $db->query("SELECT * FROM push_subscriptions");
        $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($subscriptions)) {
            $auth = [
                'VAPID' => [
                    'subject' => 'mailto:' . NOTIFICATION_EMAIL,
                    'publicKey' => VAPID_PUBLIC_KEY,
                    'privateKey' => VAPID_PRIVATE_KEY,
                ]
            ];

            $webPush = new WebPush($auth);

            foreach ($subscriptions as $sub) {
                $subscription = Subscription::create([
                    'endpoint' => $sub['endpoint'],
                    'publicKey' => $sub['public_key'],
                    'authToken' => $sub['auth_token'],
                ]);

                foreach ($triggeredAlerts as $alert) {
                    $payload = json_encode([
                        'title' => 'Alerte Météo',
                        'body' => $alert['alert_message'],
                        'icon' => '/weather-icon.png',
                        'timestamp' => time()
                    ]);

                    $webPush->queueNotification($subscription, $payload);
                }
            }

            // Send all notifications
            foreach ($webPush->flush() as $report) {
                $endpoint = $report->getRequest()->getUri()->__toString();
                
                // Remove invalid subscriptions
                if (!$report->isSuccess() && in_array($report->getResponse()->getStatusCode(), [404, 410])) {
                    $stmt = $db->prepare("DELETE FROM push_subscriptions WHERE endpoint = ?");
                    $stmt->execute([$endpoint]);
                }
            }
        }
    }

    echo "Alert check completed successfully\n";
} catch (Exception $e) {
    die("Error: " . $e->getMessage() . "\n");
} 