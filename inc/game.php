<?php
declare(strict_types=1);

/* ---------- Session state ---------- */

function game_keys(): array
{
    return ['scene', 'aether', 'resolve', 'inventory', 'flags', 'visited', 'history', 'steps', 'active'];
}

function game_state(): array
{
    $state = [];
    foreach (game_keys() as $key) {
        $state[$key] = $_SESSION[$key] ?? null;
    }
    return $state;
}

function set_game_state(array $state): void
{
    foreach (game_keys() as $key) {
        $_SESSION[$key] = $state[$key];
    }
}

function clear_game_state(): void
{
    foreach (game_keys() as $key) {
        unset($_SESSION[$key]);
    }
    $_SESSION['active'] = false;
}

function new_game_state(): array
{
    return [
        'scene' => 'rooftop', 'aether' => 40, 'resolve' => 100,
        'inventory' => [], 'flags' => [], 'visited' => ['rooftop'],
        'history' => [], 'steps' => 0, 'active' => true,
    ];
}

function clamp_stat(int $value): int
{
    return max(0, min(100, $value));
}

function item_name(string $id): string
{
    return game_items()[$id]['name'] ?? $id;
}

/* ---------- Save file (backup of the session, validated on load) ---------- */

function save_path(string $user): string
{
    return SAVE_DIR . '/' . strtolower($user) . '.txt';
}

function has_save(string $user): bool
{
    return valid_username($user) && is_file(save_path($user));
}

function save_game(string $user, array $state): void
{
    if (!valid_username($user)) {
        return;
    }
    $line = implode('|', [
        $state['scene'], $state['aether'], $state['resolve'],
        implode(',', $state['inventory']), implode(',', $state['flags']),
        implode(',', $state['visited']), $state['steps'], implode(',', $state['history']),
    ]);
    @file_put_contents(save_path($user), $line . "\n", LOCK_EX);
}

function delete_save(string $user): void
{
    if (valid_username($user) && is_file(save_path($user))) {
        @unlink(save_path($user));
    }
}

function split_list(string $text): array
{
    return $text === '' ? [] : explode(',', $text);
}

/**
 * Validate a state array. Returns the cleaned state, or null if anything is
 * out of range or unknown. Used for every value restored from disk.
 */
function validate_state(array $state): ?array
{
    $scenes = game_scenes();
    $items = game_items();
    if (!is_string($state['scene']) || !isset($scenes[$state['scene']])) {
        return null;
    }
    foreach (['aether', 'resolve', 'steps'] as $key) {
        if (!is_int($state[$key])) {
            return null;
        }
    }
    if ($state['aether'] < 0 || $state['aether'] > 100 || $state['resolve'] < 1 || $state['resolve'] > 100
        || $state['steps'] < 0 || $state['steps'] > 500) {
        return null;
    }
    foreach ($state['inventory'] as $id) {
        if (!isset($items[$id])) {
            return null;
        }
    }
    foreach ($state['flags'] as $flag) {
        if (!in_array($flag, game_known_flags(), true)) {
            return null;
        }
    }
    foreach ($state['visited'] as $id) {
        if (!isset($scenes[$id])) {
            return null;
        }
    }
    if (!in_array($state['scene'], $state['visited'], true) || count($state['history']) > 200) {
        return null;
    }
    foreach ($state['history'] as $entry) {
        if (!preg_match('/^([a-z]+):([0-3]):(ok|fail|item)$/', $entry, $m) || !isset($scenes[$m[1]])) {
            return null;
        }
    }
    $state['inventory'] = array_values(array_unique($state['inventory']));
    $state['flags'] = array_values(array_unique($state['flags']));
    $state['visited'] = array_values(array_unique($state['visited']));
    $state['active'] = true;
    return $state;
}

