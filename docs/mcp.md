# Keep 'em Posted MCP server: let an AI agent write and schedule your social media posts

Keep 'em Posted runs a remote MCP server. An AI agent such as Claude or ChatGPT can read your connected accounts and each brand's voice, write posts fitted to each platform and put them in your queue. A person approves them in the app before they go out, unless the workspace says otherwise.

- **Endpoint:** `https://mcp.keepemposted.com/mcp` (streamable HTTP)
- **Sign-in:** OAuth 2.1 with PKCE. The client registers itself, sends you to Keep 'em Posted to sign in with Google, and you choose the workspace and approve. There is no key to copy.
- **Server card:** [`https://mcp.keepemposted.com/.well-known/mcp.json`](https://mcp.keepemposted.com/.well-known/mcp.json) has the address, the scopes and the live tool list as JSON.

You need a Keep 'em Posted account ([sign in with Google](https://app.keepemposted.com)) with at least one social account connected. Connecting the MCP server costs nothing; a post costs credits when it goes out, as it does from the app.

A connection is for one person in one workspace, and the agent can do what that person can. Every connection is listed in the app under **Settings, Connected AI apps**, where you can remove it or switch it between writing posts and only looking.

## Claude Code

```bash
claude mcp add --transport http keepemposted https://mcp.keepemposted.com/mcp
```

Add `--scope user` to have it in every project. Then run `/mcp` in Claude Code, choose keepemposted and sign in. Approve the workspace in the browser window that opens.

## Claude (web and desktop)

Open Customize, then Connectors. Choose Add, then Add custom connector, name it Keep 'em Posted and paste `https://mcp.keepemposted.com/mcp` as the server URL. Leave the other settings as Claude suggests and choose Add. Claude sends you to Keep 'em Posted to sign in, choose the workspace and approve. In a chat, open the + menu, choose Connectors and switch Keep 'em Posted on.

In a Team or Enterprise plan, an owner adds the connector under Organization settings, Connectors.

## ChatGPT

Open [chatgpt.com/plugins](https://chatgpt.com/plugins), choose the plus button, then Add custom MCP server. Name it, paste `https://mcp.keepemposted.com/mcp` as the server URL and keep sign-in (OAuth) as the authentication. Confirm ChatGPT's warning about custom servers. ChatGPT sends you to Keep 'em Posted to sign in and approve. In a new chat, type @ and pick Keep 'em Posted.

## VS Code

`.vscode/mcp.json`:

```json
{
  "servers": {
    "keepemposted": {
      "type": "http",
      "url": "https://mcp.keepemposted.com/mcp"
    }
  }
}
```

Start the server from the file, and VS Code opens the browser for the sign-in.

## Other clients

Any client that speaks streamable HTTP and the MCP authorization flow works: it finds the authorization server from the first `401`, registers itself and signs in with PKCE. The redirect address a client registers must be `https`, or `http` on a loopback address (`127.0.0.1`, `localhost`, `::1`).

For a script of your own, use an [API key and the REST API](../README.md) instead: the endpoints run the same operations as the tools, with the same answers.

## Permissions

| Scope | Lets the agent | Granted by default |
|---|---|---|
| `posts:read` | Read the workspace, accounts, brands and their voices, posts, media, ideas, snippets, credits and results | Yes |
| `posts:write` | Write and schedule posts, change and cancel them, add pictures and videos, leave notes, add ideas | Yes |

On the consent screen you can choose **Only let it look**. The connection then keeps only `posts:read`, and a tool that would change something answers `insufficient_scope`.

## What waits for a person

The workspace's approval rule decides what happens to what an agent schedules. With the default, it waits as `pending_approval` until a person approves it in the app, and the agent is told so in every answer. A post the agent schedules for now waits the same way and goes out the moment someone approves it.

Some things are a person's call and have no tool: approving a post, sending a failed one again, writing a brand's voice, setting an account's posting times, buying credits, and loading results from a platform that bills each read (X). The agent reads voices and posting times; it does not change them.

## Tools

The server has 23 tools. Every write goes through the same operations the app uses, so the platform checks, the approval rule and the credits apply exactly as they do there.

| Tool | What it does | Scope |
|---|---|---|
| `get_overview` | Start here: the workspace with its time zone and approval rule, its brands and how each one speaks, the connected accounts, and how many posts are in each status | `posts:read` |
| `list_accounts` | The connected accounts, the brand each posts for and how it speaks, and what each platform takes | `posts:read` |
| `get_credits` | The balance, what the scheduled posts will cost, this month's spending and the monthly limit | `posts:read` |
| `find_posts` | Posts with their deliveries, newest first, by status, account, time, tag or words | `posts:read` |
| `get_post` | One post with its media and deliveries, and what stands in the way of each | `posts:read` |
| `get_results` | How the posts that went out are doing, per post, account and platform, and followers over the period | `posts:read` |
| `create_post` | Write a post for one or more accounts, as drafts or with a time | `posts:write` |
| `duplicate_post` | Make a new post like one that exists, as drafts | `posts:write` |
| `update_post` | Change a post's text, title, media or tags | `posts:write` |
| `add_delivery` | Send a post to one more account, as a draft | `posts:write` |
| `update_delivery` | Give one account its own text, first comment, kind of post or options | `posts:write` |
| `schedule_delivery` | Give a draft its time, or the account's next free posting time | `posts:write` |
| `reschedule_delivery` | Move a post that is scheduled or waiting for approval to another time | `posts:write` |
| `unschedule_delivery` | Take a scheduled or waiting post back to a draft so it can be changed | `posts:write` |
| `cancel_delivery` | Stop a post for good on one account | `posts:write` |
| `add_note` | Leave a note on a post for the people of the workspace | `posts:write` |
| `list_media` | The workspace's images and videos, newest first | `posts:read` |
| `add_media_from_url` | Fetch an image or a video from a public https address and store it | `posts:write` |
| `set_media_alt_text` | Describe an image or a video for people who cannot see it | `posts:write` |
| `list_ideas` | The workspace's backlog of things worth posting about | `posts:read` |
| `add_idea` | Put an idea in the backlog for a person to pick from | `posts:write` |
| `update_idea` | Change an idea's line, notes, brand, media or state | `posts:write` |
| `list_snippets` | The saved pieces of text to put into posts: hashtag groups, sign-offs, link lines | `posts:read` |

The server also has one resource, `kep://accounts` (the brands and connected accounts as JSON), and two prompts, `write-post` and `review-posts`.

### Good to know

- **Posts and deliveries.** A post is what was written. It goes to each account as a delivery with its own status, time and, where a platform needs other wording, its own text.
- **Kinds of post.** Each account lists the kinds its platform has (on Instagram: post, reel and story) with what each takes and costs. The agent picks one with `format`.
- **Times** are ISO 8601. A time without an offset is read in the workspace's time zone. `next_free` gives each account its next free posting time, as a person set them in the app.
- **Checks.** What a platform will not take (a post on Instagram without a picture, a Bluesky post over 300 characters) comes back as `issues`, and that account stays a draft with the reason. `advice` is worth knowing and stops nothing.
- **Errors** come back as tool results with `isError: true`; `structuredContent.error` has a `code` and a `message`. Codes include `invalid_arguments`, `insufficient_scope`, `not_found`, `post_locked`, `not_sendable`, `time_passed` and `with_platform`.
- **Limits.** 120 calls a minute per connection and 600 a minute per IP address; 60 files fetched from addresses an hour per connection. Over the limit the server answers `429` with a `Retry-After` header.

## Things to ask

- "What is scheduled for this week, and what is waiting for my approval?"
- "Write a post about our winter blend for every Acme Coffee account, in the brand's voice, for each account's next free time."
- "Which of last month's posts did best on Instagram, and what did they have in common?"
- "Make next Tuesday's Bluesky post shorter and move it to 08:30."

More in the guide: [Connect Claude or ChatGPT to social media](https://keepemposted.com/docs/connect-ai).
