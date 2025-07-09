<?php
session_start();

require_once __DIR__ . '/../../vendor/autoload.php'; 
require_once __DIR__ . '/../../includes/roles.php'; 
require_once __DIR__ . '/../../includes/db.php';

if (!isset($_SESSION['user_id']) || !hasRole(['SuperAdmin', 'Admin'])) {
    http_response_code(403);
    exit(json_encode(['success' => false, 'message' => 'Accès interdit.']));
}

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

$beammpFolder = rtrim($_ENV['PATH_RESOURCES'], '/');
$serverConfigToml = $_ENV['SERVERCONFIG_PATH'];

header('Content-Type: application/json');

// Récupération de l'ID de la nouvelle carte
$data = json_decode(file_get_contents('php://input'), true);
$id_map = $data['id_map'] ?? null;

if (!$id_map) {
    echo json_encode(['success' => false, 'message' => 'ID de carte manquant.']);
    exit;
}

try {
    // Récupérer la nouvelle carte
    $stmt = $pdo->prepare("SELECT id_map, chemin, map_officielle, map_active FROM beammp WHERE id_map = ?");
    $stmt->execute([$id_map]);
    $newMap = $stmt->fetch();

    if (!$newMap) {
        throw new Exception("Nouvelle carte introuvable dans la base de données.");
    }

    // Récupérer la carte actuelle
    $stmt = $pdo->prepare("SELECT id_map, chemin, map_officielle FROM beammp WHERE map_active = 1");
    $stmt->execute();
    $currentMap = $stmt->fetch();

    if (!$currentMap) {
        throw new Exception("Carte active introuvable dans la base de données.");
    }

    // Déplacement de la carte actuelle vers inactive_map/
    if (!$currentMap['map_officielle']) {
        $currentMapPath = "{$beammpFolder}/Client/{$currentMap['chemin']}";
        $inactiveMapPath = "{$beammpFolder}/inactive_map/{$currentMap['chemin']}";

        if (file_exists($currentMapPath)) {
            rename($currentMapPath, $inactiveMapPath);
        }
    }

    // Déplacement de la nouvelle carte vers Client/
    if (!$newMap['map_officielle']) {
        $newMapPath = "{$beammpFolder}/inactive_map/{$newMap['chemin']}";
        $clientMapPath = "{$beammpFolder}/Client/{$newMap['chemin']}";

        if (file_exists($newMapPath)) {
            rename($newMapPath, $clientMapPath);
        }
    }

    // Mise à jour en base
    $pdo->prepare("UPDATE beammp SET map_active = 0 WHERE id_map = ?")->execute([$currentMap['id_map']]);
    $pdo->prepare("UPDATE beammp SET map_active = 1 WHERE id_map = ?")->execute([$newMap['id_map']]);

    // Préparer la valeur pour ServerConfig.toml
    $newMapPathToml = "/levels/{$newMap['id_map']}/info.json";
    $newMapTomlLine = "Map = \"{$newMapPathToml}\"";

    // Modifier ServerConfig.toml en local
    $cmd = "sed -i 's|^Map = .*|{$newMapTomlLine}|' " . escapeshellarg($serverConfigToml);
    exec($cmd, $output, $return_var);

    if ($return_var !== 0) {
        throw new Exception("Erreur lors de la mise à jour de ServerConfig.toml.");
    }

    echo json_encode(['success' => true, 'message' => 'Carte mise à jour avec succès. Redémarrage du serveur requis.']);
} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
