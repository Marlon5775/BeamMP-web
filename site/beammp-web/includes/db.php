<?php
require_once __DIR__ . '/../vendor/autoload.php';


use Dotenv\Dotenv; 


$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

$host = $_ENV['DB_HOST'];
$db = $_ENV['DB_NAME'];
$user = $_ENV['DB_USER'];
$pass = $_ENV['DB_PASSWORD'];

try {
    // tentative de connexion à la base de données
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // si la connexion réussis 
    //echo "connexion a la base de données réussis";

} catch (PDOException $e) {
    // si une erreur se produit, afficher un message d'erreur
    die("Database connection failed: " . $e->getMessage());
}
?>