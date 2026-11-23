// Minimal client for the Keep 'em Posted API. No dependencies, Node 18+ (built-in fetch).
import { randomUUID } from "node:crypto";

const BASE_URL = (process.env.KEEPEMPOSTED_API_URL ?? "https://api.keepemposted.com").replace(/\/+$/, "");

// An answer that was not 2xx. code is the API's stable error code: invalid_key (401),
// insufficient_scope (403, a read-only key), not_found (404), post_locked (409),
// not_sendable (422, with the platform's issues), rate_limited (429, retryAfter holds
// the seconds to wait).
export class ApiError extends Error {
  constructor(status, error, retryAfter) {
    super(`HTTP ${status} ${error.code}: ${error.message}`);
    this.status = status;
    this.code = error.code;
    this.error = error;
    this.retryAfter = retryAfter;
  }
}

// Call a path under /v1 and return the decoded JSON (null when there is no body).
export async function request(method, path, { params, body, idempotencyKey } = {}) {
  const apiKey = process.env.KEEPEMPOSTED_API_KEY;
  if (!apiKey) {
    console.error("Set KEEPEMPOSTED_API_KEY to an API key (app.keepemposted.com, Settings, API keys).");
    process.exit(1);
  }
  const url = new URL(`${BASE_URL}/v1${path}`);
  for (const [name, value] of Object.entries(params ?? {})) {
    if (value != null) url.searchParams.set(name, value);
  }
  const headers = {
    Accept: "application/json",
    Authorization: `Bearer ${apiKey}`,
    "User-Agent": "keepemposted-examples-node",
  };
  if (body !== undefined) headers["Content-Type"] = "application/json";
  if (idempotencyKey) headers["Idempotency-Key"] = idempotencyKey;

  const response = await fetch(url, { method, headers, body: body === undefined ? undefined : JSON.stringify(body) });
  const text = await response.text();
  if (!response.ok) {
    let error;
    try {
      error = JSON.parse(text).error;
    } catch {
      error = { code: `http_${response.status}`, message: text.slice(0, 200) };
    }
    throw new ApiError(response.status, error, response.headers.get("retry-after"));
  }
  return text ? JSON.parse(text) : null;
}

export const get = (path, params) => request("GET", path, { params });
export const post = (path, body, idempotencyKey) => request("POST", path, { body, idempotencyKey });

// A fresh key for one new post. If the request times out, send it again with the same
// key: you get the post that was already made, never a second one.
export const newIdempotencyKey = () => randomUUID();

// What happened to a new post on each account.
export function printCreated(answer) {
  const refused = new Map((answer.not_scheduled ?? []).map((item) => [item.delivery_id, item]));
  console.log(`Post ${answer.post.id}`);
  for (const item of answer.result) {
    console.log(`  ${item.account}: ${item.standing}`);
    if (refused.has(item.delivery_id)) console.log(`    Not scheduled: ${refused.get(item.delivery_id).message}`);
  }
}

// Run an example, and say what the API refused in its own words.
export async function run(main) {
  try {
    await main();
  } catch (error) {
    if (!(error instanceof ApiError)) throw error;
    console.error(`${error.code}: ${error.error.message}`);
    for (const issue of error.error.issues ?? []) console.error(`  ${issue.message}`);
    process.exit(1);
  }
}
