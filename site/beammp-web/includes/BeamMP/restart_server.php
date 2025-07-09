<?php
session_start();

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../includes/roles.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/BeamMP/i18n.php';

// Sécurité : vérification des rôles autorisés
if (!isset($_SESSION['user_id']) || !hasRole(['SuperAdmin', 'Admin'])) {
    http_response_code(403);
    exit('Accès interdit.');
}

// Chargement des variables d’environnement (optionnel ici si déjà fait dans i18n)
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

// Récupération des variables d’environnement
$webhookUrl = $_ENV['DISCORD_WEBHOOK_SERVER_RESTART'] ?? '';
$baseUrl = rtrim($_ENV['BASE_URL'] ?? '', '/');

if (!isset($_SESSION['user_id']) || !hasRole(['SuperAdmin', 'Admin'])) {
    http_response_code(403);
    exit('Accès interdit.');
}

$webhookUrl = $_ENV['DISCORD_WEBHOOK_SERVER_RESTART'] ?? '';
$baseUrl = rtrim($_ENV['BASE_URL'] ?? '', '/');

try {
    exec('sudo systemctl restart BeamMP.service && sudo systemctl restart joueurs.service', $output, $return_var);

    if ($return_var === 0) {
        global $pdo;

        $stmt = $pdo->prepare("SELECT nom, image FROM beammp WHERE map_active=1 LIMIT 1");
        $stmt->execute();
        $mapData = $stmt->fetch();
        $activeMap = $mapData['nom'] ?? 'Aucune map active';
        $mapImageUrl = isset($mapData['image']) && !empty($baseUrl)
            ? $baseUrl . "/assets/images/BeamMP/" . $mapData['image']
            : null;

        $stmt = $pdo->prepare("SELECT nom FROM beammp WHERE mod_actif=1 AND type='vehicule'");
        $stmt->execute();
        $vehicles = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $stmt = $pdo->prepare("SELECT COUNT(*) as mod_count FROM beammp WHERE mod_actif=1 AND type='mod'");
        $stmt->execute();
        $modCount = $stmt->fetchColumn();

        $embed = [
            'title' => t('embed_title'),
            'description' => sprintf(t('embed_description'), htmlspecialchars($activeMap)),
            'color' => 3447003,
            'fields' => [
                [
                    'name' => t('embed_mods_title'),
                    'value' => sprintf(t('embed_mods_value'), $modCount),
                    'inline' => true,
                ],
                [
                    'name' => t('embed_vehicles_title'),
                    'value' => sprintf(t('embed_vehicles_value'), count($vehicles)),
                    'inline' => true,
                ],
            ],
            'footer' => ['text' => t('embed_footer')],
            'timestamp' => gmdate('c'),
        ];

        if (!empty($vehicles)) {
            $vehicleList = array_map(fn($v) => "- " . htmlspecialchars($v), $vehicles);
            $vehicleText = implode("\n", $vehicleList);
            if (strlen($vehicleText) > 1024) {
                $vehicleText = substr($vehicleText, 0, 1020) . "\n...";
            }

            $embed['fields'][] = [
                'name' => t('embed_vehicle_list'),
                'value' => $vehicleText,
                'inline' => false,
            ];
        }

        if ($mapImageUrl) {
            $embed['image'] = ['url' => $mapImageUrl];
        }

        $data = ['content' => '', 'embeds' => [$embed]];
        $jsonData = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if (!empty($webhookUrl)) {
            $ch = curl_init($webhookUrl);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 204) {
                echo json_encode(['success' => true, 'message' => t('server_restarted_and_discord_sent')]);
            } else {
                echo json_encode(['success' => false, 'message' => t('discord_error'), 'response' => $response]);
            }
        } else {
            echo json_encode(['success' => true, 'message' => t('server_restarted_no_webhook')]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => t('server_restart_error'), 'output' => $output]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
