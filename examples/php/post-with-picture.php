<?php
// Fetch a picture from a web address, describe it, and post it to one or more accounts.
//
// Usage: php post-with-picture.php WHEN PICTURE_URL "ALT TEXT" "TEXT" ACCOUNT_ID [ACCOUNT_ID ...]
//
// WHEN is draft, next_free or a time such as 2026-11-27T09:00 (see create-post.php).
// The picture must be at a public https address. The alt text goes out with the post
// on the platforms that have alt text.
require __DIR__ . '/keepemposted.php';

[$when, $pictureUrl, $altText, $text] = array_pad(array_slice($argv, 1, 4), 4, null);
$accountIds = array_slice($argv, 5);
if (!$accountIds) {
    fwrite(STDERR, "Usage: php post-with-picture.php WHEN PICTURE_URL \"ALT TEXT\" \"TEXT\" ACCOUNT_ID [ACCOUNT_ID ...]\n");
    exit(1);
}

kep_run(function () use ($when, $pictureUrl, $altText, $text, $accountIds) {
    $media = kep_post('/media/from-url', ['url' => $pictureUrl, 'alt_text' => $altText])['media'];
    echo "Picture {$media['id']}: {$media['width']} x {$media['height']}, " . intdiv($media['size_bytes'], 1024) . " kB\n";

    $body = [
        'text' => $text,
        'media_ids' => [$media['id']],
        'accounts' => array_map(fn ($id) => ['account_id' => $id], $accountIds),
    ];
    if ($when !== 'draft') {
        $body['schedule_at'] = $when;
    }
    kep_print_created(kep_post('/posts', $body, kep_new_idempotency_key()));
});
