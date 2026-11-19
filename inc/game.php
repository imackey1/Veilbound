<?php
declare(strict_types=1);

/* ---------- Session state ---------- */

function game_keys(): array
{
    return ['scene', 'health', 'insight', 'inventory', 'flags', 'visited', 'history', 'steps', 'score', 'seed', 'active'];
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
        'scene' => 'apartment', 'health' => 100, 'insight' => 0,
        'inventory' => [], 'flags' => [], 'visited' => ['apartment'],
        'history' => [], 'steps' => 0, 'score' => 0,
        'seed' => random_int(1, 2147483647), 'active' => true,
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

/** Display name; the revolver shows as empty once its bullet is spent. */
function item_label(string $id, array $state): string
{
    $name = item_name($id);
    if ($id === 'revolver' && !in_array('bullet', $state['flags'], true)) {
        $name .= ' (empty)';
    }
    return $name;
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
        $state['scene'], $state['health'], $state['insight'],
        implode(',', $state['inventory']), implode(',', $state['flags']),
        implode(',', $state['visited']), $state['steps'], implode(',', $state['history']),
        $state['score'], $state['seed'],
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
    foreach (['health', 'insight', 'steps', 'score', 'seed'] as $key) {
        if (!is_int($state[$key])) {
            return null;
        }
    }
    if ($state['health'] < 1 || $state['health'] > 100 || $state['insight'] < 0 || $state['insight'] > 100
        || $state['steps'] < 0 || $state['steps'] > 500 || $state['score'] < 0 || $state['score'] > 5000
        || $state['seed'] < 1 || $state['seed'] > 2147483647) {
        return null;
    }
    foreach ($state['inventory'] as $id) {
        if (!isset($items[$id])) {
            return null;
        }
    }
    $known = game_known_flags();
    foreach ($state['flags'] as $flag) {
        if (!in_array($flag, $known, true)) {
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
        if (!preg_match('/^([a-z_]+):([0-3]):(ok|item|hurt)$/', $entry, $m) || !isset($scenes[$m[1]])) {
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
    $p = explode('|', $line);
    if (count($p) !== 10 || !ctype_digit($p[1]) || !ctype_digit($p[2]) || !ctype_digit($p[6])
        || !ctype_digit($p[8]) || !ctype_digit($p[9])) {
        return null;
    }
    return validate_state([
        'scene' => $p[0], 'health' => (int) $p[1], 'insight' => (int) $p[2],
        'inventory' => split_list($p[3]), 'flags' => split_list($p[4]),
        'visited' => split_list($p[5]), 'steps' => (int) $p[6], 'history' => split_list($p[7]),
        'score' => (int) $p[8], 'seed' => (int) $p[9],
    ]);
}

/* ---------- Conditions and story text ---------- */

/** True when the player has acted on the current page before. */
function scene_seen(array $state): bool
{
    foreach ($state['history'] as $entry) {
        if (strpos($entry, $state['scene'] . ':') === 0) {
            return true;
        }
    }
    return false;
}

function cond_met(?array $cond, array $state): bool
{
    if (!$cond) {
        return true;
    }
    $owns = static function (string $id) use ($state): bool {
        return in_array($id, $state['inventory'], true);
    };
    $flagged = static function (string $flag) use ($state): bool {
        return in_array($flag, $state['flags'], true);
    };
    foreach ($cond['has'] ?? [] as $id) {
        if (!$owns($id)) {
            return false;
        }
    }
    if (!empty($cond['has_any'])) {
        $any = false;
        foreach ($cond['has_any'] as $id) {
            $any = $any || $owns($id);
        }
        if (!$any) {
            return false;
        }
    }
    foreach ($cond['not_has'] ?? [] as $id) {
        if ($owns($id)) {
            return false;
        }
    }
    foreach ($cond['flag'] ?? [] as $flag) {
        if (!$flagged($flag)) {
            return false;
        }
    }
    foreach ($cond['not_flag'] ?? [] as $flag) {
        if ($flagged($flag)) {
            return false;
        }
    }
    foreach ($cond['min'] ?? [] as $stat => $n) {
        if (($state[$stat] ?? 0) < $n) {
            return false;
        }
    }
    foreach ($cond['max'] ?? [] as $stat => $n) {
        if (($state[$stat] ?? 0) > $n) {
            return false;
        }
    }
    if (isset($cond['seen']) && scene_seen($state) !== $cond['seen']) {
        return false;
    }
    return true;
}

/** Paragraphs of the current page: every segment whose condition matches. */
function scene_paragraphs(array $scene, array $state): array
{
    $out = [];
    foreach ($scene['segments'] as $segment) {
        if (cond_met($segment['if'] ?? null, $state)) {
            $out[] = $segment['text'];
        }
    }
    return $out;
}

/* ---------- Choice rules ---------- */

/** Why a choice cannot be used right now, or null when it is available. */
function choice_lock(array $choice, array $state): ?string
{
    if (!empty($choice['once']) && in_array($choice['once'], $state['flags'], true)) {
        return 'Already done';
    }
    if (!cond_met($choice['requires'] ?? null, $state)) {
        return $choice['lock'] ?? 'Not available yet';
    }
    return null;
}

/** First outcome whose condition matches the current state. */
function matching_outcome(array $choice, array $state): array
{
    foreach ($choice['outcomes'] as $outcome) {
        if (cond_met($outcome['if'] ?? null, $state)) {
            return $outcome;
        }
    }
    return end($choice['outcomes']);
}

/** Deterministic "luck": different per run (seed) and per move, never re-rolled by a reload. */
function luck_wins(array $state, int $chance): bool
{
    return (crc32($state['seed'] . '|' . $state['steps']) % 100) < $chance;
}

/** Number of hurtful choices at the end of the history (setback streak). */
function setback_streak(array $state): int
{
    $streak = 0;
    foreach (array_reverse($state['history']) as $entry) {
        if (substr($entry, -5) !== ':hurt') {
            break;
        }
        $streak++;
    }
    return $streak;
}

function names_of(array $ids): string
{
    return implode(', ', array_map('item_name', $ids));
}

/**
 * Consequence preview shown under every choice. Built only from the choice's
 * metadata (its matching outcome) and the player's session state and history.
 * It never reveals which ending a final choice leads to.
 */
function choice_preview(array $choice, array $state): string
{
    $lock = choice_lock($choice, $state);
    if ($lock !== null) {
        return $lock;
    }
    $o = matching_outcome($choice, $state);
    if (isset($o['ending'])) {
        return 'Final choice: no turning back';
    }
    $parts = [];
    if (!empty($o['take'])) {
        $parts[] = 'Cost: ' . names_of($o['take']);
    }
    if (isset($o['luck'])) {
        $parts[] = sprintf('Luck: %d%% to win', $o['luck']['chance']);
        $gain = $o['luck']['win']['give'] ?? [];
    } else {
        $gain = $o['give'] ?? [];
    }
    if ($gain) {
        $parts[] = 'Reward: ' . names_of($gain);
    }
    if (in_array('bullet', $o['unset'] ?? [], true)) {
        $parts[] = 'Spends your only bullet';
    }
    $health = $o['health'] ?? 0;
    if ($health < 0) {
        $parts[] = sprintf('Risky: %d health', $health);
        if ($state['health'] + $health <= 0) {
            $parts[] = 'could kill you';
        }
        if (setback_streak($state) >= 2) {
            $parts[] = 'warning: you are being beaten down';
        }
    }
    if (!empty($o['insight'])) {
        $parts[] = sprintf('+%d perspective', $o['insight']);
    }
    if (isset($o['next']) && $o['next'] !== $state['scene']) {
        $parts[] = in_array($o['next'], $state['visited'], true) ? 'Already explored' : 'Unexplored';
    }
    return $parts ? implode(' | ', $parts) : 'Safe';
}

/** Apply a rule's item/flag changes (shared by outcomes and on_enter rules). */
function apply_changes(array &$state, array $o): void
{
    foreach ($o['take'] ?? [] as $id) {
        $state['inventory'] = array_values(array_diff($state['inventory'], [$id]));
    }
    foreach ($o['give'] ?? [] as $id) {
        if (!in_array($id, $state['inventory'], true)) {
            $state['inventory'][] = $id;
            $state['score'] += game_items()[$id]['points'];
        }
    }
    foreach ($o['set'] ?? [] as $flag) {
        if (!in_array($flag, $state['flags'], true)) {
            $state['flags'][] = $flag;
        }
    }
    foreach ($o['unset'] ?? [] as $flag) {
        $state['flags'] = array_values(array_diff($state['flags'], [$flag]));
    }
}

/**
 * Apply a validated choice. Returns
 * ['outcome' => ok|item|hurt, 'message' => string, 'ending' => ?string].
 */
function apply_choice(array &$state, array $choice, string $sceneId, int $index): array
{
    $o = matching_outcome($choice, $state);
    if (isset($o['luck'])) {
        $side = luck_wins($state, $o['luck']['chance']) ? 'win' : 'lose';
        $o = array_merge($o, $o['luck'][$side]);
        unset($o['luck']);
    }

    apply_changes($state, $o);
    $state['health'] = clamp_stat($state['health'] + ($o['health'] ?? 0));
    $state['insight'] = clamp_stat($state['insight'] + ($o['insight'] ?? 0));
    if (!empty($choice['once']) && !in_array($choice['once'], $state['flags'], true)) {
        $state['flags'][] = $choice['once'];
    }

    $outcome = 'ok';
    if (($o['health'] ?? 0) < 0) {
        $outcome = 'hurt';
    } elseif (!empty($o['give'])) {
        $outcome = 'item';
    }

    $message = $o['text'] ?? '';
    $ending = $o['ending'] ?? null;
    $state['steps']++;
    $state['history'][] = $sceneId . ':' . $index . ':' . $outcome;
    $state['history'] = array_slice($state['history'], -200);

    if ($ending === null && $state['health'] <= 0) {
        $ending = 'bad';
        $message = trim($message . ' You can’t get back up.');
    }
    if ($ending === null) {
        $next = $o['next'] ?? $sceneId;
        $moved = $next !== $sceneId;
        $state['scene'] = $next;
        if (!in_array($next, $state['visited'], true)) {
            $state['visited'][] = $next;
        }
        if ($moved) {
            foreach (game_scenes()[$next]['on_enter'] ?? [] as $rule) {
                if (cond_met($rule['if'] ?? null, $state)) {
                    apply_changes($state, $rule);
                }
            }
        }
    }
    return ['outcome' => $outcome, 'message' => $message, 'ending' => $ending];
}

/* ---------- Endings ---------- */

function final_score(array $state, string $ending): int
{
    return max(0, $state['score'] + game_endings()[$ending]['bonus']);
}

/** Dynamic ending summary built from the session history and flags. */
function ending_summary(array $state, string $ending): array
{
    $has = static function (string $flag) use ($state): bool {
        return in_array($flag, $state['flags'], true);
    };
    $lines = [];
    $count = count($state['inventory']);
    if ($count === 0) {
        $lines[] = 'You carried nothing at the end, only what you had in you.';
    } else {
        $verb = ['bad' => 'You died holding', 'good' => 'You reached the vault carrying', 'assistant' => 'You carried'][$ending] ?? 'You carried';
        $lines[] = $verb . ': ' . implode(', ', array_map(static function (string $id) use ($state): string {
            return game_items()[$id]['emoji'] . ' ' . item_label($id, $state);
        }, $state['inventory'])) . '.';
    }
    if ($ending === 'assistant') {
        $lines[] = 'You took the rich man’s hand and became his assistant, and his friend.';
    } elseif ($has('shot_man')) {
        $lines[] = 'You shot a man in an alley and never looked back.';
    } elseif ($has('with_rich_man')) {
        $lines[] = 'You rode in on a stranger’s kindness, and you never paid it back.';
    } elseif ($has('look_wet')) {
        $lines[] = 'You came to the den soaked and desperate.';
    } else {
        $lines[] = 'You slipped in dry and quiet.';
    }
    $lives = intdiv($state['insight'], 25);
    $lines[] = $lives === 0
        ? 'You never stopped to look at anyone else.'
        : sprintf('You stopped to look at %d part%s of the lives wealth buys.', $lives, $lives === 1 ? '' : 's');
    $hurt = 0;
    foreach ($state['history'] as $entry) {
        $hurt += substr($entry, -5) === ':hurt' ? 1 : 0;
    }
    $lines[] = $hurt === 0
        ? sprintf('It took %d moves, and nobody laid a hand on you.', $state['steps'])
        : sprintf('It took %d moves, and you were beaten down %d time%s along the way.', $state['steps'], $hurt, $hurt === 1 ? '' : 's');
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
