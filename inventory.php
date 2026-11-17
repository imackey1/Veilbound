<?php
declare(strict_types=1);
require __DIR__ . '/inc/bootstrap.php';
require_login();

page_header('Inventory');
if (empty($_SESSION['active'])) {
    echo '<p>You have no active game. <a href="menu.php">Go to the menu</a>.</p>';
    page_footer();
    exit;
}
$state = game_state();
?>
<div class="stats">
  <?php stat_bar('Aether', $state['aether']); ?>
  <?php stat_bar('Resolve', $state['resolve']); ?>
  <p>Fight power: <strong><?= player_power($state) ?></strong> (aether plus item bonuses)</p>
</div>
<?php if (!$state['inventory']): ?>
  <p>You carry nothing yet.</p>
<?php endif; ?>
<ul class="items">
<?php foreach (game_items() as $id => $item):
    $owned = in_array($id, $state['inventory'], true); ?>
  <li class="<?= $owned ? 'owned' : 'missing' ?>">
    <?php if ($owned): ?>
      <strong><?= e($item['name']) ?></strong> <span><?= e($item['desc']) ?></span>
    <?php else: ?>
      <strong>Unknown item</strong> <span>Not found yet.</span>
    <?php endif; ?>
  </li>
<?php endforeach; ?>
</ul>
<p><a class="button" href="scene.php">Back to the story</a></p>
<?php page_footer();
