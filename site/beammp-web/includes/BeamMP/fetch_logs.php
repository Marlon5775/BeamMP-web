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

require_once __DIR__ . '/../../includes/roles.php';

$logFilePath = $_ENV['LOG_FILE_PATH'];

if (!file_exists($logFilePath)) {
    http_response_code(404);
    echo "Fichier log non trouvé : {$logFilePath}";
    exit;
}

$linesToRead = 200;
$lines = [];

$handle = fopen($logFilePath, "r");

if ($handle) {
    fseek($handle, 0, SEEK_END);
    $position = ftell($handle);
    $chunk = '';

    while ($position > 0 && count($lines) < $linesToRead) {
        $position -= 1024;
        if ($position < 0) { $position = 0; }
        fseek($handle, $position);

        $chunk = fread($handle, 1024) . $chunk;
        $lines = explode("\n", $chunk);
    }

    fclose($handle);

    $lastLines = array_slice($lines, -$linesToRead);
    echo implode("\n", $lastLines);
} else {
    http_response_code(500);
    echo "Impossible d'ouvrir le fichier log.";
}
?>
