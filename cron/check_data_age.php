<?php
// Forcer l'exécution en CLI uniquement
if (php_sapi_name() !== 'cli') {
    die('Ce script ne peut être exécuté qu\'en ligne de commande');
}

// Charger la configuration et les dépendances
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/TelegramNotifier.php';
require_once __DIR__ . '/../includes/DataAgeChecker.php';

try {
    echo "Vérification de l'âge des données...\n";

    $dataChecker = new DataAgeChecker($db);
    $status = $dataChecker->checkDataAge();

    if ($status['is_stale']) {
        if ($status['last_update']) {
            echo "ALERTE : Données obsolètes\n";
            echo "Dernière mise à jour : " . date('d/m/Y H:i:s', strtotime($status['last_update'])) . "\n";
            echo "Âge des données : " . $status['age_minutes'] . " minutes\n";
        } else {
            echo "ALERTE : Aucune donnée disponible\n";
        }
        exit(1);
    } else {
        echo "OK : Données à jour\n";
        echo "Dernière mise à jour : " . date('d/m/Y H:i:s', strtotime($status['last_update'])) . "\n";
        exit(0);
    }
} catch (Exception $e) {
    echo "ERREUR : " . $e->getMessage() . "\n";
    exit(2);
} 