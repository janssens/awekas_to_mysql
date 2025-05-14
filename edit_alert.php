<?php
require_once 'includes/auth.php';
requireAuth();

require_once 'config.php';

// Load measurement translations
$measurements = require 'config/measurements.php';

// Initialize variables
$alert = [
    'id' => null,
    'is_active' => true,
    'alert_key' => '',
    'alert_type' => 'goes_above',
    'threshold_value' => '',
    'notification_cooldown' => 3600,
    'alert_message' => ''
];

// If editing existing alert
if (isset($_GET['id'])) {
    try {
        $stmt = $db->prepare("SELECT * FROM weather_alerts WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $alert = array_merge($alert, $row);
        }
    } catch (Exception $e) {
        $error = "Erreur lors de la récupération de l'alerte : " . $e->getMessage();
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $alert = array_merge($alert, $_POST);
    
    try {
        if (isset($_POST['id']) && $_POST['id']) {
            // Update existing alert
            $stmt = $db->prepare("UPDATE weather_alerts SET 
                is_active = ?,
                alert_key = ?,
                alert_type = ?,
                threshold_value = ?,
                notification_cooldown = ?,
                alert_message = ?
                WHERE id = ?");
            
            $stmt->execute([
                isset($_POST['is_active']) ? 1 : 0,
                $_POST['alert_key'],
                $_POST['alert_type'],
                $_POST['threshold_value'],
                $_POST['notification_cooldown'] * 60, // Convert minutes to seconds
                $_POST['alert_message'],
                $_POST['id']
            ]);
        } else {
            // Create new alert
            $stmt = $db->prepare("INSERT INTO weather_alerts 
                (is_active, alert_key, alert_type, threshold_value, notification_cooldown, alert_message)
                VALUES (?, ?, ?, ?, ?, ?)");
            
            $stmt->execute([
                isset($_POST['is_active']) ? 1 : 0,
                $_POST['alert_key'],
                $_POST['alert_type'],
                $_POST['threshold_value'],
                $_POST['notification_cooldown'] * 60, // Convert minutes to seconds
                $_POST['alert_message']
            ]);
        }
        
        header('Location: alerts.php?message=' . urlencode('Alerte ' . (isset($_POST['id']) ? 'modifiée' : 'créée') . ' avec succès'));
        exit;
    } catch (Exception $e) {
        $error = "Erreur lors de l'enregistrement : " . $e->getMessage();
    }
}

// Group measurements by category
$groupedMeasurements = [
    'Mesures principales' => array_filter($measurements, function($key) {
        return !preg_match('/(temp[1-4]|humidity[1-4]|indoor|soil|leaf)/', $key);
    }, ARRAY_FILTER_USE_KEY),
    'Sondes de température' => array_filter($measurements, function($key) {
        return preg_match('/^temp[1-4]$/', $key);
    }, ARRAY_FILTER_USE_KEY),
    'Sondes d\'humidité' => array_filter($measurements, function($key) {
        return preg_match('/^humidity[1-4]$/', $key);
    }, ARRAY_FILTER_USE_KEY),
    'Mesures intérieures' => array_filter($measurements, function($key) {
        return strpos($key, 'indoor') === 0;
    }, ARRAY_FILTER_USE_KEY),
    'Autres mesures' => array_filter($measurements, function($key) {
        return preg_match('/(soil|leaf|brightness|suntime)/', $key);
    }, ARRAY_FILTER_USE_KEY)
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $alert['id'] ? 'Modifier' : 'Créer'; ?> une Alerte</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h1 class="card-title h4 mb-4">
                            <?php echo $alert['id'] ? 'Modifier' : 'Créer'; ?> une Alerte
                        </h1>

                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger">
                                <?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php endif; ?>

                        <form method="post">
                            <?php if ($alert['id']): ?>
                                <input type="hidden" name="id" value="<?php echo $alert['id']; ?>">
                            <?php endif; ?>

                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="is_active" 
                                           name="is_active" <?php echo $alert['is_active'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="is_active">Alerte active</label>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="alert_key" class="form-label">Mesure</label>
                                <select class="form-select" id="alert_key" name="alert_key" required>
                                    <option value="">Choisir une mesure</option>
                                    <?php foreach ($groupedMeasurements as $group => $items): ?>
                                        <optgroup label="<?php echo htmlspecialchars($group); ?>">
                                            <?php foreach ($items as $key => $info): ?>
                                                <option value="<?php echo htmlspecialchars($key); ?>" 
                                                    <?php echo $alert['alert_key'] === $key ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($info['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="alert_type" class="form-label">Condition</label>
                                <select class="form-select" id="alert_type" name="alert_type" required>
                                    <option value="goes_above" <?php echo $alert['alert_type'] === 'goes_above' ? 'selected' : ''; ?>>
                                        Supérieur à
                                    </option>
                                    <option value="goes_below" <?php echo $alert['alert_type'] === 'goes_below' ? 'selected' : ''; ?>>
                                        Inférieur à
                                    </option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="threshold_value" class="form-label">Valeur seuil</label>
                                <input type="number" step="0.1" class="form-control" id="threshold_value" 
                                       name="threshold_value" value="<?php echo $alert['threshold_value']; ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="notification_cooldown" class="form-label">
                                    Délai minimum entre les notifications (minutes)
                                </label>
                                <input type="number" class="form-control" id="notification_cooldown" 
                                       name="notification_cooldown" 
                                       value="<?php echo $alert['notification_cooldown'] / 60; ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="alert_message" class="form-label">Message de notification</label>
                                <textarea class="form-control" id="alert_message" name="alert_message" 
                                          rows="3" required><?php echo $alert['alert_message']; ?></textarea>
                            </div>

                            <div class="d-flex justify-content-between">
                                <a href="alerts.php" class="btn btn-outline-secondary">Annuler</a>
                                <button type="submit" class="btn btn-primary">
                                    <?php echo $alert['id'] ? 'Modifier' : 'Créer'; ?> l'alerte
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 