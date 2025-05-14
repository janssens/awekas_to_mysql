<?php
require_once 'includes/TelegramNotifier.php';
require_once 'includes/DataAgeChecker.php';
require_once 'includes/auth.php';
require_once 'includes/DateFormatter.php';

// Get current page name for active menu item
$current_page = basename($_SERVER['PHP_SELF']);

// Vérifier l'âge des données
$dataChecker = new DataAgeChecker($db);
$dataStatus = $dataChecker->checkDataAge();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $_ENV['TITLE']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="manifest" href="/manifest.json">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
        <div class="container">
            <a class="navbar-brand" href="/"><?php echo $_ENV['TITLE']; ?></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'index.php' ? 'active' : ''; ?>" href="./index.php">
                            <i class="bi bi-thermometer-half"></i> Mesures
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'stats.php' ? 'active' : ''; ?>" 
                            href="./stats.php">
                            <i class="bi bi-graph-up"></i> Statistiques
                        </a>
                    </li>
                    <?php if (isAuthenticated()): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'alerts.php' ? 'active' : ''; ?>" 
                        href="./alerts.php">
                            <i class="bi bi-bell"></i> Alertes
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'telegram_config.php' ? 'active' : ''; ?>" 
                        href="./telegram_config.php">
                            <i class="bi bi-telegram"></i> Configuration Telegram
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <?php if (isAuthenticated()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="./logout.php">
                                <i class="bi bi-box-arrow-right"></i> Déconnexion
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="./login.php">
                                <i class="bi bi-box-arrow-in-right"></i> Connexion
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    <div class="container">

<style>
.data-status-alert {
    margin: 0;
    border-radius: 0;
    text-align: center;
    padding: 0.5rem;
}
</style>

<?php if ($dataStatus['is_stale']): ?>
    <div class="alert alert-warning data-status-alert">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <?php if ($dataStatus['last_update']): ?>
            Dernière mise à jour il y a <?php echo $dataStatus['age_minutes']; ?> minutes
            (<?php echo DateFormatter::formatFrench($dataStatus['last_update']); ?>)
        <?php else: ?>
            Aucune donnée météo disponible
        <?php endif; ?>
    </div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 