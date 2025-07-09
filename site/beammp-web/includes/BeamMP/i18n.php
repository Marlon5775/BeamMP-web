<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../vendor/autoload.php';

// Chargement du .env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

// Langues supportées
$supported_langs = ['fr', 'en'];
$defaultLang = $_ENV['LANG_DEFAULT'] ?? 'en';

// Détection navigateur
function detectBrowserLang(array $supported): ?string {
    if (!isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) return null;
    $langs = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
    foreach ($langs as $lang) {
        $code = substr(trim($lang), 0, 2);
        if (in_array($code, $supported)) return $code;
    }
    return null;
}

// Détection de la langue
if (isset($_GET['lang']) && in_array($_GET['lang'], $supported_langs)) {
    $_SESSION['lang'] = $_GET['lang'];
} elseif (!isset($_SESSION['lang']) && isset($_COOKIE['lang']) && in_array($_COOKIE['lang'], $supported_langs)) {
    $_SESSION['lang'] = $_COOKIE['lang'];
}

$lang = $_SESSION['lang'] ?? detectBrowserLang($supported_langs) ?? $defaultLang;

// Mise à jour du cookie (30 jours)
setcookie('lang', $lang, time() + (30 * 24 * 60 * 60), "/");

// Chargement des traductions
$translationFile = __DIR__ . "/lang/$lang.php";
if (file_exists($translationFile)) {
    $translations = require $translationFile;
} else {
    $translations = require __DIR__ . "/lang/en.php";
}

// Fonction de traduction
function t(string $key): string {
    global $translations;
    return $translations[$key] ?? "[[$key]]";
}
