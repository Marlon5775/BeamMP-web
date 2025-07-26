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
// Headers SSE
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
ob_implicit_flush(true);

// Sécuriser les paramètres GET
$name = preg_replace('/[^a-zA-Z0-9_ ]/', '', $_GET['name'] ?? '');
$nameSanitized = str_replace(' ', '_', $name);
$type = $_GET['type'] ?? '';
$vehicleType = $_GET['vehicle_type'] ?? null;
$modActif = (int)($_GET['status'] ?? 0);

// Chemins locaux
$uploadDir = rtrim($_ENV['DATA_PATH'], '/') . '/uploads/';
$zipPath = "{$uploadDir}{$nameSanitized}.zip";

// Dossier de destination local → à adapter selon ton arborescence serveur
$baseDestination = rtrim($_ENV['PATH_RESOURCES'], '/') . '/';

if ($type === "map") {
    $destinationDir = $baseDestination . 'inactive_map/';
} elseif ($modActif) {
    $destinationDir = $baseDestination . 'Client/';
} else {
    $destinationDir = $baseDestination . 'inactive_mod/';
}

$destinationPath = $destinationDir . "{$nameSanitized}.zip";

// Vérification de l'existence du fichier ZIP
if (!file_exists($zipPath)) {
    echo "data: " . json_encode(['success' => false, 'message' => "Fichier introuvable : {$zipPath}"]) . "\n\n";
    flush();
    exit;
}

// Créer le dossier de destination s'il n'existe pas
if (!is_dir($destinationDir)) {
    if (!mkdir($destinationDir, 0770, true)) {
        echo "data: " . json_encode(['success' => false, 'message' => "Impossible de créer le dossier destination."]) . "\n\n";
        flush();
        exit;
    }
}

// Simulation de progress (car en local, le move est instantané)
// Ici je te simule 3 étapes de 0 → 50 → 100%, juste pour l'affichage progress
for ($progress = 0; $progress <= 100; $progress += 50) {
    echo "data: " . json_encode([
        'progress' => $progress,
        'speed' => 'N/A',
        'time_remaining' => 'N/A'
    ]) . "\n\n";
    flush();
    usleep(300000); // 0.3 sec (simulation progress)
}

// Déplacement du fichier
if (rename($zipPath, $destinationPath)) {
    chmod($destinationPath, 0770);

    echo "data: " . json_encode(['progress' => 100, 'message' => "Fichier déplacé vers {$destinationPath}"]) . "\n\n";
    flush();

    echo "data: " . json_encode(['success' => true, 'message' => 'Transfert terminé et permissions appliquées.']) . "\n\n";
    flush();
} else {
    echo "data: " . json_encode(['success' => false, 'message' => "Erreur lors du déplacement vers {$destinationPath}."]) . "\n\n";
    flush();
    exit;
}

exit;