function load_saved_game(string $user): ?array
{
    if (!has_save($user)) {
        return null;
    }
    $line = trim((string) @file_get_contents(save_path($user)));
    $parts = explode('|', $line);
    if (count($parts) !== 8 || !ctype_digit($parts[1]) || !ctype_digit($parts[2]) || !ctype_digit($parts[6])) {
        return null;
    }
    return validate_state([
        'scene' => $parts[0], 'aether' => (int) $parts[1], 'resolve' => (int) $parts[2],
        'inventory' => split_list($parts[3]), 'flags' => split_list($parts[4]),
        'visited' => split_list($parts[5]), 'steps' => (int) $parts[6], 'history' => split_list($parts[7]),
    ]);
}

/* ---------- Choice rules ---------- */

function ends_with_fail(string $entry): bool
{
    return substr($entry, -5) === ':fail';
}

/** Why a choice cannot be used right now, or null when it is available. */
function choice_lock(array $choice, array $state): ?string
{
    if (!empty($choice['once']) && in_array($choice['once'], $state['flags'], true)) {
        return 'Already done';
    }
    if (!empty($choice['give']) && in_array($choice['give'], $state['inventory'], true)) {
        return 'You already have the ' . item_name($choice['give']);
    }
    $missing = [];
    foreach ($choice['requires'] ?? [] as $id) {
        if (!in_array($id, $state['inventory'], true)) {
            $missing[] = item_name($id);
        }
    }
    return $missing ? 'Requires ' . implode(' and ', $missing) : null;
}

/** Aether plus the power bonus of every owned item. */
function player_power(array $state): int
{
    $power = $state['aether'];
    foreach (game_items() as $id => $item) {
        if (in_array($id, $state['inventory'], true)) {
            $power += $item['power'];
        }
    }
    return $power;
}

function odds_label(int $margin): string
{
    if ($margin >= 15) {
        return 'Safe';
    }
    return $margin >= 0 ? 'Even odds' : 'Risky';
}

/** Number of failed fights at the end of the history (setback streak). */
function setback_streak(array $state): int
{
    $streak = 0;
    foreach (array_reverse($state['history']) as $entry) {
        if (!ends_with_fail($entry)) {
            break;
        }
        $streak++;
    }
    return $streak;
}

/**
 * Consequence preview shown under every choice. Built only from the choice's
 * metadata and the player's session state and history (no hidden information).
 */
function choice_preview(array $choice, array $state): string
{
    $lock = choice_lock($choice, $state);
    if ($lock !== null) {
        return $lock;
    }
    $parts = [];
    if (!empty($choice['give'])) {
        $parts[] = 'Gain: ' . item_name($choice['give']);
    }
    if (!empty($choice['aether'])) {
        $parts[] = sprintf('%+d aether', $choice['aether']);
    }
    if (!empty($choice['resolve'])) {
        $parts[] = sprintf('%+d resolve', $choice['resolve']);
    }
    if (isset($choice['check'])) {
        $power = player_power($state);
        $need = $choice['check']['power'];
        $parts[] = sprintf('Odds: %s (power %d vs %d)', odds_label($power - $need), $power, $need);
        $parts[] = sprintf('failure costs %d resolve', abs($choice['check']['fail_resolve']));
        if (setback_streak($state) >= 2) {
            $parts[] = 'warning: two setbacks in a row';
        }
    }
    if (isset($choice['ending']) && !isset($choice['check'])) {
        $parts[] = 'Final choice';
    }
    if (isset($choice['next']) && !isset($choice['ending'])) {
        $parts[] = in_array($choice['next'], $state['visited'], true) ? 'Already explored' : 'Unexplored';
    }
    return $parts ? implode(' | ', $parts) : 'Safe';
}

/**
 * Apply a validated choice to the state. Returns
 * ['outcome' => ok|fail|item, 'message' => string, 'ending' => ?string].
 */
