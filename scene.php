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
  <?php stat_bar('Perspective', $state['insight']); ?>
  <p>Score: <strong><?= $state['score'] ?></strong> &middot; Moves: <strong><?= $state['steps'] ?></strong></p>
</div>
<img class="scene-img" src="images/<?= e($scene['image']) ?>" width="800" height="450" alt="<?= e($scene['alt']) ?>">
<div class="story">
<?php foreach (scene_paragraphs($scene, $state) as $paragraph): ?>
  <p><?= e($paragraph) ?></p>
<?php endforeach; ?>
</div>
<?php if ($state['health'] < 30): ?>
  <p class="flash flash-error" role="alert">You’re barely standing. One more beating could finish you.</p>
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
        <?php if (!empty($choice['detail'])): ?><span class="choice-detail"><?= e($choice['detail']) ?></span><?php endif; ?>
        <span class="preview"><?= e(choice_preview($choice, $state)) ?></span>
      </button>
    </form>
  </li>
<?php endforeach; ?>
</ul>
<p><a href="inventory.php">View inventory</a></p>
<?php
items_panel($state);
page_footer();
