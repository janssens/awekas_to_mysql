<?php
require_once 'config.php';

// Handle delete request
if (isset($_POST['delete']) && isset($_POST['id'])) {
    try {
        $stmt = $db->prepare("DELETE FROM weather_alerts WHERE id = ?");
        $stmt->execute([$_POST['id']]);
        header('Location: alerts.php?message=Alerte supprimée avec succès');
        exit;
    } catch (Exception $e) {
        $error = "Erreur lors de la suppression : " . $e->getMessage();
    }
}

// Handle toggle active status
if (isset($_POST['toggle']) && isset($_POST['id'])) {
    try {
        $stmt = $db->prepare("UPDATE weather_alerts SET is_active = NOT is_active WHERE id = ?");
        $stmt->execute([$_POST['id']]);
        header('Location: alerts.php?message=Statut modifié avec succès');
        exit;
    } catch (Exception $e) {
        $error = "Erreur lors de la modification : " . $e->getMessage();
    }
}

// Get all alerts
try {
    $stmt = $db->query("SELECT * FROM weather_alerts ORDER BY created_at DESC");
    $alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error = "Erreur lors de la récupération des alertes : " . $e->getMessage();
}

// Load measurement translations
$measurements = require 'config/measurements.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Alertes Météo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
    <?php require_once 'header.php'; ?>
    
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h2">Gestion des Alertes Météo</h1>
            <a href="edit_alert.php" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Nouvelle Alerte
            </a>
        </div>

        <?php if (isset($_GET['message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($_GET['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Mesure</th>
                            <th>Condition</th>
                            <th>Valeur</th>
                            <th>Message</th>
                            <th>Délai</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($alerts as $alert): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($measurements[$alert['alert_key']]['name'] ?? $alert['alert_key']); ?></td>
                                <td>
                                    <?php 
                                    echo $alert['alert_type'] === 'goes_above' ? 
                                        '<span class="text-danger">Supérieur à</span>' : 
                                        '<span class="text-primary">Inférieur à</span>';
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($alert['threshold_value']); ?></td>
                                <td><?php echo htmlspecialchars($alert['alert_message']); ?></td>
                                <td><?php echo htmlspecialchars($alert['notification_cooldown'] / 60); ?> min</td>
                                <td>
                                    <form method="post" style="display: inline;">
                                        <input type="hidden" name="id" value="<?php echo $alert['id']; ?>">
                                        <button type="submit" name="toggle" class="btn btn-sm btn-link p-0">
                                            <?php if ($alert['is_active']): ?>
                                                <span class="badge bg-success">Actif</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Inactif</span>
                                            <?php endif; ?>
                                        </button>
                                    </form>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="edit_alert.php?id=<?php echo $alert['id']; ?>" 
                                           class="btn btn-outline-primary">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form method="post" style="display: inline;" 
                                              onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette alerte ?');">
                                            <input type="hidden" name="id" value="<?php echo $alert['id']; ?>">
                                            <button type="submit" name="delete" class="btn btn-outline-danger">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($alerts)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    Aucune alerte configurée
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script>
    const vapidPublicKey = '<?php echo VAPID_PUBLIC_KEY; ?>';
    </script>
    <script src="./js/push-notifications.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 