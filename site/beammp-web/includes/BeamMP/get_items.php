<?php
require_once __DIR__ . '/../../includes/BeamMP/shared_functions.php';

$type = $_GET['type'] ?? 'all';
$filter = $_GET['filter'] ?? 'all';
$search = $_GET['search'] ?? '';
$sort = $_GET['sort'] ?? 'az';

$conditions = [];

// Applique le filtre actif/inactif
if ($filter !== 'all') {
    $conditions['mod_actif'] = $filter === 'active' ? 1 : 0;
}

// Applique la recherche
if (!empty($search)) {
    $conditions['search'] = $search;
}

$items = fetchBeamMPData($type, $conditions, $sort);

header('Content-Type: application/json');
echo json_encode($items);
