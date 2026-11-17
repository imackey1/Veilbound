<?php
declare(strict_types=1);
require __DIR__ . '/inc/bootstrap.php';
require_login();

$rows = ranked_leaderboard(10);
$endings = game_endings();

page_header('Leaderboard');
if (!$rows): ?>
  <p>No scores yet. Be the first!</p>
<?php else: ?>
  <div class="table-wrap">
  <table>
    <caption>Top 10 sorcerers</caption>
    <thead><tr><th scope="col">Rank</th><th scope="col">Sorcerer</th><th scope="col">Score</th><th scope="col">Ending</th><th scope="col">Moves</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $i => $row): ?>
      <tr<?= $row['name'] === $_SESSION['user'] ? ' class="me"' : '' ?>>
        <td><?= $i + 1 ?></td>
        <td><?= e($row['name']) ?></td>
        <td><?= (int) $row['score'] ?></td>
        <td><?= e($endings[$row['ending']]['title']) ?></td>
        <td><?= (int) $row['steps'] ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
<?php endif; ?>
<p><a class="button" href="menu.php">Back to menu</a></p>
<?php page_footer();
