<?php
// Minimal client for the Keep 'em Posted API. PHP 8.0+ with the curl extension.

class KeepEmPostedApiError extends Exception
{
    /**
     * An answer that was not 2xx. $code is the API's stable error code: invalid_key (401),
     * insufficient_scope (403, a read-only key), not_found (404), post_locked (409),
     * not_sendable (422, with the platform's issues), rate_limited (429, $retryAfter holds
     * the seconds to wait).
     */
    public function __construct(public int $status, public string $errorCode, public array $error, public ?string $retryAfter = null)
    {
        parent::__construct("HTTP $status $errorCode: " . ($error['message'] ?? ''));
    }
}

/** Call a path under /v1 and return the decoded JSON as an array (null when there is no body). */
function kep_request(string $method, string $path, array $params = [], ?array $body = null, ?string $idempotencyKey = null): ?array
{
    $base = rtrim(getenv('KEEPEMPOSTED_API_URL') ?: 'https://api.keepemposted.com', '/');
    $apiKey = getenv('KEEPEMPOSTED_API_KEY') ?: null;
    if (!$apiKey) {
        fwrite(STDERR, "Set KEEPEMPOSTED_API_KEY to an API key (app.keepemposted.com, Settings, API keys).\n");
        exit(1);
    }
    $params = array_filter($params, fn ($value) => $value !== null);
    $url = $base . '/v1' . $path . ($params ? '?' . http_build_query($params) : '');

    $headers = ['Accept: application/json', "Authorization: Bearer $apiKey"];
    if ($body !== null) {
        $headers[] = 'Content-Type: application/json';
    }
    if ($idempotencyKey !== null) {
        $headers[] = "Idempotency-Key: $idempotencyKey";
    }
    $retryAfter = null;
    $curl = curl_init($url);
    curl_setopt_array($curl, [
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_USERAGENT => 'keepemposted-examples-php',
        CURLOPT_TIMEOUT => 60,
        CURLOPT_HEADERFUNCTION => function ($curl, $line) use (&$retryAfter) {
            if (stripos($line, 'retry-after:') === 0) {
                $retryAfter = trim(substr($line, 12));
            }
            return strlen($line);
        },
    ]);
    if ($body !== null) {
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($body, JSON_THROW_ON_ERROR));
    }
    $answer = curl_exec($curl);
    if ($answer === false) {
        throw new RuntimeException('Request failed: ' . curl_error($curl));
    }
    $status = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);

    if ($status < 200 || $status >= 300) {
        $error = json_decode($answer, true)['error'] ?? ['code' => "http_$status", 'message' => substr($answer, 0, 200)];
        throw new KeepEmPostedApiError($status, $error['code'], $error, $retryAfter);
    }
    return $answer === '' ? null : json_decode($answer, true, 512, JSON_THROW_ON_ERROR);
}

function kep_get(string $path, array $params = []): ?array
{
    return kep_request('GET', $path, $params);
}

function kep_post(string $path, array $body, ?string $idempotencyKey = null): ?array
{
    return kep_request('POST', $path, [], $body, $idempotencyKey);
}

/**
 * A fresh key for one new post. If the request times out, send it again with the same
 * key: you get the post that was already made, never a second one.
 */
function kep_new_idempotency_key(): string
{
    $bytes = random_bytes(16);
    $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
    $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
}

/** What happened to a new post on each account. */
function kep_print_created(array $answer): void
{
    $refused = [];
    foreach ($answer['not_scheduled'] ?? [] as $item) {
        $refused[$item['delivery_id']] = $item;
    }
    echo "Post {$answer['post']['id']}\n";
    foreach ($answer['result'] as $item) {
        echo "  {$item['account']}: {$item['standing']}\n";
        if (isset($refused[$item['delivery_id']])) {
            echo "    Not scheduled: {$refused[$item['delivery_id']]['message']}\n";
        }
    }
}

/** Run an example, and say what the API refused in its own words. */
function kep_run(callable $main): void
{
    try {
        $main();
    } catch (KeepEmPostedApiError $error) {
        fwrite(STDERR, "{$error->errorCode}: {$error->error['message']}\n");
        foreach ($error->error['issues'] ?? [] as $issue) {
            fwrite(STDERR, "  {$issue['message']}\n");
        }
        exit(1);
    }
}
