# Keep 'em Posted API and MCP examples: schedule social media posts from code

[![license MIT](https://img.shields.io/badge/license-MIT-blue)](LICENSE)
[![docs](https://img.shields.io/badge/docs-keepemposted.com-F2452B)](https://keepemposted.com/docs/api)

**Write a post once and schedule it to Instagram, Facebook, TikTok, YouTube, X, Bluesky, Mastodon, Telegram and Discord from your own script. Each platform's rules are checked before anything is scheduled, and a person approves before it goes out.**

These are runnable examples for the [Keep 'em Posted REST API](https://keepemposted.com/docs/api) in curl, Python, JavaScript and PHP, plus the setup for the [Keep 'em Posted MCP server](docs/mcp.md) that lets Claude, ChatGPT and other AI agents do the same. [Keep 'em Posted](https://keepemposted.com) is a social media scheduler for people who run many brands on many accounts: one queue for every account, a voice per brand, and approval in the app for what an agent or a script wrote. The examples have no dependencies, so you can copy one file and run it.

```bash
curl -X POST "https://api.keepemposted.com/v1/posts" \
  -H "Authorization: Bearer kep_live_your_key" \
  -H "Content-Type: application/json" \
  -H "Idempotency-Key: 9b1f7e52-winter-blend" \
  -d '{
    "text": "The winter blend is back: dark chocolate, orange peel and a long finish.",
    "accounts": [
      {"account_id": "4a67d469-2885-4d81-bfba-c0c7ebf43de0"},
      {"account_id": "d9510dd3-0494-44d0-85b6-c9ac288c2026"}
    ],
    "schedule_at": "2026-11-27T09:00"
  }'
```

```json
{
  "result": [
    {
      "delivery_id": "01a207eb-9734-72c6-bd06-b3038082811e",
      "account": "@acme.bsky.social",
      "status": "pending_approval",
      "standing": "waiting for a person to approve it in the app; planned for Fri 27 Nov 2026 09:00 (Europe/Copenhagen). It does not go out until then",
      "credits": 10
    },
    {
      "delivery_id": "01a207eb-9736-70be-b7d1-88bc4d4c662a",
      "account": "@acme.coffee",
      "status": "draft",
      "standing": "a draft: it has no time and does not go out until it is scheduled",
      "credits": 10
    }
  ],
  "not_scheduled": [
    {
      "delivery_id": "01a207eb-9736-70be-b7d1-88bc4d4c662a",
      "code": "not_sendable",
      "message": "A post on Instagram needs an image or a video",
      "issues": [{ "code": "media_required", "message": "A post on Instagram needs an image or a video" }]
    }
  ],
  "post": { "id": "01a207eb-9730-7ab4-9c51-6f2d0e8b1a44", "text": "The winter blend is back: …", "deliveries": [ … ] }
}
```

One post, two accounts, two outcomes. Bluesky took the post, and it waits for a person to approve it in the app before 27 November. Instagram needs a picture, so that account stays a draft with the reason, and nothing is charged for it. The answer is shortened here. Acme and its accounts in this repository are made up.

## Get started

1. Sign in at [app.keepemposted.com](https://app.keepemposted.com) with Google and connect your social accounts. Connecting accounts is free, and a new account starts with free credits.
2. Open **Settings, API keys** and make a key (workspace owners and admins can). Say what will use it, choose whether it may only read or also write posts, and when it expires, if ever. The key starts with `kep_live_` and is shown once.
3. Set the key as an environment variable:

   ```bash
   export KEEPEMPOSTED_API_KEY=kep_live_your_key
   ```

4. Run an example:

   ```bash
   python examples/python/accounts.py
   node examples/javascript/accounts.mjs
   php examples/php/accounts.php
   ./examples/curl/accounts.sh
   ```

`accounts` prints the id of each connected account and the kinds of post it takes. The examples that write take a time and those ids:

```bash
python examples/python/create_post.py WHEN "TEXT" ACCOUNT_ID [ACCOUNT_ID ...]
```

`WHEN` is `draft` (nothing is scheduled), `next_free` (each account's next free posting time, as you set them in the app) or a time such as `2026-11-27T09:00`, read in the workspace's time zone.

## The examples

| What you want | Endpoint | curl | Python | JavaScript | PHP |
|---|---|---|---|---|---|
| See the workspace, its approval rule and its credits | `GET /v1/workspace` | [workspace.sh](examples/curl/workspace.sh) | [workspace.py](examples/python/workspace.py) | [workspace.mjs](examples/javascript/workspace.mjs) | [workspace.php](examples/php/workspace.php) |
| List the accounts and the kinds of post each takes | `GET /v1/accounts` | [accounts.sh](examples/curl/accounts.sh) | [accounts.py](examples/python/accounts.py) | [accounts.mjs](examples/javascript/accounts.mjs) | [accounts.php](examples/php/accounts.php) |
| Write a post for several accounts and schedule it | `POST /v1/posts` | [create-post.sh](examples/curl/create-post.sh) | [create_post.py](examples/python/create_post.py) | [create-post.mjs](examples/javascript/create-post.mjs) | [create-post.php](examples/php/create-post.php) |
| Post a picture from a web address, with alt text | `POST /v1/media/from-url`, `POST /v1/posts` | [post-with-picture.sh](examples/curl/post-with-picture.sh) | [post_with_picture.py](examples/python/post_with_picture.py) | [post-with-picture.mjs](examples/javascript/post-with-picture.mjs) | [post-with-picture.php](examples/php/post-with-picture.php) |
| See what waits for approval and what is scheduled | `GET /v1/posts` | [upcoming.sh](examples/curl/upcoming.sh) | [upcoming.py](examples/python/upcoming.py) | [upcoming.mjs](examples/javascript/upcoming.mjs) | [upcoming.php](examples/php/upcoming.php) |
| See how the posts that went out are doing | `GET /v1/results` | [results.sh](examples/curl/results.sh) | [results.py](examples/python/results.py) | [results.mjs](examples/javascript/results.mjs) | [results.php](examples/php/results.php) |

Each language has a small client file (`keepemposted.py`, `keepemposted.mjs`, `keepemposted.php`). It puts the key in the header, sends an `Idempotency-Key` with every new post, and when the API refuses, prints its error code and message and what the platform will not take. The curl examples print the raw JSON; pipe it through `jq` to read it. The two curl examples that write need `jq` to build the request.

This is what they print:

```
$ python examples/python/accounts.py
Bluesky    @acme.bsky.social        4a67d469-2885-4d81-bfba-c0c7ebf43de0
           Kinds of post: post (10 credits)

Instagram  @acme.coffee             d9510dd3-0494-44d0-85b6-c9ac288c2026
           Kinds of post: post (10 credits), reel (20 credits), story (10 credits)
           Brand: Acme Coffee

TikTok     @acme.clips              3985a510-b9ed-47ef-835f-9a950a06eef3
           Kinds of post: video (20 credits), photos (10 credits)
           Brand: Acme Roastery
```

```
$ node examples/javascript/post-with-picture.mjs 2026-11-28T10:30 https://keepemposted.com/opengraph-image \
    "A poster that reads: Twenty accounts. One schedule." \
    "Twenty accounts, one schedule. How we plan a month of posts in an hour: link in bio." \
    d9510dd3-0494-44d0-85b6-c9ac288c2026 6d271698-01ec-4c23-98dc-95d81de3c620
Picture 01a207f1-6284-712d-8aa3-39001f025a51: 1200 x 630, 62 kB
Post 01a207f1-6293-71ec-8108-f4e371c1bf05
  @acme.coffee: waiting for a person to approve it in the app; planned for Sat 28 Nov 2026 10:30 (Europe/Copenhagen). It does not go out until then
  @acme.roastery: waiting for a person to approve it in the app; planned for Sat 28 Nov 2026 10:30 (Europe/Copenhagen). It does not go out until then
```

```
$ php examples/php/upcoming.php
Thu 26 Nov 2026 08:30 (Europe/Copenhagen)  needs approval  @acme.bsky.social      Roasting day. The first batch of the winter blend is cooling
Fri 27 Nov 2026 09:00 (Europe/Copenhagen)  needs approval  @acme.bsky.social      The winter blend is back: dark chocolate, orange peel and a
Sat 28 Nov 2026 10:30 (Europe/Copenhagen)  needs approval  @acme.coffee           Twenty accounts, one schedule. How we plan a month of posts
Sat 28 Nov 2026 10:30 (Europe/Copenhagen)  needs approval  @acme.roastery         Twenty accounts, one schedule. How we plan a month of posts
```

## What happens when your script schedules a post

- **Each account is a delivery.** A post is what was written. It goes to each account as a delivery with its own status, time and, where a platform needs other wording, its own text (`accounts[].text`). The statuses are `draft`, `pending_approval`, `scheduled`, `publishing`, `published`, `failed` and `canceled`.
- **A person approves, unless the workspace says otherwise.** The workspace's approval rule decides what happens to what a key schedules. With the default, it waits as `pending_approval` until someone approves it in the app; set to none, it goes out at its time. `GET /v1/workspace` says which rule applies, and every answer says what happened, per account, in `result`. There is no endpoint that approves a post or sends a failed one again: both are done by a person in the app.
- **Each platform's rules are checked first.** What a platform will not take (an Instagram post without a picture, a Bluesky post over 300 characters, a video that is too long) keeps that account a draft, and `not_scheduled` says why. The other accounts are still scheduled. `advice` on a delivery is worth knowing and stops nothing.
- **Kinds of post.** `GET /v1/accounts` lists the kinds each platform has (on Instagram: post, reel and story; on TikTok: video and photos) with what each takes and what it costs. Send the kind as `accounts[].format`, and its choices (a YouTube title, a reel's cover, the parts of an X thread) in `accounts[].options`.
- **Times.** ISO 8601. A time without an offset, such as `2026-11-27T09:00`, is read in the workspace's time zone, and times come back with its offset. `next_free` gives each account its next free posting time.
- **Repeats are safe.** Send an `Idempotency-Key` header with a new post. If the request times out, send it again with the same key: you get the post that was already made, with `already_created: true`, and nothing is scheduled twice.

## The API

Everything is under `https://api.keepemposted.com/v1`.

| Method and path | What it does |
|---|---|
| `GET /workspace` | The workspace, its approval rule, the key's scopes and the credits |
| `GET /credits` | The balance, what scheduled posts will cost, this month's spending and the monthly limit |
| `GET /accounts` | The connected accounts with their kinds of post, posting times and next free time |
| `GET /brands` | The brands and each one's voice, as a person wrote it in the app |
| `POST /posts` | Write a post for one or more accounts, as drafts or scheduled |
| `GET /posts` | Posts with their deliveries, newest first (`status`, `account_id`, `scheduled_from`, `scheduled_before`, `tag`, `search`, `cursor`) |
| `GET /posts/{post_id}` | One post with its media, deliveries and notes |
| `PATCH /posts/{post_id}` | Change a post's text, title, media or tags |
| `DELETE /posts/{post_id}` | Delete a post with nothing scheduled, waiting or out |
| `POST /posts/{post_id}/duplicate` | A new post like an old one, as drafts |
| `POST /posts/{post_id}/deliveries` | Send a post to one more account, as a draft |
| `POST /posts/{post_id}/notes` | Leave a note on a post for the people of the workspace |
| `PATCH /deliveries/{delivery_id}` | Give one account its own text, first comment, kind of post or options |
| `POST /deliveries/{delivery_id}/schedule` | Give a draft its time |
| `POST /deliveries/{delivery_id}/reschedule` | Move a scheduled or waiting post to another time |
| `POST /deliveries/{delivery_id}/unschedule` | Take it back to a draft so it can be changed |
| `POST /deliveries/{delivery_id}/cancel` | Do not post it to this account |
| `GET /media` | The workspace's images and videos |
| `POST /media` | Upload an image or a video (`multipart/form-data`, field `file`) |
| `POST /media/from-url` | Add an image or a video from a public https address |
| `PATCH /media/{media_id}` | Set a file's alt text |
| `GET /ideas`, `POST /ideas`, `PATCH /ideas/{idea_id}` | The backlog of things worth posting about |
| `GET /snippets` | Saved hashtag groups, sign-offs and link lines |
| `GET /results` | How the posts that went out are doing, per post, account and platform (`days` 7, 30 or 90, or `from` and `to`) |

The full reference with every field is the [OpenAPI document](https://api.keepemposted.com/v1/openapi.json) and the [API guide](https://keepemposted.com/docs/api).

## Credits, limits and errors

- **A post costs credits per account when it goes out**, never when it is scheduled, and a post that does not go out costs nothing. What each kind of post costs is on each account in `GET /v1/accounts`; the plans and packs are on [the pricing page](https://keepemposted.com/pricing), or as JSON without a key: `curl https://api.keepemposted.com/pricing`. A post that is due while the workspace is out of credits is not refused: it waits, goes out as soon as credits arrive, and fails only when none have come within a day. A key cannot buy credits.
- **Results** are what each platform lets us read: a number a platform does not give is left out, never zero, and numbers from different platforms are never added together. Reading them costs nothing.
- **Rate limit:** 120 requests a minute per key. Every answer carries `RateLimit-Limit`, `RateLimit-Remaining` and `RateLimit-Reset`; over the limit you get `429` with `Retry-After`.
- **A key belongs to the workspace**, not to the person who made it. Make one per tool, and remove it under Settings, API keys when the tool is retired: the next request with it is refused.

Errors have one shape, with a stable code:

```json
{ "error": { "code": "post_locked", "message": "This post has a scheduled delivery. Take it back to a draft before changing the post.", "current": "scheduled" } }
```

| Status | Meaning |
|---|---|
| `401` | No key, or an unknown, removed or expired one (`invalid_key`) |
| `403` | The key can only read and this changes something (`insufficient_scope`) |
| `404` | Nothing with that id in the key's workspace (`not_found`) |
| `409` | The post or delivery is not in a state that allows this (`post_locked`, `wrong_status`) |
| `422` | A field is wrong, or the platform will not take the content (`invalid_request`, `not_sendable` with `issues`, `time_passed`) |
| `429` | More than 120 requests in a minute (`rate_limited`, with `retry_after`) |

## Use Keep 'em Posted from an AI agent

Keep 'em Posted has an MCP server at `https://mcp.keepemposted.com/mcp`. Claude, ChatGPT, Claude Code, VS Code and other MCP clients sign in with OAuth and can read your accounts and each brand's voice, write posts fitted to each platform and put them in your queue for you to approve. See [the setup and the 23 tools](docs/mcp.md).

## Documentation

- [API guide](https://keepemposted.com/docs/api) and the [OpenAPI document](https://api.keepemposted.com/v1/openapi.json)
- [Connect Claude or ChatGPT](https://keepemposted.com/docs/connect-ai) and [the MCP server for social media](https://keepemposted.com/social-media-mcp)
- [All guides](https://keepemposted.com/docs)

Questions? Write to hello@keepemposted.com or open an issue here.

## License

The examples are under the [MIT license](LICENSE). Use them as you like.
