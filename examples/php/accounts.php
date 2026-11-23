<?php
// The connected accounts: the id to post to, and the kinds of post each one takes
// with what each costs in credits.
// Usage: php accounts.php
require __DIR__ . '/keepemposted.php';

kep_run(function () {
    $accounts = kep_get('/accounts')['accounts'];
    if (!$accounts) {
        echo "No accounts yet. Connect one in the app: https://app.keepemposted.com\n";
        return;
    }
    $pad = str_repeat(' ', 11);
    foreach ($accounts as $account) {
        printf("%-10s %-24s %s\n", $account['platform_name'], $account['handle'], $account['id']);
        if (!$account['can_publish']) {
            echo "{$pad}Connect it again in the app before posting to it ({$account['status']}).\n";
        }
        $kinds = [];
        foreach ($account['formats'] as $kind) {
            if (empty($kind['blocked'])) {
                $kinds[] = "{$kind['key']} ({$kind['credits']} credits)";
            }
        }
        echo "{$pad}Kinds of post: " . implode(', ', $kinds) . "\n";
        if ($account['brand']) {
            echo "{$pad}Brand: {$account['brand']['name']}\n";
        }
        if ($account['next_free_at_text']) {
            echo "{$pad}Next free posting time: {$account['next_free_at_text']}\n";
        }
        echo "\n";
    }
});
