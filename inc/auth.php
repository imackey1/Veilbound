<?php
declare(strict_types=1);

function users_file(): string
{
    return DATA_DIR . '/users.txt';
}

function valid_username(string $name): bool
{
    return (bool) preg_match('/^[A-Za-z0-9_]{3,20}$/', $name);
}

/** @return array<string, array{name:string, hash:string}> keyed by lowercase name */
function read_users(): array
{
    $file = users_file();
    if (!is_file($file)) {
        return [];
    }
    $users = [];
    foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $parts = explode('|', $line, 2);
        if (count($parts) === 2) {
            $users[strtolower($parts[0])] = ['name' => $parts[0], 'hash' => $parts[1]];
        }
    }
    return $users;
}

function data_is_writable(): bool
{
    return is_dir(DATA_DIR) && is_writable(DATA_DIR) && is_dir(SAVE_DIR) && is_writable(SAVE_DIR);
}

function register_user(string $name, string $password): bool
{
    $line = $name . '|' . password_hash($password, PASSWORD_DEFAULT) . "\n";
    return file_put_contents(users_file(), $line, FILE_APPEND | LOCK_EX) !== false;
}

/** Returns the stored username on success, null on failure. */
function verify_login(string $name, string $password): ?string
{
    $user = read_users()[strtolower($name)] ?? null;
    if ($user === null) {
        return null;
    }
    return password_verify($password, $user['hash']) ? $user['name'] : null;
}

function log_in(string $name): void
{
    session_regenerate_id(true);
    $_SESSION['logged_in'] = true;
    $_SESSION['user'] = $name;
    unset($_SESSION['csrf']);
}

function is_logged_in(): bool
{
    return !empty($_SESSION['logged_in']) && !empty($_SESSION['user']);
}

/** Session guard: every protected page calls this first. */
function require_login(): void
{
    if (!is_logged_in()) {
        flash('Please log in to continue.', 'error');
        redirect('login.php');
    }
}

function log_out(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', ['expires' => time() - 3600, 'path' => $p['path'], 'httponly' => true, 'samesite' => 'Lax']);
    }
    session_destroy();
}
