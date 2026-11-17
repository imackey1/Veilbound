<?php
declare(strict_types=1);
require __DIR__ . '/inc/bootstrap.php';
require_login();

$result = $_SESSION['last_result'] ?? null;
$endings = game_endings();
if (!is_array($result) || !isset($endings[$result['ending']])) {
    flash('No finished game to show yet.', 'error');
    redirect('menu.php');
}
$ending = $endings[$result['ending']];

page_header($ending['title']);
?>
<img class="scene-img" src="images/<?= e($ending['image']) ?>" width="800" height="450" alt="<?= e($ending['alt']) ?>">
<p class="story"><?= e($ending['text']) ?></p>
<h2>Your path</h2>
<ul>
<?php foreach ($result['summary'] as $line): ?>
  <li><?= e($line) ?></li>
<?php endforeach; ?>
</ul>
<p class="score">Final score: <strong><?= (int) $result['score'] ?></strong></p>
<div class="actions">
  <a class="button" href="leaderboard.php">View leaderboard</a>
  <form method="post" action="actions.php">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="new_game">
    <button type="submit" class="secondary">Play again</button>
  </form>
</div>
<?php page_footer();
