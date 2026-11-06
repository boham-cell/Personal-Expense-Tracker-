<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}
$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare(
    "SELECT t.id, t.type, t.amount, t.description, t.transaction_date, c.name AS category
     FROM transactions t
     LEFT JOIN categories c ON c.id = t.category_id
     WHERE t.user_id = ?
     ORDER BY t.transaction_date DESC, t.id DESC"
);
$stmt->execute([$user_id]);
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Transactions</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include __DIR__ . '/../includes/navbar.php'; ?>
<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">Transactions</h3>
        <div>
            <a href="add.php" class="btn btn-primary btn-sm">+ Add</a>
            <a href="../dashboard.php" class="btn btn-outline-secondary btn-sm">Dashboard</a>
        </div>
    </div>

    <?php if (isset($_GET['added'])): ?>
        <div class="alert alert-success py-2">Transaction saved.</div>
    <?php endif; ?>
    <?php if (isset($_GET['updated'])): ?>
        <div class="alert alert-success py-2">Transaction updated.</div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>Date</th><th>Type</th><th>Category</th>
                    <th>Note</th><th class="text-end">Amount</th><th></th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($transactions)): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">No transactions yet.</td></tr>
            <?php endif; ?>
            <?php foreach ($transactions as $t): ?>
                <tr>
                    <td><?= htmlspecialchars($t['transaction_date']) ?></td>
                    <td>
                        <span class="badge bg-<?= $t['type'] === 'income' ? 'success' : 'danger' ?>">
                            <?= htmlspecialchars($t['type']) ?>
                        </span>
                    </td>
                    <td><?= htmlspecialchars($t['category'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($t['description'] ?? '') ?></td>
                    <td class="text-end">GH₵<?= number_format($t['amount'], 2) ?></td>
                    <td class="text-end text-nowrap">
                        <a href="edit.php?id=<?= (int) $t['id'] ?>" class="btn btn-sm btn-outline-secondary">Edit</a>
                        <form method="POST" action="delete.php" class="d-inline"
                            onsubmit="return confirm('Delete this transaction?');">
                            <input type="hidden" name="id" value="<?= (int) $t['id'] ?>">
                            <button class="btn btn-sm btn-outline-danger">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
