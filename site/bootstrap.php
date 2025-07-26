<?php
// Définir la racine de l’instance 
if (!defined('INSTANCE_ROOT')) {
    define('INSTANCE_ROOT', __DIR__);
}

// Chargement automatique des classes Composer 
if (file_exists(INSTANCE_ROOT . '/vendor/autoload.php')) {
    require_once INSTANCE_ROOT . '/vendor/autoload.php';
}

// Chargement du .env
if (file_exists(INSTANCE_ROOT . '/.env')) {
    // Charge via Dotenv si dispo, sinon fallback manuel
    if (class_exists('Dotenv\Dotenv')) {
        $dotenv = Dotenv\Dotenv::createImmutable(INSTANCE_ROOT);
        $dotenv->safeLoad();
    } else {
        $lines = file(INSTANCE_ROOT . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) continue;
            list($name, $value) = array_map('trim', explode('=', $line, 2));
            $_ENV[$name] = $value;
        }
    }
} else {
    throw new Exception("Fichier .env introuvable dans " . INSTANCE_ROOT);
}

// --- GESTION DU NOM DE SESSION ISOLÉ PAR INSTANCE ---
// Utilise la clé TITLE du .env (obligatoire pour chaque instance)
$session_name = 'PHPSESSID_' . preg_replace('/[^a-zA-Z0-9]/', '', strtolower($_ENV['TITLE'] ?? 'default'));
session_name($session_name);

// Sécurisation avancée des cookies de session
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443;
session_set_cookie_params([
    'httponly' => true,
    'secure'   => $isHttps,
    'samesite' => 'Strict',
]);
ini_set('session.cookie_secure', $isHttps ? '1' : '0');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Strict');

// Démarrage de la session (protection contre redémarrage)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
