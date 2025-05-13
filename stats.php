<?php
require_once 'config.php';

// Load measurements configuration
$measurements = require_once 'config/measurements.php';

// Fonction pour calculer les statistiques pour une période donnée
function getStats($db, $measurement, $interval) {
    $sql = "SELECT 
        MIN(datatimestamp) as start_date,
        MAX(datatimestamp) as end_date,
        MIN($measurement) as min_value,
        MAX($measurement) as max_value,
        AVG($measurement) as avg_value,
        COUNT(*) as total_readings
    FROM weather_data 
    WHERE recorded_at >= DATE_SUB(NOW(), INTERVAL $interval)
    AND $measurement IS NOT NULL";

    $stmt = $db->prepare($sql);
    $stmt->execute([]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Fonction pour obtenir les données pour le graphique
function getChartData($db, $measurement, $interval, $format) {
    $sql = "SELECT 
        DATE_FORMAT(recorded_at, $format) as label,
        MIN($measurement) as min_value,
        MAX($measurement) as max_value,
        AVG($measurement) as avg_value
    FROM weather_data 
    WHERE recorded_at >= DATE_SUB(NOW(), INTERVAL $interval)
    AND $measurement IS NOT NULL
    GROUP BY label
    ORDER BY datatimestamp";

    $stmt = $db->prepare($sql);
    $stmt->execute([]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Sélectionner la mesure à afficher (par défaut: température)
$selectedMeasurement = $_GET['measurement'] ?? 'temperature';
if (!isset($measurements[$selectedMeasurement])) {
    $selectedMeasurement = 'temperature';
}

// Sélectionner la période (par défaut: jour)
$selectedPeriod = $_GET['period'] ?? 'day';
$periods = [
    'day' => ['label' => 'Dernières 24h', 'format' => '%H:00', 'interval' => '1 DAY'],
    'week' => ['label' => '7 derniers jours', 'format' => '%d/%m', 'interval' => '7 DAY'],
    'month' => ['label' => '30 derniers jours', 'format' => '%d/%m', 'interval' => '30 DAY'],
    'year' => ['label' => '12 derniers mois', 'format' => '%m/%Y', 'interval' => '12 MONTH']
];

// Récupérer les statistiques
$stats = getStats($db, $selectedMeasurement, $periods[$selectedPeriod]['interval']);

// Récupérer les données pour le graphique
$chartData = getChartData(
    $db, 
    $selectedMeasurement, 
    $periods[$selectedPeriod]['interval'],
    $periods[$selectedPeriod]['format']
);

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistiques Météo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-light">
    <?php require_once 'header.php'; ?>
    
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h2">Statistiques Météo</h1>
            <div class="btn-group">
                <?php foreach ($periods as $key => $period): ?>
                    <a href="?measurement=<?php echo $selectedMeasurement; ?>&period=<?php echo $key; ?>" 
                       class="btn btn-outline-primary <?php echo $selectedPeriod === $key ? 'active' : ''; ?>">
                        <?php echo $period['label']; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-3">
                <div class="list-group">
                    <?php foreach ($measurements as $key => $info): ?>
                        <a href="?measurement=<?php echo $key; ?>&period=<?php echo $selectedPeriod; ?>" 
                           class="list-group-item list-group-item-action <?php echo $selectedMeasurement === $key ? 'active' : ''; ?>">
                            <?php echo $info['name']; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="col-md-9">
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <h2 class="card-title h4">
                            <?php echo $measurements[$selectedMeasurement]['name']; ?>
                            <small class="text-muted">
                                (<?php echo $periods[$selectedPeriod]['label']; ?>)
                            </small>
                        </h2>
                        <div class="row text-center mb-4">
                            <div class="col">
                                <div class="h3 mb-0 text-primary">
                                    <?php echo $measurements[$selectedMeasurement]['format']($stats['min_value']); ?>
                                    <?php echo $measurements[$selectedMeasurement]['unit']; ?>
                                </div>
                                <div class="text-muted small">Minimum</div>
                            </div>
                            <div class="col">
                                <div class="h3 mb-0 text-success">
                                    <?php echo $measurements[$selectedMeasurement]['format']($stats['avg_value']); ?>
                                    <?php echo $measurements[$selectedMeasurement]['unit']; ?>
                                </div>
                                <div class="text-muted small">Moyenne</div>
                            </div>
                            <div class="col">
                                <div class="h3 mb-0 text-danger">
                                    <?php echo $measurements[$selectedMeasurement]['format']($stats['max_value']); ?>
                                    <?php echo $measurements[$selectedMeasurement]['unit']; ?>
                                </div>
                                <div class="text-muted small">Maximum</div>
                            </div>
                        </div>
                        <canvas id="measurementChart"></canvas>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-body">
                        <h3 class="card-title h5">Informations</h3>
                        <ul class="list-unstyled mb-0">
                            <li>Période : du <?php echo date('d/m/Y H:i', $stats['start_date']); ?> 
                                au <?php echo date('d/m/Y H:i', $stats['end_date']); ?></li>
                            <li>Nombre de mesures : <?php echo $stats['total_readings']; ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    const ctx = document.getElementById('measurementChart').getContext('2d');
    const chartData = <?php echo json_encode($chartData); ?>;
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: chartData.map(d => d.label),
            datasets: [
                {
                    label: 'Maximum',
                    data: chartData.map(d => d.max_value),
                    borderColor: 'rgb(220, 53, 69)',
                    backgroundColor: 'rgba(220, 53, 69, 0.1)',
                    fill: '+1'
                },
                {
                    label: 'Moyenne',
                    data: chartData.map(d => d.avg_value),
                    borderColor: 'rgb(40, 167, 69)',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    fill: true
                },
                {
                    label: 'Minimum',
                    data: chartData.map(d => d.min_value),
                    borderColor: 'rgb(0, 123, 255)',
                    backgroundColor: 'rgba(0, 123, 255, 0.1)',
                    fill: '-1'
                }
            ]
        },
        options: {
            responsive: true,
            interaction: {
                intersect: false,
                mode: 'index'
            },
            scales: {
                y: {
                    title: {
                        display: true,
                        text: '<?php echo $measurements[$selectedMeasurement]['unit']; ?>'
                    }
                }
            },
            plugins: {
                title: {
                    display: true,
                    text: '<?php echo $measurements[$selectedMeasurement]['name']; ?>'
                }
            }
        }
    });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 