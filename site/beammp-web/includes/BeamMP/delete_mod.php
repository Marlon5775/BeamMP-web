<?php
session_start();
require_once __DIR__ . '/../../includes/roles.php';
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !hasRole(['SuperAdmin', 'Admin'])) {
    http_response_code(403);
    exit(json_encode(['success' => false, 'message' => 'Accès interdit.']));
}

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Requête non autorisée.');
    }

    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input || !isset($input['mod_nom']) || !isset($input['mod_type'])) {
        throw new Exception('Données invalides.');
    }

    $modNom = $input['mod_nom'];
    $modType = $input['mod_type'];

    $stmt = $pdo->prepare("SELECT chemin, image, mod_actif, description, map_active FROM beammp WHERE nom = :nom AND type = :type");
    $stmt->execute([':nom' => $modNom, ':type' => $modType]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        throw new Exception("Élément introuvable dans la base de données.");
    }

    $pathBaseRemove = rtrim($_ENV['PATH_RESOURCES'], '/');
    $basePath = rtrim($_ENV['BASE_PATH'], '/');

    $remoteZipPath = ($modType === "map")
        ? $pathBaseRemove . ($row['map_active'] == 0 ? "/inactive_map/" : "/")
        : $pathBaseRemove . ($row['mod_actif'] == 1 ? "/Client/" : "/inactive_mod/");

    $remoteZipPath .= $row['chemin'];

    $localImagePath = $basePath . "/assets/images/BeamMP/" . $row['image'];
    $localDescriptionPath = $basePath . "/assets/fichiers/BeamMP/" . $row['description'];

    // Suppression fichier ZIP local
    if (file_exists($remoteZipPath)) {
        unlink($remoteZipPath);
    }

    // Suppression fichiers locaux
    if (file_exists($localImagePath)) {
        unlink($localImagePath);
    }

    if (file_exists($localDescriptionPath)) {
        unlink($localDescriptionPath);
    }

    // Suppression dans la base
    $stmt = $pdo->prepare("DELETE FROM beammp WHERE nom = :nom AND type = :type");
    $stmt->execute([':nom' => $modNom, ':type' => $modType]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
