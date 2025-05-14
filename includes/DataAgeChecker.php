<?php
require_once __DIR__ . '/DateFormatter.php';

class DataAgeChecker {
    private $db;
    private $telegram;
    private $lastNotificationFile;
    private $notificationCooldown = 3600; // 1 heure entre les notifications

    public function __construct($db) {
        $this->db = $db;
        $this->telegram = new TelegramNotifier($db);
        $this->lastNotificationFile = __DIR__ . '/../data/last_stale_notification.txt';
    }

    public function checkDataAge() {
        $stmt = $this->db->query("SELECT datatimestamp FROM weather_data ORDER BY datatimestamp DESC LIMIT 1");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$result) {
            return [
                'is_stale' => true,
                'last_update' => null,
                'age_minutes' => null
            ];
        }

        $lastUpdate = $result['datatimestamp']; //timestamp
        $now = time(); //timestamp
        $ageSeconds = $now - $lastUpdate;
        $ageMinutes = round($ageSeconds / 60);

        $isStale = $ageSeconds > 3600; // Plus d'une heure

        if ($isStale) {
            $this->notifyIfNeeded($ageMinutes, $lastUpdate);
        }

        return [
            'is_stale' => $isStale,
            'last_update' => $lastUpdate,
            'age_minutes' => $ageMinutes
        ];
    }

    private function notifyIfNeeded($ageMinutes, $lastUpdate) {
        // Vérifier si on a déjà notifié récemment
        if (file_exists($this->lastNotificationFile)) {
            $lastNotification = (int)file_get_contents($this->lastNotificationFile);
            if ((time() - $lastNotification) < $this->notificationCooldown) {
                return;
            }
        }

        // Créer le répertoire data s'il n'existe pas
        $dataDir = dirname($this->lastNotificationFile);
        if (!is_dir($dataDir)) {
            mkdir($dataDir, 0755, true);
        }

        // Envoyer la notification Telegram
        $message = "<b>⚠️ Alerte Station Météo</b>\n\n" .
                  "Aucune donnée reçue depuis {$ageMinutes} minutes.\n" .
                  "Veuillez vérifier la station météo.\n\n" .
                  "Dernière mise à jour : " . DateFormatter::formatFrench($lastUpdate);

        if ($this->telegram->isConfigured()) {
            $this->telegram->sendMessage($message);
        }

        // Enregistrer l'heure de la notification
        file_put_contents($this->lastNotificationFile, time());
    }
} 