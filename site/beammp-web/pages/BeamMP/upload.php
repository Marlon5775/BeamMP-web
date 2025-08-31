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

require_once __DIR__ . '/../../includes/roles.php';
require_once __DIR__ . '/../../includes/BeamMP/i18n.php';

$title = 'Upload';
$h1Title = $_ENV['TITLE'] ?? 'BeamMP';
$buttons = [
    ['link' => '../BeamMP', 'title' => t('btn_back'), 'icon' => '../../assets/images/BACK.webp']
];
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BeamMP - Upload</title>
    <link rel="stylesheet" href="../../assets/css/upload.css">
    <link rel="stylesheet" href="../../assets/css/styles.css">
    <link rel="stylesheet" href="../../assets/css/root.css">
</head>

<body>
<?php include __DIR__ . '/../../includes/header.php'; ?>

<main>
    <div class="upload-container">
        <form id="uploadForm" action="../../includes/BeamMP/addmod.php" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="type"><?= t('form_type') ?></label>
                <select id="type" name="type" required>
                    <option value="map"><?= t('form_map') ?></option>
                    <option value="vehicule"><?= t('form_vehicle') ?></option>
                    <option value="mod"><?= t('form_mod') ?></option>
                </select>
            </div>
            <div class="form-group">
                <label for="name"><?= t('form_name') ?></label>
                <input type="text" id="name" name="name" placeholder="<?= t('form_name_placeholder') ?>" required>
            </div>
            <div class="form-group">
                <label for="description"><?= t('form_description') ?></label>
                <textarea id="description" name="description" placeholder="<?= t('form_description_placeholder') ?>"></textarea>
            </div>
            <div class="form-group">
                <label for="link"><?= t('form_link') ?></label>
                <input type="url" id="link" name="link" placeholder="<?= t('form_link_placeholder') ?>">
            </div>
            <div class="form-group">
                <label for="image"><?= t('form_image') ?></label>
                <input type="file" id="image" name="image" accept="image/*" required>
            </div>
            <div class="form-group">
                <label for="zip"><?= t('form_zip') ?></label>
                <input type="file" id="zip" name="zip" accept=".zip" required>
            </div>
            <div class="form-group" id="map-id-group" style="display: block;">
                <label for="id_map"><?= t('form_map_id') ?></label>
                <input type="text" id="id_map" name="id_map" placeholder="<?= t('form_map_id_placeholder') ?>">
            </div>
            <div class="form-group" id="vehicle-type-group" style="display: none;">
                <label for="status"><?= t('form_status') ?></label>
                <input type="checkbox" id="status" name="status"> <?= t('form_status_active') ?>
            </div>
            <div class="form-group" id="mod-status-group" style="display: none;">
                <label for="status"><?= t('form_status') ?></label>
                <input type="checkbox" id="status" name="status"> <?= t('form_status_active') ?>
            </div>
            <div class="form-group">
                <button
                    type="<?= hasRole(['Admin', 'SuperAdmin']) ? 'submit' : 'button'; ?>"
                    class="btn btn-upload <?= hasRole(['Testeur', 'LeKevin']) ? 'disabled' : ''; ?>"
                    <?= hasRole(['Admin', 'SuperAdmin']) ? '' : 'onclick="alert(\'' . t('no_permission') . '\')" disabled'; ?>>
                    <?= t('btn_upload') ?>
                </button>
            </div>
            <div class="progress-bar" id="progressBar">
                <div class="progress" id="progress"></div>
            </div>
            <div id="uploadMessage" class="upload-message"></div>
        </form>
    </div>
</main>

<!-- Modale -->
<div id="uploadModal" class="modal" style="display: none;">
    <div class="modal-content">
        <h2>
            <div class="loader">
                <?php for ($i = 1; $i <= 12; $i++): ?>
                    <div class="bar<?= $i ?>"></div>
                <?php endfor; ?>
            </div>
            <span id="modalTitle"><?= t('modal_title') ?></span>
        </h2>

        <div id="progressContainer">
            <div>
                <p id="uploadSection" class="step"><?= t('modal_upload') ?></p>
                <div class="progressBar">
                    <div id="uploadProgressBar" class="progressFill"></div>
                </div>
            </div>
            <div>
                <p id="rsyncSection" class="step"><?= t('modal_transfer') ?></p>
                <div class="progressBar">
                    <div id="rsyncProgressBar" class="progressFill"></div>
                </div>
            </div>
        </div>

        <p id="uploadDetails"><?= t('modal_file_name') ?> : <span id="fileName"></span></p>
        <p id="uploadProgress"><?= t('modal_progress') ?> : <span id="progressPercent">0%</span></p>
        <p id="uploadSpeed"><?= t('modal_speed') ?> : <span id="uploadSpeedText">0 MB/s</span></p>
        <p id="uploadRemaining"><?= t('modal_remaining') ?> : <span id="uploadTimeRemaining">--</span></p>
        <p id="transferStatus" style="display: none;"><?= t('modal_transfer_status') ?> : <span id="rsyncProgress">--</span></p>
        <div id="waitingIndicator" style="display: none;">
            <p><?= t('modal_wait') ?></p>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

<!-- JS -->
 <script>
    const t = <?= json_encode($translations, JSON_UNESCAPED_UNICODE); ?>;
</script>
<?php include __DIR__ . '/../../includes/BeamMP/traductions_js.php'; ?>
<script src="../../assets/js/upload.js"></script>
<script>
    // Force la détection du type une fois le DOM prêt
    document.addEventListener("DOMContentLoaded", () => {
        const typeSelect = document.getElementById("type");
        if (typeSelect) {
            typeSelect.dispatchEvent(new Event("change"));
        }
    });
</script>
</body>
</html>
