<?php
require_once __DIR__ . '/../db.php';

// Fonction pour construire le chemin d'une image
function buildImagePath($image) {
    $pathForImages = '/assets/images/BeamMP';
    $fullImagePath = rtrim($pathForImages, '/') . '/' . ltrim($image, '/');
    return file_exists($_SERVER['DOCUMENT_ROOT'] . $fullImagePath) ? $fullImagePath : '/assets/images/no_image.png';
}

// Fonction pour récupérer le contenu d'un fichier description
function getDescriptionContent($descriptionPath) {
    $pathForDescriptions = '/assets/fichiers/BeamMP';
    $fullDescriptionPath = rtrim($pathForDescriptions, '/') . '/' . ltrim($descriptionPath, '/');

    if (is_file($_SERVER['DOCUMENT_ROOT'] . $fullDescriptionPath)) {
        return file_get_contents($_SERVER['DOCUMENT_ROOT'] . $fullDescriptionPath);
    } else {
        error_log("Description introuvable ou invalide : $fullDescriptionPath");
        return "Description non disponible.";
    }
}

// Fonction générique pour récupérer des éléments de la table `beammp`
function fetchBeamMPData($type, $conditions = [], $sort = 'az') {
    global $pdo;

    $sql = "SELECT * FROM beammp WHERE type = :type";
    $params = ['type' => $type];

    foreach ($conditions as $column => $value) {
        if ($column === 'search') {
            $sql .= " AND LOWER(nom) LIKE :search";
            $params['search'] = '%' . strtolower($value) . '%';
        } else {
            $sql .= " AND $column = :$column";
            $params[$column] = $value;
        }
    }

    // Ajout du tri
    if ($sort === 'az') {
        $sql .= " ORDER BY nom ASC";
    } elseif ($sort === 'za') {
        $sql .= " ORDER BY nom DESC";
    } elseif ($sort === 'recent') {
        $sql .= " ORDER BY date DESC";
    } elseif ($sort === 'oldest') {
        $sql .= " ORDER BY date ASC";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($results as &$item) {
        $item['image'] = buildImagePath($item['image']);
        $item['description'] = getDescriptionContent($item['description']);
    }

    return $results;
}

// Fonction pour récupérer les cartes
function getMaps($mapType = 'all') {
    $conditions['map_active'] = 0;
    if ($mapType === 'official') {
        $conditions['map_officielle'] = 1;
    } elseif ($mapType === 'mod') {
        $conditions['map_officielle'] = 0;
    }

    return fetchBeamMPData('map', $conditions);
}

// Fonction pour récupérer tous les véhicules
function getVehicles($vehicleType = 'all') {
    $conditions = [];
    if ($vehicleType === 'active') {
        $conditions['mod_actif'] = 1;
    } elseif ($vehicleType === 'inactive') {
        $conditions['mod_actif'] = 0;
    }

    return fetchBeamMPData('vehicule', $conditions);
}

// Fonction pour récupérer tous les mods
function getMods($modStatus = 'all') {
    $conditions = [];
    if ($modStatus === 'active') {
        $conditions['mod_actif'] = 1;
    } elseif ($modStatus === 'inactive') {
        $conditions['mod_actif'] = 0;
    }

    return fetchBeamMPData('mod', $conditions);
}

// Fonction pour récupérer la carte active
function getActiveMap() {
    global $pdo;

    $sql = "SELECT nom, description, image FROM beammp WHERE map_active = 1";
    $stmt = $pdo->query($sql);
    $mapInfo = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$mapInfo) {
        return [
            'nom' => 'Aucune carte active',
            'description' => 'Aucune description disponible.',
            'image' => 'assets/images/default_map.png'
        ];
    }

    $mapInfo['image'] = buildImagePath($mapInfo['image']);
    $mapInfo['description'] = getDescriptionContent($mapInfo['description']);

    return $mapInfo;
}

// Fonction pour récupérer les véhicules actifs
function getActiveVehicules() {
    $results = fetchBeamMPData('vehicule', ['mod_actif' => 1]);
    // error_log(print_r($results, true)); // Debug uniquement
    return $results;
}

// Fonction pour récupérer les mods actifs
function getActiveMods() {
    return fetchBeamMPData('mod', ['mod_actif' => 1]);
}

// Fonction pour compter les véhicules actifs et inactifs
function getVehiculeCounts() {
    global $pdo;
    $sql = "SELECT 
                SUM(mod_actif = 1) AS active_count,
                SUM(mod_actif = 0) AS inactive_count
            FROM beammp
            WHERE type = 'vehicule'";

    $stmt = $pdo->query($sql);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

?>
