<?php
// How the posts that went out in the last 7, 30 or 90 days are doing, per account
// and per post, as each platform reports it.
// Usage: php results.php [7|30|90]
require __DIR__ . '/keepemposted.php';

$days = (int) ($argv[1] ?? 30);

function plural(int $count, string $word): string
{
    return "$count $word" . ($count === 1 ? '' : 's');
}

function numbers(array $values, array $names): string
{
    $parts = [];
    foreach ($values as $key => $value) {
        $parts[] = ($names[$key] ?? $key) . ' ' . number_format($value);
    }
    return implode(', ', $parts);
}

kep_run(function () use ($days) {
    $data = kep_get('/results', ['days' => $days, 'limit' => 10]);
    echo "Last $days days: " . plural($data['published'], 'post') . " went out\n\n";
    foreach ($data['accounts'] as $account) {
        if (!$account['published']) {
            continue;
        }
        $line = sprintf('%-10s %-24s %s  ', $account['platform_name'], $account['handle'], plural($account['published'], 'post'));
        $line .= $account['readable'] ? (numbers($account['totals'], $account['names']) ?: 'no numbers yet') : $account['note'];
        $followers = $account['followers'] ?? [];
        if (($followers['latest'] ?? null) !== null) {
            $change = $followers['change'] ? sprintf(' (%s%s)', $followers['change'] > 0 ? '+' : '', number_format($followers['change'])) : '';
            $line .= "  {$followers['name']} " . number_format($followers['latest']) . $change;
        }
        echo "$line\n";
    }

    echo "\n";
    foreach ($data['posts'] as $found) {
        $text = trim(preg_replace('/\s+/', ' ', $found['text'] ?? $found['title'] ?? ''));
        echo "{$found['published_at_text']}  {$found['platform']}  " . rtrim(mb_substr($text, 0, 60)) . "\n";
        $results = $found['results'];
        echo '  ' . (numbers($results['metrics'], $results['labels']) ?: ($results['note'] ?: 'no numbers yet')) . "\n";
        if ($found['external_url']) {
            echo "  {$found['external_url']}\n";
        }
    }
    // A number a platform does not give is left out, never zero, and numbers from
    // different platforms are never added together: a view on YouTube is not a like on Bluesky.
});
