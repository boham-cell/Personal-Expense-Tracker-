<?php
// Works from any folder: figure out the project's base URL automatically
$base = '/expense-tracker/';

// Highlight the link for the page the user is on
$current = $_SERVER['REQUEST_URI'];
function nav_active(string $path): string {
    global $current, $base;
    return strpos($current, $base . $path) === 0 ? 'active' : '';
}
?>
<nav class="navbar navbar-expand-md navbar-dark bg-dark mb-4">
    <div class="container">
        <a class="navbar-brand" href="<?= $base ?>dashboard.php">Expense Tracker</a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                data-bs-target="#mainNav" aria-controls="mainNav"
                aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?= nav_active('dashboard.php') ?>" href="<?= $base ?>dashboard.php">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= nav_active('transactions/add.php') ?>" href="<?= $base ?>transactions/add.php">Add</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= nav_active('transactions/list.php') ?>" href="<?= $base ?>transactions/list.php">Transactions</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= nav_active('budgets/') ?>" href="<?= $base ?>budgets/index.php">Budgets</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= nav_active('categories/') ?>" href="<?= $base ?>categories/index.php">Categories</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= nav_active('reports.php') ?>" href="<?= $base ?>reports.php">Reports</a>
                </li>
            </ul>

            <span class="navbar-text me-3">
                <?= htmlspecialchars($_SESSION['full_name'] ?? '') ?>
            </span>
            <a class="btn btn-outline-light btn-sm" href="<?= $base ?>auth/logout.php">Log out</a>
        </div>
    </div>
</nav>
