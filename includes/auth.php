<?php

session_start();

function isAuthenticated() {
    return isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true;
}

function requireAuth() {
    if (!isAuthenticated()) {
        header('Location: ./login.php');
        exit;
    }
}

function login($username, $password) {
    // Vérification de la configuration
    if (empty($_ENV['ADMIN_USERNAME']) || empty($_ENV['ADMIN_PASSWORD_HASH'])) {
        error_log('Erreur de configuration : ADMIN_USERNAME ou ADMIN_PASSWORD_HASH non défini dans .env');
        return 'Erreur de configuration : identifiants administrateur non définis';
    }

    // Vérification des identifiants
    if (empty($username) || empty($password)) {
        return 'Veuillez remplir tous les champs';
    }

    if ($username !== $_ENV['ADMIN_USERNAME']) {
        error_log('Tentative de connexion avec un nom d\'utilisateur invalide : ' . $username);
        return false;
    }
    
    if (!password_verify($password, $_ENV['ADMIN_PASSWORD_HASH'])) {
        error_log('Tentative de connexion avec un mot de passe invalide pour l\'utilisateur : ' . $username);
        return false;
    }

    $_SESSION['authenticated'] = true;
    return true;
}

function logout() {
    session_destroy();
    header('Location: ./login.php');
    exit;
} 