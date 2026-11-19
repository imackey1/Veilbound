<?php
declare(strict_types=1);
require __DIR__ . '/inc/bootstrap.php';

if (is_logged_in()) {
    redirect('menu.php');
}

$remembered = (string) ($_COOKIE['veil_user'] ?? '');
$username = valid_username($remembered) ? $remembered : '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = post_str('username');
    $password = (string) ($_POST['password'] ?? '');
    $remember = isset($_POST['remember']);

    if (!csrf_valid()) {
        $error = 'Your form expired. Please try again.';
    } elseif ($username === '' || $password === '') {
        $error = 'Enter your username and password.';
    } else {
        $name = verify_login($username, $password);
        if ($name === null) {
            $error = 'Incorrect username or password.';
        } else {
            log_in($name);
            // Cookie only remembers the username for convenience; never credentials or scores.
            if ($remember) {
                setcookie('veil_user', $name, ['expires' => time() + 30 * 86400, 'httponly' => true, 'samesite' => 'Lax']);
            } else {
                setcookie('veil_user', '', ['expires' => time() - 3600]);
            }
            // Restore the saved game (validated) so earlier items are kept.
            clear_game_state();
            $saved = load_saved_game($name);
            if ($saved !== null) {
                set_game_state($saved);
                flash('Welcome back. Your saved progress and items were restored.', 'success');
            } else {
                if (has_save($name)) {
                    delete_save($name);
                    flash('Your save file was invalid and was discarded.', 'error');
                }
                flash('Welcome back.', 'success');
            }
            redirect('menu.php');
        }
    }
}

page_header('Log in');
if ($error !== '') {
    echo '<p class="flash flash-error" role="alert">' . e($error) . '</p>';
}
?>
<form method="post" action="login.php" class="card" novalidate>
  <?= csrf_field() ?>
  <label for="username">Username</label>
  <input id="username" name="username" type="text" maxlength="20" autocomplete="username" required value="<?= e($username) ?>">
  <label for="password">Password</label>
  <input id="password" name="password" type="password" maxlength="72" autocomplete="current-password" required>
  <label class="inline"><input type="checkbox" name="remember" value="1"<?= $username !== '' && $remembered === $username ? ' checked' : '' ?>> Remember my username</label>
  <button type="submit">Enter the Veil</button>
</form>
<p>New here? <a href="register.php">Create an account</a></p>
<?php page_footer();
