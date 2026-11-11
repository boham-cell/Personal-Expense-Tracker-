<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}
$user_id = $_SESSION['user_id'];

// Optional date range: export.php?from=2026-11-01&to=2026-11-30
function valid_date(?string $s): ?string {
    if (!$s) return null;
    $d = DateTime::createFromFormat('Y-m-d', $s);
    return ($d && $d->format('Y-m-d') === $s) ? $s : null;
}
$from = valid_date($_GET['from'] ?? null);
$to   = valid_date($_GET['to'] ?? null);

$sql = "SELECT t.transaction_date, t.type, c.name AS category, t.description, t.amount
        FROM transactions t
        LEFT JOIN categories c ON c.id = t.category_id
        WHERE t.user_id = ?";
$params = [$user_id];

if ($from) { $sql .= " AND t.transaction_date >= ?"; $params[] = $from; }
if ($to)   { $sql .= " AND t.transaction_date <= ?"; $params[] = $to; }
$sql .= " ORDER BY t.transaction_date DESC, t.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

// File name, e.g. transactions_2026-11-11.csv
$filename = 'transactions_' . date('Y-m-d') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$out = fopen('php://output', 'w');

// Byte order mark so Excel reads accents and symbols correctly
fwrite($out, "\xEF\xBB\xBF");

fputcsv($out, ['Date', 'Type', 'Category', 'Note', 'Amount (GHS)'], ',', '"', '');

// Stop spreadsheet programs from running text as a formula
function safe_cell(?string $v): string {
    $v = $v ?? '';
    return ($v !== '' && strpbrk($v[0], "=+-@\t\r") !== false) ? "'" . $v : $v;
}

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($out, [
        $row['transaction_date'],
        $row['type'],
        safe_cell($row['category'] ?? ''),
        safe_cell($row['description'] ?? ''),
        number_format((float) $row['amount'], 2, '.', ''),
    ], ',', '"', '');
}

fclose($out);
exit;
