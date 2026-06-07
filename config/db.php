<?php
// db.php - MySQL Database connection and initialization

// Start session centrally here before any output
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host = '127.0.0.1:3307';
$user = 'root';
$pass = '';
$dbname = 'chibicon_db';

try {
    // Connect to the server first
    $db = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Create database if not exists and select it
    $db->exec("CREATE DATABASE IF NOT EXISTS `$dbname`");
    $db->exec("USE `$dbname`");

    // We no longer auto-initialize tables here since the schema is large and normalized.
    // Please import chibicon_db.sql directly if the tables are missing.

} catch (PDOException $e) {
    die("Database Connection failed: " . $e->getMessage() . "<br><br><b>Tips:</b> Make sure XAMPP/MAMP MySQL is running. If your password is not empty, please update \$pass in db.php.");
}

// Utility function for consistent response formatting
function format_currency($amount)
{
    return 'Rp ' . number_format($amount, 0, ',', '.');
}
?>