function apply_choice(array &$state, array $choice, string $sceneId, int $index): array
{
    $outcome = 'ok';
    $message = $choice['note'] ?? '';
    $next = $choice['next'] ?? $sceneId;
    $ending = $choice['ending'] ?? null;

    $failed = false;
    if (isset($choice['check']) && player_power($state) < $choice['check']['power']) {
        $failed = true;
        $outcome = 'fail';
        $state['resolve'] = clamp_stat($state['resolve'] + $choice['check']['fail_resolve']);
        $message = $choice['check']['fail_note'];
        $next = $choice['check']['fail_next'] ?? $sceneId;
        $ending = $choice['check']['fail_ending'] ?? null;
    }

    if (!$failed) {
        $state['aether'] = clamp_stat($state['aether'] + ($choice['aether'] ?? 0));
        $state['resolve'] = clamp_stat($state['resolve'] + ($choice['resolve'] ?? 0));
        if (!empty($choice['give'])) {
            $state['inventory'][] = $choice['give'];
            $outcome = 'item';
        }
        if (!empty($choice['once'])) {
            $state['flags'][] = $choice['once'];
        }
    }

    $state['steps']++;
    $state['history'][] = $sceneId . ':' . $index . ':' . $outcome;
    $state['history'] = array_slice($state['history'], -200);

    if ($ending === null && $state['resolve'] <= 0) {
        $ending = 'C';
        $message = 'Your resolve is gone. The Hollows close in.';
    }
    if ($ending === null) {
        $state['scene'] = $next;
        if (!in_array($next, $state['visited'], true)) {
            $state['visited'][] = $next;
        }
    }
    return ['outcome' => $outcome, 'message' => $message, 'ending' => $ending];
}

/* ---------- Endings ---------- */

function final_score(array $state, string $ending): int
{
    $base = game_endings()[$ending]['base'];
    $score = $base + $state['aether'] * 2 + $state['resolve'] * 2 + count($state['inventory']) * 25
        - max(0, $state['steps'] - 15) * 5;
    return max(0, $score);
}

/** Dynamic ending summary built from the session history. */
function ending_summary(array $state, string $ending): array
{
    $items = count($state['inventory']);
    $total = count(game_items());
    $fails = 0;
    $fights = 0;
    foreach ($state['history'] as $entry) {
        [$sceneId, $idx] = explode(':', $entry);
        $choice = game_scenes()[$sceneId]['choices'][(int) $idx] ?? [];
        if (isset($choice['check'])) {
            $fights++;
        }
        if (ends_with_fail($entry)) {
            $fails++;
        }
    }
    $lines = [];
    if ($items === 0) {
        $lines[] = 'You walked in with nothing but nerve.';
    } elseif ($items >= 5) {
        $lines[] = sprintf('You gathered nearly everything the Order left behind (%d of %d items).', $items, $total);
    } else {
        $lines[] = sprintf('You carried %d of %d possible items into the arcade.', $items, $total);
    }
    if ($fights === 0) {
        $lines[] = 'You avoided every fight you could.';
    } elseif ($fails === 0) {
        $lines[] = sprintf('You won all %d of your fights.', $fights);
    } else {
        $lines[] = sprintf('You lost %d of %d fights, and the setbacks left their mark.', $fails, $fights);
    }
    $lines[] = $state['steps'] <= 15
        ? sprintf('You finished in just %d moves, a fast and decisive route.', $state['steps'])
        : sprintf('It took you %d moves across %d locations.', $state['steps'], count($state['visited']));
    if ($ending === 'A' && $items < $total) {
        $lines[] = 'Some secrets stayed hidden, so a second run may reveal more.';
    }
    return $lines;
}

/** Finish the current run: score, leaderboard, summary, and clear the save. */
function finish_game(array &$state, string $ending): void
{
    $score = final_score($state, $ending);
    $user = (string) $_SESSION['user'];
    $_SESSION['last_result'] = [
        'ending' => $ending, 'score' => $score, 'steps' => $state['steps'],
        'items' => count($state['inventory']), 'summary' => ending_summary($state, $ending),
    ];
    add_leaderboard_entry($user, $score, $ending, $state['steps']);
    $state['active'] = false;
    delete_save($user);
}
