<?php

class TelegramNotifier {
    private $botToken;
    private $channelId;
    private $isActive;
    private $db;

    public function __construct($db) {
        $this->db = $db;
        $this->loadConfig();
    }

    private function loadConfig() {
        $stmt = $this->db->query("SELECT * FROM telegram_config WHERE is_active = 1 LIMIT 1");
        $config = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($config) {
            $this->botToken = $config['bot_token'];
            $this->channelId = $config['channel_id'];
            $this->isActive = true;
        } else {
            $this->isActive = false;
        }
    }

    public function isConfigured() {
        return $this->isActive && !empty($this->botToken) && !empty($this->channelId);
    }

    public function sendMessage($message) {
        if (!$this->isConfigured()) {
            return false;
        }

        $url = "https://api.telegram.org/bot{$this->botToken}/sendMessage";
        
        $data = [
            'chat_id' => $this->channelId,
            'text' => $message,
            'parse_mode' => 'HTML'
        ];

        $options = [
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/x-www-form-urlencoded',
                'content' => http_build_query($data)
            ]
        ];

        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);

        if ($result === false) {
            error_log("Erreur lors de l'envoi du message Telegram");
            return false;
        }

        return true;
    }

    public function formatAlertMessage($alert, $currentValue) {
        // Load measurement config
        $measurements = require __DIR__ . '/../config/measurements.php';
        $measurement = $measurements[$alert['alert_key']] ?? ['name' => $alert['alert_key'], 'unit' => ''];
        
        $condition = $alert['alert_type'] === 'goes_above' ? 'supérieure' : 'inférieure';
        
        return "<b>🚨 Alerte Météo</b>\n\n" .
               "{$alert['alert_message']}\n\n" .
               "📊 <b>{$measurement['name']}</b>\n" .
               "Valeur actuelle : {$currentValue} {$measurement['unit']}\n" .
               "Seuil {$condition} : {$alert['threshold_value']} {$measurement['unit']}\n" .
               "🕒 " . date('d/m/Y H:i:s');
    }
} 