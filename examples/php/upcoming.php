<?php
// What waits for a person's approval and what is scheduled, in the order it goes out.
// Usage: php upcoming.php
require __DIR__ . '/keepemposted.php';

const WORDS = ['pending_approval' => 'needs approval', 'scheduled' => 'scheduled'];

/** Every delivery in this status, one page of posts at a time. */
function deliveries(string $status): array
{
    $rows = [];
    $cursor = null;
    do {
        $page = kep_get('/posts', ['status' => $status, 'limit' => 50, 'cursor' => $cursor]);
        foreach ($page['posts'] as $found) {
            foreach ($found['deliveries'] as $delivery) {
                if ($delivery['status'] === $status) {
                    $rows[] = [$delivery, $found];
                }
            }
        }
        $cursor = $page['next_cursor'];
    } while ($cursor);
    return $rows;
}

kep_run(function () {
    $rows = array_merge(deliveries('pending_approval'), deliveries('scheduled'));
    if (!$rows) {
        echo "Nothing waits for approval and nothing is scheduled.\n";
        return;
    }
    // What goes out the moment it is approved has no time and comes first.
    $at = fn ($row) => $row[0]['scheduled_at'] ? strtotime($row[0]['scheduled_at']) : PHP_INT_MIN;
    usort($rows, fn ($a, $b) => $at($a) <=> $at($b));
    foreach ($rows as [$delivery, $found]) {
        $when = $delivery['scheduled_at_text'] ?? 'As soon as it is approved';
        $state = $delivery['on_its_way'] ? 'going out' : WORDS[$delivery['status']];
        $text = trim(preg_replace('/\s+/', ' ', $delivery['text'] ?? $found['text'] ?? $found['title'] ?? ''));
        printf("%-42s %-15s %-22s %s\n", $when, $state, $delivery['account']['handle'], rtrim(mb_substr($text, 0, 60)));
    }
});
