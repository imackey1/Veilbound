<?php
declare(strict_types=1);
require __DIR__ . '/inc/bootstrap.php';
require_login();

$active = !empty($_SESSION['active']);
$scenes = game_scenes();

page_header('Main Menu');
?>
<p>Welcome, <?= e((string) $_SESSION['user']) ?>.</p>
<?php if ($active && isset($scenes[$_SESSION['scene']])): ?>
  <p>Saved progress: <strong><?= e($scenes[$_SESSION['scene']]['title']) ?></strong> with <?= count($_SESSION['inventory']) ?> item(s).</p>
<?php endif; ?>
<div class="actions">
  <?php if ($active): ?>
    <a class="button" href="scene.php">Continue</a>
  <?php endif; ?>
  <form method="post" action="actions.php">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="new_game">
    <button type="submit" class="<?= $active ? 'secondary' : '' ?>">New Game</button>
  </form>
  <a class="button secondary" href="inventory.php">Inventory</a>
  <a class="button secondary" href="leaderboard.php">Leaderboard</a>
</div>
<?php if ($active): ?>
  <p class="hint">Starting a new game replaces your saved progress.</p>
<?php endif; ?>
<?php page_footer();
