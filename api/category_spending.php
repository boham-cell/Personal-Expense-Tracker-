<?php
session_start();
require '../config/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

$stmt = $pdo->prepare(
    "SELECT COALESCE(c.name, 'Uncategorised') AS label, SUM(t.amount) AS total
     FROM transactions t
     LEFT JOIN categories c ON c.id = t.category_id
     WHERE t.user_id = ? AND t.type = 'expense'
       AND DATE_FORMAT(t.transaction_date, '%Y-%m') = ?
     GROUP BY label
     ORDER BY total DESC"
);
$stmt->execute([$_SESSION['user_id'], date('Y-m')]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'labels' => array_column($rows, 'label'),
    'values' => array_map('floatval', array_column($rows, 'total')),
]);
