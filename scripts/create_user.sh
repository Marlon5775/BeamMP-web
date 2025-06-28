#!/usr/bin/php
<?php
// CONFIGURATION DE LA BASE DE DONNÉES (en dur ici)
$db_host = 'localhost';
$db_name = 'beammp_db';
$db_user = 'beammp_web';
$db_pass = 'beammp';

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "❌ Erreur connexion BDD : " . $e->getMessage() . PHP_EOL;
    exit(1);
}

// Fonction prompt CLI
function prompt($text) {
    echo $text . ' ';
    return trim(fgets(STDIN));
}

// Demander les infos
$username = prompt("Username :");
$password = prompt("Password :");
$role = prompt("Role (Admin ou SuperAdmin) :");

// Vérifier le rôle
$roleInput = strtolower(trim($role));
if ($roleInput === 'superadmin') {
    $role = 'SuperAdmin';
} elseif ($roleInput === 'admin') {
    $role = 'Admin';
} else {
    echo "❌ Rôle invalide ! Choisir 'Admin' ou 'SuperAdmin'." . PHP_EOL;
    exit(1);
}

// Hasher le mot de passe
$password_hash = password_hash($password, PASSWORD_DEFAULT);

// Insérer dans la table users
try {
    $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, role) VALUES (:username, :password_hash, :role)");
    $stmt->execute([
        ':username' => $username,
        ':password_hash' => $password_hash,
        ':role' => $role
    ]);

    echo "✅ Utilisateur '$username' créé avec succès avec le rôle '$role'." . PHP_EOL;
} catch (PDOException $e) {
    echo "❌ Erreur lors de l'insertion : " . $e->getMessage() . PHP_EOL;
    exit(1);
}
