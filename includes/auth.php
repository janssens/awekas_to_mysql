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
    if (!isset($_ENV['ADMIN_USERNAME']) || !isset($_ENV['ADMIN_PASSWORD_HASH'])) {
        error_log('Configuration d\'authentification manquante dans le fichier .env');
        return 'Configuration d\'authentification non définie';
    }

    if ($username !== $_ENV['ADMIN_USERNAME']) {
        return false;
    }
    
    if (!password_verify($password, $_ENV['ADMIN_PASSWORD_HASH'])) {
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