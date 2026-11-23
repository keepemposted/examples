// Write one post for one or more accounts: as drafts, for each account's next free
// posting time, or for a time you give.
//
// Usage: node create-post.mjs WHEN "TEXT" ACCOUNT_ID [ACCOUNT_ID ...]
//
// WHEN is draft (nothing is scheduled), next_free (each account's next free posting
// time, as set in the app) or a time such as 2026-11-27T09:00, read in the
// workspace's time zone. accounts.mjs lists the account ids.
import { newIdempotencyKey, post, printCreated, run } from "./keepemposted.mjs";

const [when, text, ...accountIds] = process.argv.slice(2);
if (!accountIds.length) {
  console.error('Usage: node create-post.mjs WHEN "TEXT" ACCOUNT_ID [ACCOUNT_ID ...]');
  process.exit(1);
}

await run(async () => {
  const body = { text, accounts: accountIds.map((id) => ({ account_id: id })) };
  if (when !== "draft") body.schedule_at = when;
  printCreated(await post("/posts", body, newIdempotencyKey()));
});
