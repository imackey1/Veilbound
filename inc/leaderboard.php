<?php
declare(strict_types=1);

/**
 * The session ($_SESSION['leaderboard']) holds this player's runs and is the
 * authoritative record. Each run is also appended to data/scores.txt so the
 * leaderboard page can show other players' scores too.
 */

function scores_file(): string
{
    return DATA_DIR . '/scores.txt';
}

function add_leaderboard_entry(string $user, int $score, string $ending, int $steps): void
{
    $entry = ['name' => $user, 'score' => $score, 'ending' => $ending, 'steps' => $steps, 'time' => time()];
    $_SESSION['leaderboard'][] = $entry;
    $line = implode('|', $entry) . "\n";
    @file_put_contents(scores_file(), $line, FILE_APPEND | LOCK_EX);
}

function read_shared_scores(): array
{
    $file = scores_file();
    if (!is_file($file)) {
        return [];
    }
    $rows = [];
    foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $p = explode('|', $line);
        if (count($p) === 5 && valid_username($p[0]) && ctype_digit($p[1]) && isset(game_endings()[$p[2]])
            && ctype_digit($p[3]) && ctype_digit($p[4])) {
            $rows[] = ['name' => $p[0], 'score' => (int) $p[1], 'ending' => $p[2], 'steps' => (int) $p[3], 'time' => (int) $p[4]];
        }
    }
    return $rows;
}

/** Session runs merged with the shared file, de-duplicated, ranked, top $limit. */
function ranked_leaderboard(int $limit = 10): array
{
    $all = [];
    foreach (array_merge(read_shared_scores(), $_SESSION['leaderboard'] ?? []) as $row) {
        $all[implode('|', $row)] = $row;
    }
    $rows = array_values($all);
    usort($rows, static function (array $a, array $b): int {
        return [$b['score'], $a['steps'], $a['time']] <=> [$a['score'], $b['steps'], $b['time']];
    });
    return array_slice($rows, 0, $limit);
}
