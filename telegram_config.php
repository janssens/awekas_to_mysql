<?php
require_once 'config.php';
require_once 'includes/TelegramNotifier.php';

// Handle test request
if (isset($_POST['action']) && $_POST['action'] === 'test') {
    header('Content-Type: application/json');
    
    try {
        $telegram = new TelegramNotifier($db);
        $result = $telegram->testConfiguration();
        echo json_encode($result);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Erreur : ' . $e->getMessage()
        ]);
    }
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get current config
        $stmt = $db->query("SELECT id FROM telegram_config LIMIT 1");
        $config = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($config) {
            // Update existing config
            $stmt = $db->prepare("UPDATE telegram_config SET 
                bot_token = ?,
                channel_id = ?,
                is_active = ?,
                updated_at = CURRENT_TIMESTAMP
                WHERE id = ?");
            
            $stmt->execute([
                $_POST['bot_token'],
                $_POST['channel_id'],
                isset($_POST['is_active']) ? 1 : 0,
                $config['id']
            ]);
        } else {
            // Create new config
            $stmt = $db->prepare("INSERT INTO telegram_config 
                (bot_token, channel_id, is_active)
                VALUES (?, ?, ?)");
            
            $stmt->execute([
                $_POST['bot_token'],
                $_POST['channel_id'],
                isset($_POST['is_active']) ? 1 : 0
            ]);
        }
        
        $message = "Configuration Telegram mise à jour avec succès";
    } catch (Exception $e) {
        $error = "Erreur lors de l'enregistrement : " . $e->getMessage();
    }
}

// Get current config
try {
    $stmt = $db->query("SELECT * FROM telegram_config LIMIT 1");
    $config = $stmt->fetch(PDO::FETCH_ASSOC) ?: [
        'id' => null,
        'bot_token' => '',
        'channel_id' => '',
        'is_active' => true
    ];
} catch (Exception $e) {
    $error = "Erreur lors de la récupération de la configuration : " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuration Telegram</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        #testResult {
            display: none;
            margin-top: 1rem;
        }
    </style>
</head>
<body class="bg-light">
    <?php require_once 'header.php'; ?>
    
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h1 class="card-title h4 mb-4">Configuration Telegram</h1>

                        <?php if (isset($message)): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <?php echo htmlspecialchars($message); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?php echo htmlspecialchars($error); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <form method="post" id="configForm">
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="is_active" 
                                           name="is_active" <?php echo $config['is_active'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="is_active">Notifications Telegram actives</label>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="bot_token" class="form-label">Token du Bot Telegram</label>
                                <input type="text" class="form-control" id="bot_token" 
                                       name="bot_token" value="<?php echo htmlspecialchars($config['bot_token']); ?>" required>
                                <div class="form-text">
                                    Pour créer un bot et obtenir un token, contactez @BotFather sur Telegram
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="channel_id" class="form-label">ID du Canal Telegram</label>
                                <input type="text" class="form-control" id="channel_id" 
                                       name="channel_id" value="<?php echo htmlspecialchars($config['channel_id']); ?>" required>
                                <div class="form-text">
                                    Format : @nomducanal ou -100123456789
                                </div>
                            </div>

                            <div class="alert alert-info" role="alert" id="testResult"></div>

                            <div class="d-flex justify-content-between align-items-center">
                                <a href="alerts.php" class="btn btn-outline-secondary">
                                    <i class="bi bi-arrow-left"></i> Retour aux alertes
                                </a>
                                <div>
                                    <button type="button" class="btn btn-info me-2" id="testButton">
                                        <i class="bi bi-send"></i> Tester la configuration
                                    </button>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-save"></i> Enregistrer la configuration
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const testButton = document.getElementById('testButton');
        const testResult = document.getElementById('testResult');
        const configForm = document.getElementById('configForm');

        testButton.addEventListener('click', async function() {
            testButton.disabled = true;
            testResult.style.display = 'block';
            testResult.className = 'alert alert-info';
            testResult.textContent = 'Test en cours...';

            try {
                const response = await fetch('telegram_config.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=test'
                });

                const result = await response.json();
                
                testResult.className = `alert alert-${result.success ? 'success' : 'danger'}`;
                testResult.textContent = result.message;
            } catch (error) {
                testResult.className = 'alert alert-danger';
                testResult.textContent = 'Erreur lors du test : ' + error.message;
            } finally {
                testButton.disabled = false;
            }
        });
    });
    </script>
</body>
</html> 