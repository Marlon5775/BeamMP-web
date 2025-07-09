<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/db.php';

// Vérifier si l'action est une connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username']) && isset($_POST['password'])) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $_SESSION['login_error'] = "Nom d'utilisateur et mot de passe requis.";
        header('Location: ../index.php'); // Redirige vers index.php en cas d'erreur
        exit;
    }

    $stmt = $pdo->prepare("SELECT id, username, password_hash, role FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        
        unset($_SESSION['login_error']); // supprime les erreurs précédentes
        header('Location: ../index.php'); // redirige vers index.php après la connexion
        exit;
    } else {
        $_SESSION['login_error'] = "Nom d'utilisateur ou mot de passe incorrect.";
        header('Location: ../index.php'); // garde la boite de dialogue ouverte
        exit;
    }
}

// Si la méthode n'est pas POST, rediriger vers la page d'accueil
header('Location: ../index.php');
exit;
