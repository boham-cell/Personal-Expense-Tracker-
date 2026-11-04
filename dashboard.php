<?php
session_start();
require 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit;
}
$user_id = $_SESSION['user_id'];

// Totals for the current month
$stmt = $pdo->prepare(
    "SELECT type, COALESCE(SUM(amount), 0) AS total
     FROM transactions
     WHERE user_id = ? AND MONTH(transaction_date) = MONTH(CURDATE())
       AND YEAR(transaction_date) = YEAR(CURDATE())
     GROUP BY type"
);
$stmt->execute([$user_id]);
$totals = ['income' => 0, 'expense' => 0];
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $totals[$row['type']] = (float) $row['total'];
}
$balance = $totals['income'] - $totals['expense'];

// Five most recent transactions
$stmt = $pdo->prepare(
    "SELECT t.type, t.amount, t.transaction_date, c.name AS category
     FROM transactions t LEFT JOIN categories c ON c.id = t.category_id
     WHERE t.user_id = ?
     ORDER BY t.transaction_date DESC, t.id DESC LIMIT 5"
);
$stmt->execute([$user_id]);
$recent = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0">Hi, <?= htmlspecialchars($_SESSION['full_name']) ?></h3>
        <div>
            <a href="transactions/add.php" class="btn btn-primary btn-sm">+ Add transaction</a>
            <a href="transactions/list.php" class="btn btn-outline-secondary btn-sm">All transactions</a>
            <a href="auth/logout.php" class="btn btn-outline-secondary btn-sm">Log out</a>
        </div>
    </div>

    <p class="text-muted">This month (<?= date('F Y') ?>)</p>
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card shadow-sm"><div class="card-body">
                <div class="text-muted">Total income</div>
                <h3 class="text-success">GH₵<?= number_format($totals['income'], 2) ?></h3>
            </div></div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm"><div class="card-body">
                <div class="text-muted">Total expenses</div>
                <h3 class="text-danger">GH₵<?= number_format($totals['expense'], 2) ?></h3>
            </div></div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm"><div class="card-body">
                <div class="text-muted">Remaining balance</div>
                <h3 class="<?= $balance < 0 ? 'text-danger' : '' ?>">GH₵<?= number_format($balance, 2) ?></h3>
            </div></div>
        </div>
    </div>

    <h5>Recent transactions</h5>
    <div class="card shadow-sm">
        <table class="table mb-0">
            <tbody>
            <?php if (empty($recent)): ?>
                <tr><td class="text-center text-muted py-3">Nothing recorded yet.</td></tr>
            <?php endif; ?>
            <?php foreach ($recent as $t): ?>
                <tr>
                    <td><?= htmlspecialchars($t['transaction_date']) ?></td>
                    <td><?= htmlspecialchars($t['category'] ?? '-') ?></td>
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
