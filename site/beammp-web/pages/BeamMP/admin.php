<?php
// Trouver la racine d'instance
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

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../index.php');
    exit;
}

// ⬇️ Chargement des rôles + traduction
require_once __DIR__ . '/../../includes/roles.php';
require_once __DIR__ . '/../../includes/BeamMP/i18n.php';

// Droits
$isSuperAdmin = hasRole(['SuperAdmin']);
$canViewLogs = hasRole(['SuperAdmin', 'Admin']);

// Variables d'affichage
$title = 'Admin';
$h1Title = $_ENV['TITLE'] ?? 'BeamMP';
$buttons = [
    ['link' => '/BeamMP', 'title' => t('btn_back'), 'icon' => '/assets/images/BACK.png']
];

// Lecture du fichier .env (déjà chargé dans i18n.php)
$configData = [];
$configFile = $_ENV['CONFIG_REMOTE_PATH'] ?? null;

if ($configFile && file_exists($configFile)) {
    $configContent = file($configFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($configContent as $line) {
        if (preg_match('/^(\w+)\s*=\s*"?([^"]+)"?$/', $line, $matches)) {
            $configData[$matches[1]] = $matches[2];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>BeamMP - Admin</title>
    <link rel="stylesheet" href="../../assets/css/root.css" />
    <link rel="stylesheet" href="../../assets/css/admin.css" />
</head>

<body>
    <?php include __DIR__ . '/../../includes/header.php'; ?>

    <main class="admin-main">
        <h2><?= t('admin_logs_title') ?></h2>
        <div class="log-container">
            <div class="scrollbox" id="logScrollbox">
                <?php if ($canViewLogs): ?>
                    <pre id="logContent"><?= t('admin_logs_loading') ?></pre>
                <?php else: ?>
                    <p><?= t('admin_logs_restricted') ?></p>
                <?php endif; ?>
            </div>
        </div>

        <h2><?= t('admin_serverconfig') ?></h2>
        <form id="configForm" class="config-form">
            <?php
            $fields = ['MaxPlayers', 'Description', 'Tags', 'Port', 'Name', 'MaxCars'];
            foreach ($fields as $key): ?>
                <div class="form-group">
                    <label for="<?= $key ?>"><?= t("field_$key") ?: $key ?>:</label>
                    <div class="field-wrapper">
                        <input
                            type="text"
                            name="<?= $key ?>"
                            id="<?= $key ?>"
                            value="<?= htmlspecialchars($configData[$key] ?? '') ?>"
                            class="editable"
                        />
                        <button type="button" class="btn-save" onclick="saveField('<?= $key ?>')"><?= t('btn_save') ?></button>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php
            $switches = ['Private', 'Debug', 'LogChat'];
            foreach ($switches as $switch): ?>
                <div class="form-group">
                    <label for="<?= $switch ?>"><?= t("field_$switch") ?: $switch ?>:</label>
                    <div class="switch-container">
                        <input
                            type="checkbox"
                            id="<?= $switch ?>"
                            name="<?= $switch ?>"
                            class="switch-checkbox editable"
                            <?= (isset($configData[$switch]) && $configData[$switch] === 'true') ? 'checked' : '' ?>
                        />
                        <button type="button" class="btn-save" onclick="saveSwitch('<?= $switch ?>')"><?= t('btn_save') ?></button>
                    </div>
                </div>
            <?php endforeach; ?>
        </form>
    </main>

    <?php include __DIR__ . '/../../includes/footer.php'; ?>
    <?php include __DIR__ . '/../../includes/BeamMP/traductions_js.php'; ?>
    <script src="../../assets/js/admin.js"></script>
</body>
</html>
