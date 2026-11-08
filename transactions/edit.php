<?php
session_start();
require '../config/db.php';
require __DIR__ . '/../includes/csrf.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}
$user_id = $_SESSION['user_id'];
$errors  = [];

// Which transaction? (from the link, or from the form when saving)
$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);

// Load it, making sure it belongs to this user
$stmt = $pdo->prepare("SELECT * FROM transactions WHERE id = ? AND user_id = ?");
$stmt->execute([$id, $user_id]);
$tx = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$tx) {
    header('Location: list.php');
    exit;
}

// This user's categories for the dropdown
$stmt = $pdo->prepare("SELECT id, name FROM categories WHERE user_id = ? ORDER BY name");
$stmt->execute([$user_id]);
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
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
            "UPDATE transactions
             SET category_id = ?, type = ?, amount = ?, description = ?, transaction_date = ?
             WHERE id = ? AND user_id = ?"
        );
        $stmt->execute([$category_value, $type, $amount, $description, $date, $id, $user_id]);

        header('Location: list.php?updated=1');
        exit;
    }

    // On error, show what the user just typed instead of the saved values
    $tx['type']             = $type;
    $tx['amount']           = $amount;
    $tx['category_id']      = $category_id;
    $tx['transaction_date'] = $date;
    $tx['description']      = $description;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit transaction</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include __DIR__ . '/../includes/navbar.php'; ?>
<div class="container" style="max-width: 480px;">
    <div class="card shadow-sm">
        <div class="card-body p-4">
            <h3 class="mb-3">Edit transaction</h3>

            <?php foreach ($errors as $error): ?>
                <div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div>
            <?php endforeach; ?>

            <form method="POST">
            <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int) $tx['id'] ?>">

                <div class="mb-3">
                    <label class="form-label">Type</label>
                    <select name="type" class="form-select" required>
                        <option value="expense" <?= $tx['type'] === 'expense' ? 'selected' : '' ?>>Expense</option>
                        <option value="income"  <?= $tx['type'] === 'income'  ? 'selected' : '' ?>>Income</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Amount (GH₵)</label>
                    <input type="number" name="amount" step="0.01" min="0.01" class="form-control"
                           value="<?= htmlspecialchars($tx['amount']) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Category <small class="text-muted">(optional for income)</small></label>
                    <select name="category_id" class="form-select">
                        <option value="">-- None --</option>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= (int) $c['id'] ?>"
                                <?= (string) $tx['category_id'] === (string) $c['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Date</label>
                    <input type="date" name="transaction_date" class="form-control"
                           value="<?= htmlspecialchars($tx['transaction_date']) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Note</label>
                    <input type="text" name="description" maxlength="255" class="form-control"
                           value="<?= htmlspecialchars($tx['description'] ?? '') ?>">
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1">Save changes</button>
                    <a href="list.php" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
