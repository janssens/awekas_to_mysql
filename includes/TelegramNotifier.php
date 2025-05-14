<?php
require_once __DIR__ . '/DateFormatter.php';

class TelegramNotifier {
    private $botToken;
    private $channelId;
    private $isActive;
    private $db;
    private $lastError;

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

    public function getLastError() {
        return $this->lastError;
    }

    public function testConfiguration() {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'La configuration Telegram n\'est pas active ou est incomplète.'
            ];
        }

        $testMessage = "<b>🔧 Test de Configuration</b>\n\n" .
                      "La configuration de votre bot Telegram est fonctionnelle.\n" .
                      "Vous recevrez les alertes météo sur ce canal.\n\n" .
                      "🕒 " . DateFormatter::formatFrench(time());

        if ($this->sendMessage($testMessage)) {
            return [
                'success' => true,
                'message' => 'Message de test envoyé avec succès !'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Erreur lors de l\'envoi du message de test : ' . $this->getLastError()
            ];
        }
    }

    public function sendMessage($message) {
        if (!$this->isConfigured()) {
            $this->lastError = "Configuration incomplète";
            return false;
        }

        $url = "https://api.telegram.org/bot{$this->botToken}/sendMessage";
        
        // Préparation des données au format application/x-www-form-urlencoded
        $data = [
            'chat_id' => $this->channelId,
            'text' => $message,
            'parse_mode' => 'HTML'
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded'
            ]
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            $this->lastError = "Erreur CURL : " . $curlError;
            error_log("Erreur Telegram (CURL) : " . $curlError);
            return false;
        }

        $result = json_decode($response, true);
        
        if (!isset($result['ok']) || !$result['ok']) {
            $errorMsg = isset($result['description']) ? $result['description'] : 'Erreur inconnue';
            $this->lastError = "Erreur Telegram : " . $errorMsg;
            error_log("Erreur Telegram (API) : " . $errorMsg);
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
               "🕒 " . DateFormatter::formatFrench(time());
    }
} 