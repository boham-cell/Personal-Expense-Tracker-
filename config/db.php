<?php
$host = 'localhost';
$db   = 'expense_tracker';
$user = 'root';
$pass = '';

try {
    //phps way of communicating with databases like mysql or mariadb
    //used everywhere the database is needed 
    //pdo lets you write a query with placeholders and pass the values separately,
    //whic protects you from sql injection, the most common way databases get attacked. 
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
