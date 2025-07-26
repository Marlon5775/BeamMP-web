<?php
if (!isset($instanceRoot)) {
    function findInstanceRoot($maxLevels = 5) {
        $dir = dirname($_SERVER['SCRIPT_FILENAME']);
        for ($i = 0; $i < $maxLevels; $i++) {
            if (file_exists($dir . '/bootstrap.php')) return $dir;
            $parent = dirname($dir);
            if ($parent === $dir) break;
            $dir = $parent;
        }
        throw new Exception("Instance root (bootstrap.php) not found");
    }
    $instanceRoot = findInstanceRoot(5);
    require_once $instanceRoot . '/bootstrap.php';
}

// Langues supportées
$supported_langs = ['fr', 'en', 'de'];
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
