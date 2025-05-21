<?php
require_once 'config.php';

// Load measurements configuration
$measurements = require_once 'config/measurements.php';

// Sélectionner les mesures à afficher
$selectedMeasurements = [];
if (isset($_GET['measurements']) && is_array($_GET['measurements'])) {
    foreach ($_GET['measurements'] as $key) {
        if (isset($measurements[$key])) {
            $selectedMeasurements[$key] = $measurements[$key];
        }
    }
}
// Si aucune mesure n'est sélectionnée, utiliser la température par défaut
if (empty($selectedMeasurements)) {
    $selectedMeasurements['temperature'] = $measurements['temperature'];
}

// Sélectionner la période (par défaut: jour)
$selectedPeriod = $_GET['period'] ?? 'day';
$periods = [
    'live' => ['label' => 'Live (6h)', 'format' => '%H:%i', 'interval' => '6 HOUR'],
    'day' => ['label' => 'Dernières 24h', 'format' => '%H:00', 'interval' => '1 DAY'],
    'week' => ['label' => '7 derniers jours', 'format' => '%H:00 %d/%m', 'interval' => '7 DAY'],
    'month' => ['label' => '30 derniers jours', 'format' => '%d/%m', 'interval' => '30 DAY'],
    'year' => ['label' => '12 derniers mois', 'format' => '%m/%Y', 'interval' => '12 MONTH']
];

