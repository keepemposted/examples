<?php
// Write one post for one or more accounts: as drafts, for each account's next free
// posting time, or for a time you give.
//
// Usage: php create-post.php WHEN "TEXT" ACCOUNT_ID [ACCOUNT_ID ...]
//
// WHEN is draft (nothing is scheduled), next_free (each account's next free posting
// time, as set in the app) or a time such as 2026-11-27T09:00, read in the
// workspace's time zone. accounts.php lists the account ids.
require __DIR__ . '/keepemposted.php';

[$when, $text] = [$argv[1] ?? null, $argv[2] ?? null];
$accountIds = array_slice($argv, 3);
if (!$accountIds) {
    fwrite(STDERR, "Usage: php create-post.php WHEN \"TEXT\" ACCOUNT_ID [ACCOUNT_ID ...]\n");
    exit(1);
}

kep_run(function () use ($when, $text, $accountIds) {
    $body = ['text' => $text, 'accounts' => array_map(fn ($id) => ['account_id' => $id], $accountIds)];
    if ($when !== 'draft') {
        $body['schedule_at'] = $when;
    }
    kep_print_created(kep_post('/posts', $body, kep_new_idempotency_key()));
});
