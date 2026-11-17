<?php
declare(strict_types=1);
require __DIR__ . '/inc/bootstrap.php';
require_login();

// Controller (no HTML): validates every POST, updates state, saves, then redirects.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('menu.php');
}
if (!csrf_valid()) {
    flash('Your form expired. Please try again.', 'error');
    redirect('menu.php');
}

$user = (string) $_SESSION['user'];
$action = post_str('action');

if ($action === 'new_game') {
    $state = new_game_state();
    set_game_state($state);
    save_game($user, $state);
    flash('A new case begins.', 'success');
    redirect('scene.php');
}

if ($action === 'choose') {
    if (empty($_SESSION['active'])) {
        flash('Start or continue a game first.', 'error');
        redirect('menu.php');
    }
    $state = game_state();
    $scenes = game_scenes();

    // The submitted scene must be the player's current scene (stale pages are rejected).
    if (post_str('scene') !== $state['scene'] || !isset($scenes[$state['scene']])) {
        flash('That page was out of date. Here is where you really are.', 'error');
        redirect('scene.php');
    }
    $index = filter_var(post_str('choice'), FILTER_VALIDATE_INT);
    $choices = $scenes[$state['scene']]['choices'];
    if ($index === false || !isset($choices[$index])) {
        flash('That is not a valid choice.', 'error');
        redirect('scene.php');
    }
    $choice = $choices[$index];

    // Re-check requirements on the server so forged POSTs cannot skip item gates.
    $lock = choice_lock($choice, $state);
    if ($lock !== null) {
        flash('You cannot do that: ' . $lock . '.', 'error');
        redirect('scene.php');
    }

    $result = apply_choice($state, $choice, $state['scene'], $index);

    if ($result['ending'] !== null) {
        finish_game($state, $result['ending']);
        set_game_state($state);
        if ($result['message'] !== '') {
            flash($result['message'], 'info');
        }
        redirect('ending.php');
    }

    set_game_state($state);
    save_game($user, $state);
    if ($result['message'] !== '') {
        flash($result['message'], $result['outcome'] === 'fail' ? 'error' : 'success');
    }
    redirect('scene.php');
}

flash('Unknown action.', 'error');
redirect('menu.php');
