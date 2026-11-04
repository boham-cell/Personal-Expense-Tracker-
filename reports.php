<?php
session_start();
require 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit;
}
$user_id = $_SESSION['user_id'];

// Read and validate the filters
$period = $_GET['period'] ?? 'month';
if (!in_array($period, ['day', 'week', 'month'], true)) {
    $period = 'month';
}
$date = $_GET['date'] ?? date('Y-m-d');
$d = DateTime::createFromFormat('Y-m-d', $date);
if (!$d || $d->format('Y-m-d') !== $date) {
    $d = new DateTime();
    $date = $d->format('Y-m-d');
}

// Work out the start and end of the chosen period
if ($period === 'day') {
    $start = clone $d;
    $end   = clone $d;
} elseif ($period === 'week') {
    $start = (clone $d)->modify('monday this week');
    $end   = (clone $start)->modify('+6 days');
} else {
    $start = (clone $d)->modify('first day of this month');
    $end   = (clone $d)->modify('last day of this month');
}
$from = $start->format('Y-m-d');
$to   = $end->format('Y-m-d');

// Totals
$stmt = $pdo->prepare(
    "SELECT type, COALESCE(SUM(amount), 0) AS total
     FROM transactions
     WHERE user_id = ? AND transaction_date BETWEEN ? AND ?
     GROUP BY type"
);
$stmt->execute([$user_id, $from, $to]);
$totals = ['income' => 0, 'expense' => 0];
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
    $totals[$r['type']] = (float) $r['total'];
}

// Expenses by category
$stmt = $pdo->prepare(
    "SELECT COALESCE(c.name, 'Uncategorised') AS name, SUM(t.amount) AS total
     FROM transactions t LEFT JOIN categories c ON c.id = t.category_id
     WHERE t.user_id = ? AND t.type = 'expense' AND t.transaction_date BETWEEN ? AND ?
     GROUP BY name ORDER BY total DESC"
);
$stmt->execute([$user_id, $from, $to]);
$byCategory = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Every transaction in the period
$stmt = $pdo->prepare(
    "SELECT t.type, t.amount, t.description, t.transaction_date, c.name AS category
     FROM transactions t LEFT JOIN categories c ON c.id = t.category_id
     WHERE t.user_id = ? AND t.transaction_date BETWEEN ? AND ?
     ORDER BY t.transaction_date DESC, t.id DESC"
);
$stmt->execute([$user_id, $from, $to]);
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

$balance = $totals['income'] - $totals['expense'];
$label = $period === 'day'
    ? $start->format('D, j M Y')
    : $start->format('j M Y') . ' – ' . $end->format('j M Y');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reports</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-4" style="max-width: 800px;">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">Reports</h3>
        <a href="dashboard.php" class="btn btn-outline-secondary btn-sm">Dashboard</a>
    </div>

    <form method="GET" class="card shadow-sm mb-3">
        <div class="card-body row g-2 align-items-end">
            <div class="col-sm-4">
                <label class="form-label">Period</label>
                <select name="period" class="form-select">
                    <option value="day"   <?= $period === 'day'   ? 'selected' : '' ?>>Daily</option>
                    <option value="week"  <?= $period === 'week'  ? 'selected' : '' ?>>Weekly</option>
                    <option value="month" <?= $period === 'month' ? 'selected' : '' ?>>Monthly</option>
                </select>
            </div>
            <div class="col-sm-5">
                <label class="form-label">Date within period</label>
                <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($date) ?>">
            </div>
            <div class="col-sm-3">
                <button class="btn btn-primary w-100">Show</button>
            </div>
        </div>
    </form>

    <p class="text-muted"><?= htmlspecialchars($label) ?></p>

    <div class="row g-3 mb-4">
        <div class="col-md-4"><div class="card shadow-sm"><div class="card-body">
            <div class="text-muted">Income</div>
            <h4 class="text-success">GH₵<?= number_format($totals['income'], 2) ?></h4>
        </div></div></div>
        <div class="col-md-4"><div class="card shadow-sm"><div class="card-body">
            <div class="text-muted">Expenses</div>
            <h4 class="text-danger">GH₵<?= number_format($totals['expense'], 2) ?></h4>
        </div></div></div>
        <div class="col-md-4"><div class="card shadow-sm"><div class="card-body">
            <div class="text-muted">Balance</div>
            <h4 class="<?= $balance < 0 ? 'text-danger' : '' ?>">GH₵<?= number_format($balance, 2) ?></h4>
        </div></div></div>
    </div>

    <h5>Expenses by category</h5>
    <div class="card shadow-sm mb-4">
        <table class="table mb-0">
            <tbody>
            <?php if (empty($byCategory)): ?>
                <tr><td class="text-center text-muted py-3">No expenses in this period.</td></tr>
            <?php endif; ?>
            <?php foreach ($byCategory as $c): ?>
                <tr>
                    <td><?= htmlspecialchars($c['name']) ?></td>
                    <td class="text-end">GH₵<?= number_format($c['total'], 2) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <h5>Transactions</h5>
    <div class="card shadow-sm mb-4">
        <table class="table mb-0">
            <tbody>
            <?php if (empty($transactions)): ?>
                <tr><td class="text-center text-muted py-3">No transactions in this period.</td></tr>
            <?php endif; ?>
            <?php foreach ($transactions as $t): ?>
                <tr>
                    <td><?= htmlspecialchars($t['transaction_date']) ?></td>
                    <td><?= htmlspecialchars($t['category'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($t['description'] ?? '') ?></td>
                    <td class="text-end <?= $t['type'] === 'income' ? 'text-success' : 'text-danger' ?>">
                        <?= $t['type'] === 'income' ? '+' : '-' ?>GH₵<?= number_format($t['amount'], 2) ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
