<?php
session_start();
require '../config/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

// Build the last 6 months so months with no data still show as 0
$months = [];
for ($i = 5; $i >= 0; $i--) {
    $m = new DateTime('first day of this month');
    $m->modify("-$i month");
    $months[] = $m->format('Y-m');
}

$stmt = $pdo->prepare(
    "SELECT DATE_FORMAT(transaction_date, '%Y-%m') AS ym, type, SUM(amount) AS total
     FROM transactions
     WHERE user_id = ? AND DATE_FORMAT(transaction_date, '%Y-%m') >= ?
     GROUP BY ym, type"
);
$stmt->execute([$_SESSION['user_id'], $months[0]]);

$income  = array_fill_keys($months, 0);
$expense = array_fill_keys($months, 0);
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
    if ($r['type'] === 'income')  $income[$r['ym']]  = (float) $r['total'];
    if ($r['type'] === 'expense') $expense[$r['ym']] = (float) $r['total'];
}

echo json_encode([
    'labels'  => array_map(fn($m) => date('M Y', strtotime($m . '-01')), $months),
    'income'  => array_values($income),
    'expense' => array_values($expense),
]);
