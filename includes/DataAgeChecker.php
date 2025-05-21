<?php
require_once __DIR__ . '/DateFormatter.php';

class DataAgeChecker {
    private $db;
    private $telegram;
    private $stateFile;
    private $notificationCooldown = 3600; // 1 heure entre les notifications

    public function __construct($db) {
        $this->db = $db;
        $this->telegram = new TelegramNotifier($db);
        $this->stateFile = __DIR__ . '/../data/station_status.json';
    }

    private function getState() {
        if (!file_exists($this->stateFile)) {
            return [
                'state' => 'online',
                'last_notification' => 0
            ];
        }
        $data = json_decode(file_get_contents($this->stateFile), true);
        return $data ?: [
            'state' => 'online',
            'last_notification' => 0
        ];
    }

    private function saveState($state, $notificationTime = null) {
        // Créer le répertoire data s'il n'existe pas
        $dataDir = dirname($this->stateFile);
        if (!is_dir($dataDir)) {
            mkdir($dataDir, 0755, true);
        }

        $currentData = $this->getState();
        $currentData['state'] = $state;
        if ($notificationTime !== null) {
            $currentData['last_notification'] = $notificationTime;
        }

        file_put_contents($this->stateFile, json_encode($currentData));
    }

    public function checkDataAge() {
        $stmt = $this->db->query("SELECT datatimestamp FROM weather_data ORDER BY datatimestamp DESC LIMIT 1");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$result) {
            $this->handleStateChange('offline');
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

        $isStale = $ageSeconds > 1800; // Plus d'une demi heure

        if ($isStale) {
            $this->handleStateChange('offline', $ageMinutes, $lastUpdate);
        } else {
            $this->handleStateChange('online', $ageMinutes, $lastUpdate);
        }

        return [
            'is_stale' => $isStale,
            'last_update' => $lastUpdate,
            'age_minutes' => $ageMinutes
        ];
    }

    private function handleStateChange($newState, $ageMinutes = null, $lastUpdate = null) {
        $stateData = $this->getState();
        $lastState = $stateData['state'];
        $lastNotification = $stateData['last_notification'];
        
        $now = time();
        if (($now - $lastNotification) < $this->notificationCooldown) {
            return;
        }

        if ($newState === 'offline' && $lastState === 'online') {
            $this->notifyOffline($ageMinutes, $lastUpdate);
            $this->saveState($newState, $now);
        } else if ($newState === 'online' && $lastState === 'offline') {
            $this->notifyRecovery($lastUpdate);
            $this->saveState($newState, $now);
        } else {
            $this->saveState($newState);
        }
    }

    private function notifyOffline($ageMinutes, $lastUpdate) {
        $message = "<b>⚠️ Alerte Station Météo</b>\n\n" .
                  "Aucune donnée reçue depuis {$ageMinutes} minutes.\n" .
                  "Veuillez vérifier la station météo.\n\n" .
                  "Dernière mise à jour : " . DateFormatter::formatFrench($lastUpdate);

        if ($this->telegram->isConfigured()) {
            $this->telegram->sendMessage($message);
        }
    }

    private function notifyRecovery($lastUpdate) {
        $message = "<b>✅ Station Météo en ligne</b>\n\n" .
                  "La station météo est à nouveau en ligne.\n" .
                  "Dernière mise à jour : " . DateFormatter::formatFrench($lastUpdate);

        if ($this->telegram->isConfigured()) {
            $this->telegram->sendMessage($message);
        }
    }
} 