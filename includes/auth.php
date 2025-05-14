<?php
session_start();

function isAuthenticated() {
    return isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true;
}

function requireAuth() {
    if (!isAuthenticated()) {
        header('Location: /login.php');
        exit;
    }
}

function login($username, $password) {
    if ($username === $_ENV['ADMIN_USERNAME'] && password_verify($password, $_ENV['ADMIN_PASSWORD_HASH'])) {
        $_SESSION['authenticated'] = true;
        return true;
    }
    return false;
}

function logout() {
    session_destroy();
    header('Location: /login.php');
    exit;
} 