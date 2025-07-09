<?php
require_once __DIR__ . '/../../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

header('Content-Type: application/json');

try {
    $configPath = $_ENV['CONFIG_REMOTE_PATH'];
    if (!file_exists($configPath)) {
        throw new Exception("Le fichier ServerConfig.toml est introuvable.");
    }

    // Lecture du fichier TOML
    $configLines = file($configPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    // Lecture du POST
    $input = json_decode(file_get_contents('php://input'), true);
    $keyToUpdate = $input['key'] ?? null;
    $newValue = $input['value'] ?? null;

    if (!$keyToUpdate || $newValue === null) {
        throw new Exception("Clé ou valeur manquante.");
    }

    // Correspondance section -> clé
    $mapSections = [
        // Section General
        'Description' => 'General',
        'MaxCars' => 'General',
        'LogChat' => 'General',
        'ResourceFolder' => 'General',
        'IP' => 'General',
        'Name' => 'General',
        'Private' => 'General',
        'InformationPacket' => 'General',
        'AllowGuests' => 'General',
        'Port' => 'General',
        'Debug' => 'General',
        'Tags' => 'General',
        'AuthKey' => 'General',
        'MaxPlayers' => 'General',
        'Map' => 'General',

        // Section Misc
        'UpdateReminderTime' => 'Misc',
        'ImScaredOfUpdates' => 'Misc',
    ];

    if (!isset($mapSections[$keyToUpdate])) {
        throw new Exception("La clé '$keyToUpdate' est inconnue.");
    }

    $sectionTarget = $mapSections[$keyToUpdate];
    $currentSection = null;
    $lineFound = false;

    // Modifier la ligne correspondante
    foreach ($configLines as &$line) {
        if (preg_match('/^\[(.*?)\]/', $line, $matches)) {
            $currentSection = trim($matches[1]);
        }

        if ($currentSection === $sectionTarget && preg_match('/^' . preg_quote($keyToUpdate, '/') . '\s*=\s*(.*)$/', $line)) {
            // Déterminer si on écrit une string, un booléen ou un nombre
            if (is_numeric($newValue)) {
                $formattedValue = $newValue;
            } elseif (in_array(strtolower($newValue), ['true', 'false'])) {
                $formattedValue = strtolower($newValue);
            } else {
                $formattedValue = '"' . addslashes($newValue) . '"';
            }

            $line = "$keyToUpdate = $formattedValue";
            $lineFound = true;
            break;
        }
    }

    if (!$lineFound) {
        throw new Exception("Impossible de trouver la clé '$keyToUpdate' dans la section '$sectionTarget'.");
    }

    // Sauvegarde du fichier
    if (file_put_contents($configPath, implode("\n", $configLines) . "\n") === false) {
        throw new Exception("Impossible d'écrire dans ServerConfig.toml.");
    }

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
