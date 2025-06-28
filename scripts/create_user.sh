#!/usr/bin/php
<?php
// Load config from JSON
$configFile = __DIR__ . '/config.json';
if (!file_exists($configFile)) {
    echo "❌ Configuration file not found: $configFile" . PHP_EOL;
    exit(1);
}

$config = json_decode(file_get_contents($configFile), true);
if (!isset($config['db'])) {
    echo "❌ Invalid config format." . PHP_EOL;
    exit(1);
}

$db_host = $config['db']['host'];
$db_name = $config['db']['name'];
$db_user = $config['db']['user'];
$db_pass = $config['db']['pass'];

// Connect to the database
try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "❌ Database connection error: " . $e->getMessage() . PHP_EOL;
    exit(1);
}

// CLI prompt function
function prompt($text) {
    echo $text . ' ';
    return trim(fgets(STDIN));
}

// Ask user info
$username = prompt("Username:");
$password = prompt("Password:");
$role = prompt("Role (Admin or SuperAdmin):");

// Validate role
$roleInput = strtolower(trim($role));
if ($roleInput === 'superadmin') {
    $role = 'SuperAdmin';
} elseif ($roleInput === 'admin') {
    $role = 'Admin';
} else {
    echo "❌ Invalid role! Choose 'Admin' or 'SuperAdmin'." . PHP_EOL;
    exit(1);
}

// Hash password
$password_hash = password_hash($password, PASSWORD_DEFAULT);

// Insert into users table
try {
    $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, role) VALUES (:username, :password_hash, :role)");
    $stmt->execute([
        ':username' => $username,
        ':password_hash' => $password_hash,
        ':role' => $role
    ]);

    echo "✅ User '$username' created successfully with role '$role'." . PHP_EOL;
} catch (PDOException $e) {
    echo "❌ Insert error: " . $e->getMessage() . PHP_EOL;
    exit(1);
}
