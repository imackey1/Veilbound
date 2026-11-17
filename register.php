<?php
declare(strict_types=1);
require __DIR__ . '/inc/bootstrap.php';

if (is_logged_in()) {
    redirect('menu.php');
}

$username = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = post_str('username');
    $password = (string) ($_POST['password'] ?? '');
    $confirm = (string) ($_POST['confirm'] ?? '');

    if (!csrf_valid()) {
        $errors[] = 'Your form expired. Please try again.';
    }
    if (!valid_username($username)) {
        $errors[] = 'Username must be 3 to 20 letters, numbers, or underscores.';
    } elseif (isset(read_users()[strtolower($username)])) {
        $errors[] = 'That username is already taken.';
    }
    if (strlen($password) < 8 || strlen($password) > 72) {
        $errors[] = 'Password must be 8 to 72 characters.';
    }
    if ($password !== $confirm) {
        $errors[] = 'The two passwords do not match.';
    }
    if (!$errors && !data_is_writable()) {
        $errors[] = 'The server cannot store accounts right now (data folder is not writable).';
    }
    if (!$errors && !register_user($username, $password)) {
        $errors[] = 'Could not save your account. Please try again.';
    }
    if (!$errors) {
        flash('Account created. You can log in now.', 'success');
        redirect('login.php');
    }
}

page_header('Create an account');
foreach ($errors as $error) {
    echo '<p class="flash flash-error" role="alert">' . e($error) . '</p>';
}
?>
<form method="post" action="register.php" class="card" novalidate>
  <?= csrf_field() ?>
  <label for="username">Username</label>
  <input id="username" name="username" type="text" maxlength="20" autocomplete="username" required value="<?= e($username) ?>">
  <label for="password">Password (8+ characters)</label>
  <input id="password" name="password" type="password" maxlength="72" autocomplete="new-password" required>
  <label for="confirm">Confirm password</label>
  <input id="confirm" name="confirm" type="password" maxlength="72" autocomplete="new-password" required>
  <button type="submit">Register</button>
</form>
<p>Already have an account? <a href="login.php">Log in</a></p>
<?php page_footer();
