<?php
// Trouver dynamiquement la racine d'instance (identique partout)
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

// Ensuite, tu fais ton require DB comme avant :
require_once __DIR__ . '/../includes/db.php';

// Vérifier si l'action est une connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username']) && isset($_POST['password'])) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $_SESSION['login_error'] = "Nom d'utilisateur et mot de passe requis.";
        header('Location: ../index.php');
        exit;
    }

    $stmt = $pdo->prepare("SELECT id, username, password_hash, role FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];

        unset($_SESSION['login_error']);
        header('Location: ../index.php');
        exit;
    } else {
        $_SESSION['login_error'] = "Nom d'utilisateur ou mot de passe incorrect.";
        header('Location: ../index.php');
        exit;
    }
}

header('Location: ../index.php');
exit;
