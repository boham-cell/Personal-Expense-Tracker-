<?php
session_start();
require '../config/db.php';
require __DIR__ . '/../includes/csrf.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $stmt = $pdo->prepare("DELETE FROM transactions WHERE id = ? AND user_id = ?");
    $stmt->execute([$_POST['id'] ?? 0, $_SESSION['user_id']]);
}

header('Location: list.php');
exit;
