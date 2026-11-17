<?php
declare(strict_types=1);
require __DIR__ . '/inc/bootstrap.php';
require_login();

if (empty($_SESSION['active'])) {
    flash('Start or continue a game first.', 'error');
    redirect('menu.php');
}

$state = game_state();
$scenes = game_scenes();
// The scene always comes from the session, never from the URL.
if (!isset($scenes[$state['scene']])) {
    clear_game_state();
    flash('Your game data was invalid. Please start a new game.', 'error');
    redirect('menu.php');
}
$scene = $scenes[$state['scene']];

page_header($scene['title']);
?>
<div class="stats" aria-label="Your stats">
  <?php stat_bar('Aether', $state['aether']); ?>
  <?php stat_bar('Resolve', $state['resolve']); ?>
  <p>Items: <strong><?= count($state['inventory']) ?></strong> / <?= count(game_items()) ?> &middot; Moves: <strong><?= $state['steps'] ?></strong></p>
</div>
<img class="scene-img" src="images/<?= e($scene['image']) ?>" width="800" height="450" alt="<?= e($scene['alt']) ?>">
<p class="story"><?= e($scene['text']) ?></p>
<?php if ($state['resolve'] < 30): ?>
  <p class="flash flash-error" role="alert">Your resolve is dangerously low. One more setback could end your run.</p>
<?php endif; ?>

<ul class="choices">
<?php foreach ($scene['choices'] as $i => $choice):
    $lock = choice_lock($choice, $state); ?>
  <li>
    <form method="post" action="actions.php">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="choose">
      <input type="hidden" name="scene" value="<?= e($state['scene']) ?>">
      <input type="hidden" name="choice" value="<?= $i ?>">
      <button type="submit" class="choice<?= $lock !== null ? ' locked' : '' ?>"<?= $lock !== null ? ' disabled aria-disabled="true"' : '' ?>>
        <span class="choice-label"><?= ($i + 1) . '. ' . e($choice['label']) ?></span>
        <span class="preview"><?= e(choice_preview($choice, $state)) ?></span>
      </button>
    </form>
  </li>
<?php endforeach; ?>
</ul>
<p><a href="inventory.php">View inventory</a></p>
<?php page_footer();
