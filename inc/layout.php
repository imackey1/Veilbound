<?php
declare(strict_types=1);

function page_header(string $title): void
{
    $messages = pull_flash();
    ?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title) ?> | Veilbound</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
<a class="skip" href="#main">Skip to content</a>
<header class="site-header">
  <a class="brand" href="<?= is_logged_in() ? 'menu.php' : 'index.php' ?>">VEILBOUND</a>
  <?php if (is_logged_in()): ?>
  <nav aria-label="Main">
    <a href="menu.php">Menu</a>
    <a href="inventory.php">Inventory</a>
    <a href="leaderboard.php">Leaderboard</a>
    <a href="logout.php">Logout (<?= e((string) $_SESSION['user']) ?>)</a>
  </nav>
  <?php endif; ?>
</header>
<main id="main">
<?php foreach ($messages as $m): ?>
  <p class="flash flash-<?= e($m['type']) ?>" role="<?= $m['type'] === 'error' ? 'alert' : 'status' ?>"><?= e($m['msg']) ?></p>
<?php endforeach; ?>
<h1><?= e($title) ?></h1>
<?php
}

function page_footer(): void
{
    ?>
</main>
<footer class="site-footer">Veilbound &middot; CSC 4370 Project 2 &middot; Ibrahim Mackey</footer>
</body>
</html>
<?php
}

function stat_bar(string $label, int $value): void
{
    ?><label class="stat"><?= e($label) ?> <progress max="100" value="<?= $value ?>"><?= $value ?></progress> <span><?= $value ?></span></label>
<?php
}
