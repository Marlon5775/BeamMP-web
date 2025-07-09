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

$pathBase = $_ENV['PATH_RESOURCES'];

$data = json_decode(file_get_contents('php://input'), true);
$nom = $data['nom'] ?? null;
$type = $data['type'] ?? null;

if (!$nom || !$type) {
    echo json_encode(['success' => false, 'message' => 'Paramètres manquants.']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT mod_actif, chemin FROM beammp WHERE nom = :nom AND type = :type");
    $stmt->execute(['nom' => $nom, 'type' => $type]);
    $currentDataList = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($currentDataList)) {
        echo json_encode(['success' => false, 'message' => "Aucun élément trouvé."]);
        exit;
    }

    $currentStatus = $currentDataList[0]['mod_actif'];
    $newStatus = $currentStatus == 1 ? 0 : 1;

    $updateStmt = $pdo->prepare("UPDATE beammp SET mod_actif = :newStatus WHERE nom = :nom AND type = :type");
    $updateStmt->execute(['newStatus' => $newStatus, 'nom' => $nom, 'type' => $type]);

    if ($updateStmt->rowCount() >= 1) {
        foreach ($currentDataList as $currentData) {
            $currentPath = $currentData['chemin'];
            $inactivePath = $pathBase . "inactive_mod/" . $currentPath;
            $activePath = $pathBase . "Client/" . $currentPath;

            if ($newStatus == 1) {
                // Activation : de inactive → Client
                if (file_exists($inactivePath)) {
                    rename($inactivePath, $activePath);
                }
            } else {
                // Désactivation : de Client → inactive
                if (file_exists($activePath)) {
                    rename($activePath, $inactivePath);
                }
            }
        }

        echo json_encode(['success' => true, 'message' => 'Statut mis à jour avec succès.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Aucun changement effectué.']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour.']);
}
