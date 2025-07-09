<?php
function hasRole($roles) {
    return isset($_SESSION['role']) && in_array($_SESSION['role'], $roles);
}

function getImagePath($imagePath, $defaultPath) {
    return file_exists($imagePath) ? htmlspecialchars($imagePath) : htmlspecialchars($defaultPath);
}

?>
