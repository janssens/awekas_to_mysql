<?php
require_once 'config.php';

// Load measurements configuration
$measurements = require_once 'config/measurements.php';

try {
    // Get the latest non-null values for each measurement
    $measurementFields = array_keys($measurements);
    $fields = implode(', ', array_map(function($field) {
        return "(SELECT $field FROM weather_data WHERE $field IS NOT NULL ORDER BY datatimestamp DESC LIMIT 1) as $field";
    }, $measurementFields));
    
    $stmt = $db->query("SELECT 
        (SELECT datatimestamp FROM weather_data ORDER BY datatimestamp DESC LIMIT 1) as recorded_at,
        $fields");
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Error fetching data: " . $e->getMessage());
}

// Function to format datetime in current timezone
function formatDateTime($timestamp) {
    $dt = new DateTime();
    $dt->setTimestamp($timestamp);
    return $dt->format('d/m/Y H:i');
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Météo - Dernières mesures</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .weather-card {
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .table th { 
            background-color: #f8f9fa;
            font-weight: 600;
        }
        .update-time {
            font-size: 0.9em;
            color: #6c757d;
        }
        .measurement-group {
            margin-bottom: 2rem;
        }
        .measurement-group h2 {
            font-size: 1.2rem;
            margin-bottom: 1rem;
            color: #495057;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="weather-card p-4">
            <h1 class="h3 mb-4">Dernières mesures météorologiques</h1>
            <p class="update-time mb-4">
                Dernière mise à jour : <?php echo formatDateTime($data['recorded_at']); ?>
                <?php if (defined('TIMEZONE')): ?>
                    (<?php echo TIMEZONE; ?>)
                <?php endif; ?>
            </p>

            <?php
            // Group measurements by their position in the config file
            $groups = [
                'Mesures principales' => array_slice($measurements, 0, 11),
                'Sondes de température' => array_slice($measurements, 11, 4),
                'Sondes d\'humidité' => array_slice($measurements, 15, 4),
                'Mesures intérieures' => array_slice($measurements, 19, 2),
                'Autres mesures' => array_slice($measurements, 21)
            ];
            ?>

            <?php foreach ($groups as $groupName => $groupMeasurements): ?>
                <?php
                // Check if there are any non-null values in this group
                $hasValues = false;
                foreach ($groupMeasurements as $key => $info) {
                    if (isset($data[$key]) && $data[$key] !== '' && $data[$key] !== null) {
                        $hasValues = true;
                        break;
                    }
                }
                if (!$hasValues) continue;
                ?>
                <div class="measurement-group">
                    <h2><?php echo htmlspecialchars($groupName); ?></h2>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Mesure</th>
                                    <th class="text-end">Valeur</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($groupMeasurements as $key => $info): ?>
                                    <?php if (isset($data[$key]) && $data[$key] !== '' && $data[$key] !== null): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($info['name']); ?></td>
                                            <td class="text-end">
                                                <?php 
                                                $formattedValue = $info['format']($data[$key]);
                                                echo htmlspecialchars($formattedValue);
                                                if ($info['unit']) {
                                                    echo ' ' . htmlspecialchars($info['unit']);
                                                }
                                                ?>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 