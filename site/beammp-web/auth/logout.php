<?php
// Trouver dynamiquement la racine d'instance (comme dans index.php)
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

// Détruit la session proprement (avec suppression du cookie)
session_unset();
session_destroy();
$params = session_get_cookie_params();
setcookie(session_name(), '', time() - 42000,
    $params["path"], $params["domain"],
    $params["secure"], $params["httponly"]
);

// Redirige vers la page d'accueil de l'instance
header('Location: /index.php');
exit;
