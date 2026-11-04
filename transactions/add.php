<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}
$user_id = $_SESSION['user_id'];
$errors = [];

// Load this user's categories for the dropdown
$stmt = $pdo->prepare("SELECT id, name FROM categories WHERE user_id = ? ORDER BY name");
$stmt->execute([$user_id]);
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type        = $_POST['type'] ?? '';
    $amount      = $_POST['amount'] ?? '';
    $category_id = $_POST['category_id'] ?? '';
    $date        = $_POST['transaction_date'] ?? '';
    $description = trim($_POST['description'] ?? '');

    if (!in_array($type, ['income', 'expense'], true)) {
        $errors[] = 'Choose income or expense.';
    }
    if (!is_numeric($amount) || $amount <= 0) {
        $errors[] = 'Amount must be a number greater than 0.';
    }
    $d = DateTime::createFromFormat('Y-m-d', $date);
    if (!$d || $d->format('Y-m-d') !== $date) {
        $errors[] = 'Enter a valid date.';
    }

    // Expenses need a category; make sure it belongs to this user
    $category_value = null;
    if ($category_id !== '') {
        $stmt = $pdo->prepare("SELECT id FROM categories WHERE id = ? AND user_id = ?");
        $stmt->execute([$category_id, $user_id]);
        if (!$stmt->fetch()) {
            $errors[] = 'Invalid category.';
        } else {
            $category_value = $category_id;
        }
    } elseif ($type === 'expense') {
        $errors[] = 'Choose a category for the expense.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare(
            "INSERT INTO transactions (user_id, category_id, type, amount, description, transaction_date)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([$user_id, $category_value, $type, $amount, $description, $date]);

        header('Location: list.php?added=1');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Add transaction</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container" style="max-width: 480px;">
    <div class="card shadow-sm mt-5">
        <div class="card-body p-4">
            <h3 class="mb-3">Add transaction</h3>

            <?php foreach ($errors as $error): ?>
                <div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div>
            <?php endforeach; ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Type</label>
                    <select name="type" class="form-select" required>
                        <option value="expense" <?= ($_POST['type'] ?? '') === 'expense' ? 'selected' : '' ?>>Expense</option>
                        <option value="income" <?= ($_POST['type'] ?? '') === 'income' ? 'selected' : '' ?>>Income</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Amount (GH₵)</label>
                    <input type="number" name="amount" step="0.01" min="0.01" class="form-control"
                           value="<?= htmlspecialchars($_POST['amount'] ?? '') ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Category <small class="text-muted">(optional for income)</small></label>
                    <select name="category_id" class="form-select">
                        <option value="">-- None --</option>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= (int) $c['id'] ?>"
                                <?= ($_POST['category_id'] ?? '') == $c['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Date</label>
                    <input type="date" name="transaction_date" class="form-control"
                           value="<?= htmlspecialchars($_POST['transaction_date'] ?? date('Y-m-d')) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Note</label>
                    <input type="text" name="description" maxlength="255" class="form-control"
                           value="<?= htmlspecialchars($_POST['description'] ?? '') ?>">
                </div>
                <button type="submit" class="btn btn-primary w-100">Save</button>
            </form>

            <p class="mt-3 mb-0 text-center">
                <a href="list.php">View transactions</a> ·
                <a href="../dashboard.php">Dashboard</a>
            </p>
        </div>
    </div>
</div>
</body>
</html>
