<?php
declare(strict_types=1);

// Included first by every page: starts the session and loads shared code.
ini_set('session.use_strict_mode', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('ROOT', dirname(__DIR__));
define('DATA_DIR', ROOT . '/data');
define('SAVE_DIR', DATA_DIR . '/saves');

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/scenes.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/game.php';
require_once __DIR__ . '/leaderboard.php';
require_once __DIR__ . '/layout.php';
