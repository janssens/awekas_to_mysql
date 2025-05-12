<?php
require_once 'config.php';

try {
    // Get the latest weather data
    $stmt = $db->query("SELECT * FROM weather_data ORDER BY datatimestamp DESC LIMIT 1");
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Error fetching data: " . $e->getMessage());
}

// Define display names and units for the values we want to show
$displayData = [
    'recorded_at' => ['name' => 'Date et heure', 'unit' => '', 'format' => function($val) { return $val; }],
    'temperature' => ['name' => 'Température', 'unit' => '°C', 'format' => function($val) { return number_format($val, 1); }],
    'humidity' => ['name' => 'Humidité', 'unit' => '%', 'format' => function($val) { return $val; }],
    'dewpoint' => ['name' => 'Point de rosée', 'unit' => '°C', 'format' => function($val) { return number_format($val, 1); }],
    'windchill' => ['name' => 'Température ressentie', 'unit' => '°C', 'format' => function($val) { return number_format($val, 1); }],
    'wetbulbtemperature' => ['name' => 'Température humide', 'unit' => '°C', 'format' => function($val) { return number_format($val, 1); }],
    'windspeed' => ['name' => 'Vitesse du vent', 'unit' => 'km/h', 'format' => function($val) { return number_format($val, 1); }],
    'gustspeed' => ['name' => 'Rafales', 'unit' => 'km/h', 'format' => function($val) { return number_format($val, 1); }],
    'winddirection' => ['name' => 'Direction du vent', 'unit' => '°', 'format' => function($val) { return $val; }],
    'uv' => ['name' => 'Index UV', 'unit' => '', 'format' => function($val) { return number_format($val, 1); }],
    'solar' => ['name' => 'Rayonnement solaire', 'unit' => 'W/m²', 'format' => function($val) { return $val; }],
    'precipitation' => ['name' => 'Précipitations', 'unit' => 'mm', 'format' => function($val) { return number_format($val, 1); }],
    'indoortemperature' => ['name' => 'Température intérieure', 'unit' => '°C', 'format' => function($val) { return number_format($val, 1); }],
    'indoorhumidity' => ['name' => 'Humidité intérieure', 'unit' => '%', 'format' => function($val) { return $val; }]
];
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
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="weather-card p-4">
            <h1 class="h3 mb-4">Dernières mesures météorologiques</h1>
            <p class="update-time mb-4">
                Dernière mise à jour : <?php echo date('d/m/Y H:i', strtotime($data['recorded_at'])); ?>
            </p>

            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Mesure</th>
                            <th class="text-end">Valeur</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($displayData as $key => $info): ?>
                            <?php if (isset($data[$key]) && $data[$key] !== '' && $data[$key] !== null): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($info['name']); ?></td>
                                    <td class="text-end">
                                        <?php 
                                        echo htmlspecialchars($info['format']($data[$key]));
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
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 