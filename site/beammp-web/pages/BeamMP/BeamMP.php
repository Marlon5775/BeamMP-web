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
require_once __DIR__ . '/../../includes/BeamMP/shared_functions.php';
require_once __DIR__ . '/../../includes/BeamMP/i18n.php';

$isSuperAdmin = hasRole(['SuperAdmin']);
$isAdmin = hasRole(['SuperAdmin', 'Admin']);

$title = 'BeamMP';
$h1Title = $_ENV['TITLE'] ?? 'BeamMP';
$buttons = [
    ['link' => '/Upload', 'title' => t('btn_add_mod'), 'icon' => '/assets/images/ADD.png'],
    ['link' => '/Admin', 'title' => t('btn_logs'), 'icon' => '/assets/images/LOG.png'],
];
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BeamMP</title>
    <link rel="stylesheet" href="../../assets/css/beammp.css">
    <link rel="stylesheet" href="../../assets/css/root.css">
</head>

<body>
    <?php include __DIR__ . '/../../includes/header.php'; ?>

    <main>
        <?php
        $mapInfo = getActiveMap();
        $imagePath = htmlspecialchars($mapInfo['image']);
        ?>
        <div class="refresh-serveur">
            <button class="btn btn refresh" onclick="refreshServer()"<?= $isAdmin ? '' : 'disabled' ?>><?= t('btn_refresh') ?></button>
        </div>

        <div class="mapactive-box">
            <div class="mapactive-image-container">
                <img src="<?= $imagePath ?>" alt="Map image">
            </div>
            <div class="mapactive-info-container">
                <h2><?= htmlspecialchars($mapInfo['nom']) ?></h2>
                <?php
                    $description = 'Description non disponible';
                    try {
                        $descArray = json_decode($mapInfo['description'], true);
                        if (is_array($descArray) && isset($descArray[$lang])) {
                            $description = nl2br(htmlspecialchars($descArray[$lang]));
                        }
                    } catch (Exception $e) {
                        // Optionnel : log erreur ou fallback
                    }
                    ?>
                    <p><?= $description ?></p>
                <button class="change-map-button" onclick="openMapModal()"><?= t('btn_change_map') ?></button>
            </div>

            <!-- Modal -->
            <div id="mapModal" class="modal">
                <div class="modal-backdrop" onclick="closeMapModal()"></div>
                <div class="modal-content">
                    <div class="modal-header">
                        <h2><?= t('modal_change_map') ?></h2>
                        <select id="mapTypeSelector" onchange="filterMaps()">
                            <option value="all"><?= t('filter_all') ?></option>
                            <option value="official"><?= t('filter_official') ?></option>
                            <option value="mod"><?= t('filter_mod') ?></option>
                        </select>
                    </div>
                    <div id="mapList" class="modal-body"></div>
                    <div class="modal-footer">
                        <button class="btn btn-close" onclick="closeMapModal()"><?= t('btn_close') ?></button>
                    </div>
                </div>
            </div>
        </div>

        <?php
        $vehicules = getActiveVehicules();
        $mods = getActiveMods();
        ?>

        <!-- Véhicules -->
        <div class="scroll-box-container">
            <div class="scroll-box-header">
                <h2><?= t('section_vehicles') ?></h2>
                <div class="summary-counters">
                    <span><?= t('total') ?> : <span id="vehiculeTotalCount">0</span></span> |
                    <span><?= t('active') ?> : <span id="vehiculeActiveCount">0</span></span> |
                    <span><?= t('inactive') ?> : <span id="vehiculeInactiveCount">0</span></span>
                </div>
                <div class="filters">
                    <div class="filter-left">
                        <select id="vehiculeStatusFilter" class="filter-btn" onchange="applyStatusFilter('vehicule')">
                            <option value="all"><?= t('filter_all') ?></option>
                            <option value="active"><?= t('active') ?></option>
                            <option value="inactive"><?= t('inactive') ?></option>
                        </select>
                    </div>
                    <div class="filter-right">
                        <input type="text" id="vehiculeSearchBox" class="vehicule-seach-box search-box" placeholder="<?= t('search') ?>" oninput="applySearch('vehicule')" />
                        <select id="vehiculeSortFilter" class="sort-select" onchange="applySort('vehicule', this.value)">
                            <option value="az"><?= t('sort_az') ?></option>
                            <option value="za"><?= t('sort_za') ?></option>
                            <option value="recent"><?= t('sort_recent') ?></option>
                            <option value="oldest"><?= t('sort_oldest') ?></option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="scroll-box vehicule-scroll-box"></div>
        </div>

        <!-- Mods -->
        <div class="scroll-box-container">
            <div class="scroll-box-header">
                <h2><?= t('section_mods') ?></h2>
                <div class="summary-counters">
                    <span><?= t('total') ?> : <span id="modTotalCount">0</span></span> |
                    <span><?= t('active') ?> : <span id="modActiveCount">0</span></span> |
                    <span><?= t('inactive') ?> : <span id="modInactiveCount">0</span></span>
                </div>
                <div class="filters">
                    <div class="filter-left">
                        <select id="modStatusFilter" class="filter-btn" onchange="applyStatusFilter('mod')">
                            <option value="all"><?= t('filter_all') ?></option>
                            <option value="active"><?= t('active') ?></option>
                            <option value="inactive"><?= t('inactive') ?></option>
                        </select>
                    </div>
                    <div class="filter-right">
                        <input type="text" id="modSearchBox" class="mod-search-box search-box" placeholder="<?= t('search') ?>" oninput="applySearch('mod')" />
                        <select id="modSortFilter" class="sort-select" onchange="applySort('mod', this.value)">
                            <option value="az"><?= t('sort_az') ?></option>
                            <option value="za"><?= t('sort_za') ?></option>
                            <option value="recent"><?= t('sort_recent') ?></option>
                            <option value="oldest"><?= t('sort_oldest') ?></option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="scroll-box mod-scroll-box"></div>
        </div>

        <!-- Modale suppression -->
        <div id="deleteConfirmationBox" class="delete-confirmation">
            <div class="delete-confirmation-content">
                <p id="deleteConfirmationMessage"><?= t('confirm_delete') ?></p>
                <div class="delete-confirmation-buttons">
                    <button id="confirmDeleteButton" class="btn btn-danger"><?= t('btn_confirm') ?></button>
                    <button id="cancelDeleteButton" class="btn btn-secondary"><?= t('btn_cancel') ?></button>
                </div>
            </div>
        </div>
    </main>

    <?php include __DIR__ . '/../../includes/footer.php'; ?>

    <script>
        const isAdmin = <?= json_encode($isAdmin); ?>;
        const isSuperAdmin = <?= json_encode($isSuperAdmin); ?>;
    </script>
    <script>const currentLang = '<?= $lang ?>';</script>
    <?php include __DIR__ . '/../../includes/BeamMP/traductions_js.php'; ?>
    <script src="../../assets/js/BeamMP.js"></script>

</body>
</html>