// Fonction pour calculer les statistiques pour une période donnée
function getStats($db, $measurements, $interval) {
    $stats = [];
    foreach ($measurements as $key => $info) {
        $sql = "SELECT 
            MIN(datatimestamp) as start_date,
            MAX(datatimestamp) as end_date,
            MIN($key) as min_value,
            MAX($key) as max_value,
            AVG($key) as avg_value,
            COUNT(*) as total_readings
        FROM weather_data 
        WHERE recorded_at >= DATE_SUB(NOW(), INTERVAL $interval)
        AND $key IS NOT NULL";

        $stmt = $db->prepare($sql);
        $stmt->execute([]);
        $stats[$key] = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    return $stats;
}

// Fonction pour obtenir les données pour le graphique
function getChartData($db, $measurements, $interval, $format) {
    $measurementList = implode(', ', array_map(function($key) {
        return "$key as {$key}_value";
    }, array_keys($measurements)));

    if ($interval === '6 HOUR') {
        $sql = "SELECT 
            DATE_FORMAT(recorded_at, '$format') as label,
            datatimestamp as timestamp,
            $measurementList
        FROM weather_data 
        WHERE recorded_at >= DATE_SUB(NOW(), INTERVAL $interval)
        AND " . implode(' IS NOT NULL AND ', array_keys($measurements)) . " IS NOT NULL
        ORDER BY datatimestamp";
    } else {
        $aggregations = [];
        foreach (array_keys($measurements) as $key) {
            $aggregations[] = "MIN($key) as {$key}_min";
            $aggregations[] = "MAX($key) as {$key}_max";
            $aggregations[] = "AVG($key) as {$key}_avg";
        }
        $sql = "SELECT 
            DATE_FORMAT(recorded_at, '$format') as label,
            " . implode(', ', $aggregations) . "
        FROM weather_data 
        WHERE recorded_at >= DATE_SUB(NOW(), INTERVAL $interval)
        AND " . implode(' IS NOT NULL AND ', array_keys($measurements)) . " IS NOT NULL
        GROUP BY label
        ORDER BY datatimestamp";
    }
    
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute([]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("SQL Error in getChartData: " . $e->getMessage() . "\nSQL: " . $sql);
        return [];
    }
}

// Récupérer les statistiques et les données
$stats = getStats($db, $selectedMeasurements, $periods[$selectedPeriod]['interval']);
$chartData = getChartData(
    $db, 
    $selectedMeasurements, 
    $periods[$selectedPeriod]['interval'],
    $periods[$selectedPeriod]['format']
);

// Générer des couleurs distinctes pour chaque mesure
$colors = [
    'rgb(40, 167, 69)',    // vert
    'rgb(220, 53, 69)',    // rouge
    'rgb(0, 123, 255)',    // bleu
    'rgb(255, 193, 7)',    // jaune
    'rgb(111, 66, 193)',   // violet
    'rgb(23, 162, 184)',   // cyan
    'rgb(255, 127, 80)',   // corail
    'rgb(128, 0, 128)',    // pourpre
];
$measurementColors = array_combine(
    array_keys($selectedMeasurements),
    array_slice($colors, 0, count($selectedMeasurements))
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
    <style>
        @media (max-width: 768px) {
            .container {
                padding-left: 8px;
                padding-right: 8px;
            }
            .card {
                border-radius: 10px;
            }
            .card-body {
                padding: 12px;
            }
            .btn-group {
                width: 100%;
            }
            .btn-group .btn {
                flex: 1;
                padding: 0.375rem 0.5rem;
                font-size: 0.875rem;
            }
            .h2 {
                font-size: 1.5rem;
            }
            .h3 {
                font-size: 1.25rem;
            }
        }
        #measurementChart {
            min-height: 300px;
            max-height: 400px;
        }
        .stats-header {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .stats-header h1 {
            margin: 0;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .stats-header .btn-group {
            width: 100%;
            max-width: 600px;
        }
        .stats-header .btn-group .btn {
            flex: 1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        @media (max-width: 768px) {
            .stats-header .btn-group .btn {
                padding: 0.375rem 0.5rem;
                font-size: 0.875rem;
            }
        }
        .stats-controls {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            align-items: start;
        }
        .measurements-select {
            flex: 1;
            min-width: 250px;
            max-width: 400px;
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            padding: 0.5rem;
        }
        .measurements-select .form-check {
            margin-bottom: 0.25rem;
        }

        /* Styles pour le panneau latéral */
        .sidebar {
            position: fixed;
            left: -320px;
            top: 0;
            bottom: 0;
            width: 320px;
            background: white;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
            transition: left 0.3s ease;
            z-index: 1040;
            padding: 1rem;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
        }
        .sidebar.open {
            left: 0;
        }
        .sidebar-toggle {
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 50%;
            width: 42px;
            height: 42px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: none;
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: background-color 0.2s;
        }
        .sidebar-toggle:hover {
            background-color: #f8f9fa;
        }
        .sidebar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        .sidebar-header h2 {
            margin: 0;
            font-size: 1.25rem;
        }
        .sidebar-content {
            flex: 1;
            overflow-y: auto;
        }
        .sidebar-backdrop {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s;
            z-index: 1030;
        }
        .sidebar-backdrop.show {
            opacity: 1;
            visibility: visible;
        }
        @media (max-width: 576px) {
            .sidebar {
                width: 100%;
                left: -100%;
            }
        }
        .main-content {
            transition: margin-left 0.3s ease;
        }
        @media (min-width: 992px) {
            .main-content.shifted {
                margin-left: 320px;
            }
        }
    </style>
</head>
<body class="bg-light">
    <?php require_once 'header.php'; ?>
    
    <!-- Contenu principal -->
    <div class="container py-4 main-content">
        <form id="statsForm" method="get">
            <div class="stats-header">
                <h1 class="h2">
                    <button type="button" class="sidebar-toggle">
                        <i class="bi bi-sliders"></i>
                    </button>
                    Statistiques Météo
                </h1>
                <div class="btn-group">
                    <?php foreach ($periods as $key => $period): ?>
                        <input type="radio" class="btn-check" name="period" id="period_<?php echo $key; ?>"
                               value="<?php echo $key; ?>" <?php echo $selectedPeriod === $key ? 'checked' : ''; ?>>
                        <label class="btn btn-outline-primary" for="period_<?php echo $key; ?>">
                            <?php echo $period['label']; ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Panneau latéral -->
            <div class="sidebar">
                <div class="sidebar-header">
                    <h2>Configuration</h2>
                    <button type="button" class="btn-close" aria-label="Fermer"></button>
                </div>
                <div class="sidebar-content">
                    <div class="measurements-select mb-4">
                        <div class="mb-2">Sélectionner les mesures :</div>
                        <?php foreach ($measurements as $key => $info): ?>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="measurements[]" 
                                       value="<?php echo $key; ?>" id="check_<?php echo $key; ?>"
                                       <?php echo isset($selectedMeasurements[$key]) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="check_<?php echo $key; ?>">
                                    <?php echo $info['name']; ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </form>
        <div class="sidebar-backdrop"></div>

        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <canvas id="measurementChart"></canvas>
            </div>
        </div>

        <?php foreach ($selectedMeasurements as $key => $info): ?>
            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <h3 class="card-title h5 mb-3">
                        <?php echo $info['name']; ?>
                        <small class="text-muted">
                            (<?php echo $periods[$selectedPeriod]['label']; ?>)
                        </small>
                    </h3>
                    <div class="row text-center">
                        <div class="col">
                            <div class="h3 mb-0" style="color: <?php echo $measurementColors[$key]; ?>">
                                <?php echo $info['format']($stats[$key]['min_value']); ?>
                                <?php echo $info['unit']; ?>
                            </div>
                            <div class="text-muted small">Minimum</div>
                        </div>
                        <div class="col">
                            <div class="h3 mb-0" style="color: <?php echo $measurementColors[$key]; ?>">
                                <?php echo $info['format']($stats[$key]['avg_value']); ?>
                                <?php echo $info['unit']; ?>
                            </div>
                            <div class="text-muted small">Moyenne</div>
                        </div>
                        <div class="col">
                            <div class="h3 mb-0" style="color: <?php echo $measurementColors[$key]; ?>">
                                <?php echo $info['format']($stats[$key]['max_value']); ?>
                                <?php echo $info['unit']; ?>
                            </div>
                            <div class="text-muted small">Maximum</div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <div class="card shadow-sm">
            <div class="card-body">
                <h3 class="card-title h5">Informations</h3>
                <ul class="list-unstyled mb-0">
                    <li>Période : du <?php echo date('d/m/Y H:i', $stats[array_key_first($stats)]['start_date']); ?> 
                        au <?php echo date('d/m/Y H:i', $stats[array_key_first($stats)]['end_date']); ?></li>
                    <li>Nombre de mesures : <?php echo $stats[array_key_first($stats)]['total_readings']; ?></li>
                </ul>
            </div>
        </div>
    </div>

    <script>
    // Configuration du panneau latéral
    const sidebar = document.querySelector('.sidebar');
    const backdrop = document.querySelector('.sidebar-backdrop');
    const mainContent = document.querySelector('.main-content');
    const toggleButton = document.querySelector('.sidebar-toggle');
    const closeButton = document.querySelector('.sidebar .btn-close');

    function toggleSidebar() {
        sidebar.classList.toggle('open');
        backdrop.classList.toggle('show');
        if (window.innerWidth >= 992) {
            mainContent.classList.toggle('shifted');
        }
    }

    toggleButton.addEventListener('click', toggleSidebar);
    closeButton.addEventListener('click', toggleSidebar);
    backdrop.addEventListener('click', toggleSidebar);

    // Auto-submit form when selection changes
    document.querySelectorAll('#statsForm input').forEach(input => {
        input.addEventListener('change', () => {
            // Ensure at least one measurement is selected
            if (input.type === 'checkbox') {
                if (document.querySelectorAll('#statsForm input[name="measurements[]"]:checked').length === 0) {
                    input.checked = true;
                    return;
                }
            }
            document.getElementById('statsForm').submit();
        });
    });

    // Configuration du graphique
    const ctx = document.getElementById('measurementChart').getContext('2d');
    const chartData = <?php echo json_encode($chartData); ?>;
    const selectedPeriod = '<?php echo $selectedPeriod; ?>';
    const measurements = <?php echo json_encode($selectedMeasurements); ?>;
    const measurementColors = <?php echo json_encode($measurementColors); ?>;
    
    // Préparer les datasets
    const datasets = [];
    if (selectedPeriod === 'live') {
        // Mode live : une ligne par mesure
        Object.entries(measurements).forEach(([key, info]) => {
            datasets.push({
                label: info.name,
                data: chartData.map(d => d[key + '_value']),
                borderColor: measurementColors[key],
                backgroundColor: measurementColors[key],
                borderWidth: 2,
                pointRadius: 2,
                fill: false,
                tension: 0.1,
                yAxisID: key
            });
        });
    } else {
        // Mode agrégé : trois lignes par mesure
        Object.entries(measurements).forEach(([key, info]) => {
            datasets.push({
                label: info.name + ' (Max)',
                data: chartData.map(d => d[key + '_max']),
                borderColor: measurementColors[key],
                backgroundColor: measurementColors[key].replace('rgb', 'rgba').replace(')', ', 0.1)'),
                fill: '+2',
                tension: 0.1,
                yAxisID: key
            });
            datasets.push({
                label: info.name + ' (Moy)',
                data: chartData.map(d => d[key + '_avg']),
                borderColor: measurementColors[key],
                backgroundColor: measurementColors[key].replace('rgb', 'rgba').replace(')', ', 0.1)'),
                fill: true,
                tension: 0.1,
                yAxisID: key
            });
            datasets.push({
                label: info.name + ' (Min)',
                data: chartData.map(d => d[key + '_min']),
                borderColor: measurementColors[key],
                backgroundColor: measurementColors[key].replace('rgb', 'rgba').replace(')', ', 0.1)'),
                fill: '-2',
                tension: 0.1,
                yAxisID: key
            });
        });
    }

    // Créer les axes Y
    const scales = {
        x: {
            grid: {
                display: selectedPeriod === 'live'
            }
        }
    };
    Object.entries(measurements).forEach(([key, info], index) => {
        scales[key] = {
            type: 'linear',
            display: true,
            position: index % 2 === 0 ? 'left' : 'right',
            grid: {
                drawOnChartArea: index === 0
            },
            title: {
                display: true,
                text: info.name + (info.unit ? ` (${info.unit})` : '')
            }
        };
    });
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: chartData.map(d => d.label),
            datasets: datasets
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                intersect: false,
                mode: 'index'
            },
            scales: scales,
            plugins: {
                title: {
                    display: true,
                    text: 'Évolution des mesures'
                }
            }
        }
    });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 