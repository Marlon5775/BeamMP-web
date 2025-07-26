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

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../../includes/roles.php';

// Chargement de la langue et des traductions
$supported_langs = ['fr', 'en', 'de'];
$lang = $_SESSION['lang'] ?? $_COOKIE['lang'] ?? $_ENV['LANG_DEFAULT'] ?? 'en';
if (!in_array($lang, $supported_langs)) $lang = 'en';
$translationFile = __DIR__ . "/../../includes/BeamMP/lang/{$lang}.php";
$translations = file_exists($translationFile) ? require $translationFile : require __DIR__ . "/../../includes/BeamMP/lang/en.php";
function t(string $key): string {
    global $translations;
    return $translations[$key] ?? "[[" . $key . "]]";
}

header('Content-Type: application/json');

function handleUpload() {
    global $pdo;

    if (!isset($_SESSION['user_id']) || !hasRole(['SuperAdmin', 'Admin'])) {
        http_response_code(403);
        throw new Exception(t("error_forbidden"));
    }

    $type = $_POST['type'] ?? null;
    $name = preg_replace('/[^a-zA-Z0-9_ ]/', '', $_POST['name'] ?? '');
    $description = $_POST['description'] ?? '';
    $link = $_POST['link'] ?? null;
    $id_map = $_POST['id_map'] ?? null;
    $vehicle_type = $_POST['vehicle_type'] ?? null;
    $mod_actif = isset($_POST['status']) ? 1 : 0;
    $nameSanitized = str_replace(' ', '_', $name);

    if (empty($type) || empty($name)) throw new Exception(t("error_missing_type_or_name"));

    $dataPath = $_ENV['DATA_PATH'] ?? '/var/www/beammp-web/DATA';
    $uploadDir = $dataPath . '/uploads/';
    $imagePath = $dataPath . "/images/{$nameSanitized}.jpg";
    $zipPath = "{$uploadDir}{$nameSanitized}.zip";


    // Description : détection de la langue et génération JSON
    function detectLang($text) {
        if (preg_match('/[éèàêùçôîâëïœ]/i', $text) || preg_match('/\b(le|la|les|un|une|des|est|avec|sur|dans)\b/i', $text)) {
            return 'fr';
        } else {
            return 'en';
        }
    }

    $descriptionContent = empty($description)
        ? t("error_no_description")
        : (preg_match('/(http|https|www)/i', $description) ? throw new Exception(t("error_links_forbidden")) : $description);

    $lang = detectLang($descriptionContent);
    $descriptionJson = [
        'fr' => $lang === 'fr' ? $descriptionContent : '',
        'en' => $lang === 'en' ? $descriptionContent : ''
    ];
    $descriptionJsonFile = $dataPath . "/descriptions/{$nameSanitized}.json";
    file_put_contents($descriptionJsonFile, json_encode($descriptionJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    chmod($descriptionJsonFile, 0770);

    // Image
    if (!empty($_FILES['image']['name'])) {
        $imageTmp = $_FILES['image']['tmp_name'];
        if (move_uploaded_file($imageTmp, $imagePath)) {
            chmod($imagePath, 0770);
        } else {
            throw new Exception(t("error_image_upload"));
        }
    } else {
        throw new Exception(t("error_image_required"));
    }

    // ZIP
    if (!empty($_FILES['zip']['name'])) {
        $zipTmp = $_FILES['zip']['tmp_name'];

        if (mime_content_type($zipTmp) !== 'application/zip') {
            throw new Exception(t("error_invalid_zip"));
        }

        if (move_uploaded_file($zipTmp, $zipPath)) {
            chmod($zipPath, 0770);
        } else {
            throw new Exception(t("error_zip_upload"));
        }
    } else {
        throw new Exception(t("error_zip_required"));
    }

    // Insertion SQL
    $table = $_ENV['BEAMMP_TABLE'] ?? 'beammp';
    $stmt = $pdo->prepare("INSERT INTO $table (nom, description, type, chemin, image, id_map, mod_actif, map_officielle, map_active, vehicule_type, archive, link, date)
                           VALUES (:nom, :description, :type, :chemin, :image, :id_map, :mod_actif, :map_officielle, :map_active, :vehicule_type, :archive, :link, NOW())");

    $stmt->execute([
        ':nom' => $name,
        ':description' => "descriptions/{$nameSanitized}.json",
        ':type' => $type,
        ':chemin' => "{$nameSanitized}.zip",
        ':image' => "images/{$nameSanitized}.jpg",
        ':id_map' => $type === "map" ? $id_map : null,
        ':mod_actif' => $mod_actif,
        ':map_officielle' => $type === "map" ? 0 : null,
        ':map_active' => $type === "map" ? 0 : null,
        ':vehicule_type' => $type === "map" ? null : $vehicle_type,
        ':archive' => $_FILES['zip']['name'],
        ':link' => $link
    ]);

    $baseUrl = rtrim($_ENV['BASE_URL'] ?? '', '/');
    $imageUrl = "{$baseUrl}/DATA/images/{$nameSanitized}.jpg";


    sendWebhookDiscord($name, $descriptionContent, $type, $imageUrl);

    echo json_encode(['success' => true, 'message' => t("success_upload")]);
}

handleUpload();

function sendWebhookDiscord($modName, $description, $type, $imageUrl) {
    $webhookUrl = $_ENV['DISCORD_WEBHOOK_MOD_UPLOAD'] ?? '';

    if (empty($webhookUrl)) return;

    $embed = [
        "title" => t("embed_mod_title"),
        "description" => "**" . t("embed_mod_name") . "** $modName\n**" . t("embed_mod_type") . "** $type\n\n" . strip_tags($description),
        "image" => [ "url" => $imageUrl ],
        "color" => hexdec("3498db"),
        "footer" => [ "text" => t("embed_mod_footer") ],
        "timestamp" => date(DATE_ATOM)
    ];

    $payload = json_encode(["embeds" => [$embed]]);

    $ch = curl_init($webhookUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_exec($ch);
    curl_close($ch);
}
