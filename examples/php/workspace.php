<?php
// The workspace the key belongs to: its time zone, what happens to what the key
// schedules, and what it has to spend.
// Usage: KEEPEMPOSTED_API_KEY=kep_live_... php workspace.php
require __DIR__ . '/keepemposted.php';

kep_run(function () {
    $data = kep_get('/workspace');
    $workspace = $data['workspace'];
    $credits = $data['credits'];
    echo "{$workspace['name']} ({$workspace['slug']}), time zone {$workspace['timezone']}\n";
    echo "Now: {$data['now_text']}\n\n";
    echo "Approval: {$data['approval']['mode']}\n";
    echo "  {$data['approval']['rule']}\n\n";
    echo "Credits: {$credits['balance']} ({$credits['paid_balance']} paid), {$credits['committed']} spoken for by what is scheduled\n";
    $counts = [];
    foreach ($data['deliveries'] as $status => $count) {
        if ($count) {
            $counts[] = "$count $status";
        }
    }
    echo 'Posts per account: ' . ($counts ? implode(', ', $counts) : 'none yet') . "\n";
    echo "This key: {$data['key']['name']} (" . implode(', ', $data['key']['scopes']) . ")\n";
});
