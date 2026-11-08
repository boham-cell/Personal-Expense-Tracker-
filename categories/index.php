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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    $id     = (int) ($_POST['id'] ?? 0);
    $name   = trim($_POST['name'] ?? '');

    if ($action === 'add' || $action === 'rename') {
        if ($name === '' || mb_strlen($name) > 50) {
            $errors[] = 'Category name must be 1 to 50 characters.';
        } else {
            // Block duplicate names for this user (ignoring the row being renamed)
            $stmt = $pdo->prepare(
                "SELECT id FROM categories WHERE user_id = ? AND name = ? AND id <> ?"
            );
            $stmt->execute([$user_id, $name, $id]);
            if ($stmt->fetch()) {
                $errors[] = 'You already have a category with that name.';
            }
        }

        if (empty($errors)) {
            if ($action === 'add') {
                $stmt = $pdo->prepare("INSERT INTO categories (user_id, name) VALUES (?, ?)");
                $stmt->execute([$user_id, $name]);
                header('Location: index.php?msg=added');
            } else {
                $stmt = $pdo->prepare("UPDATE categories SET name = ? WHERE id = ? AND user_id = ?");
                $stmt->execute([$name, $id, $user_id]);
                header('Location: index.php?msg=renamed');
            }
            exit;
        }
    } elseif ($action === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $user_id]);
        header('Location: index.php?msg=deleted');
        exit;
    }
}

// Each category with how many transactions use it
$stmt = $pdo->prepare(
    "SELECT c.id, c.name, COUNT(t.id) AS tx_count
     FROM categories c
     LEFT JOIN transactions t ON t.category_id = c.id
     WHERE c.user_id = ?
     GROUP BY c.id, c.name
     ORDER BY c.name"
);
$stmt->execute([$user_id]);
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

$messages = [
    'added'   => 'Category added.',
    'renamed' => 'Category renamed.',
    'deleted' => 'Category deleted.',
];
$msg = $messages[$_GET['msg'] ?? ''] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Categories</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include __DIR__ . '/../includes/navbar.php'; ?>
<div class="container" style="max-width: 640px;">
    <h3 class="mb-3">Categories</h3>

    <?php if ($msg): ?>
        <div class="alert alert-success py-2"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>
    <?php foreach ($errors as $error): ?>
        <div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div>
    <?php endforeach; ?>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="POST" class="row g-2 align-items-end">
            <?= csrf_field() ?>
                <input type="hidden" name="action" value="add">
                <div class="col-sm-9">
                    <label class="form-label">New category</label>
                    <input type="text" name="name" maxlength="50" class="form-control"
                           placeholder="e.g. Rent, Savings, Gifts" required>
                </div>
                <div class="col-sm-3">
                    <button class="btn btn-primary w-100">Add</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <table class="table align-middle mb-0">
            <tbody>
            <?php if (empty($categories)): ?>
                <tr><td class="text-center text-muted py-3">No categories yet. Add one above.</td></tr>
            <?php endif; ?>
            <?php foreach ($categories as $c): ?>
                <tr>
                    <td>
                        <form method="POST" class="input-group input-group-sm">
                        <?= csrf_field() ?>
                            <input type="hidden" name="action" value="rename">
                            <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                            <input type="text" name="name" maxlength="50" class="form-control"
                                   value="<?= htmlspecialchars($c['name']) ?>" required>
                            <button class="btn btn-outline-secondary">Rename</button>
                        </form>
                    </td>
                    <td class="text-muted small text-nowrap">
                        <?= (int) $c['tx_count'] ?> transaction<?= $c['tx_count'] == 1 ? '' : 's' ?>
                    </td>
                    <td class="text-end">
                        <form method="POST"
                              onsubmit="return confirm('Delete this category?<?= $c['tx_count'] > 0 ? ' Its transactions will be kept but become uncategorised, and its budgets will be removed.' : '' ?>');">
                              <?= csrf_field() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
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
