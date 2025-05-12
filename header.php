<?php
// Get current page name for active menu item
$current_page = basename($_SERVER['PHP_SELF']);
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container">
        <a class="navbar-brand" href="index.php"><?php echo TITLE; ?></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page === 'index.php' ? 'active' : ''; ?>" 
                       href="index.php">
                        <i class="bi bi-thermometer-half"></i> Mesures
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page === 'alerts.php' ? 'active' : ''; ?>" 
                       href="alerts.php">
                        <i class="bi bi-bell"></i> Alertes
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav> 