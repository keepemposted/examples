// The connected accounts: the id to post to, and the kinds of post each one takes
// with what each costs in credits.
// Usage: node accounts.mjs
import { get, run } from "./keepemposted.mjs";

const pad = " ".repeat(11);

await run(async () => {
  const { accounts } = await get("/accounts");
  if (!accounts.length) {
    console.log("No accounts yet. Connect one in the app: https://app.keepemposted.com");
    return;
  }
  for (const account of accounts) {
    console.log(`${account.platform_name.padEnd(10)} ${account.handle.padEnd(24)} ${account.id}`);
    if (!account.can_publish) console.log(`${pad}Connect it again in the app before posting to it (${account.status}).`);
    const kinds = account.formats
      .filter((kind) => !kind.blocked)
      .map((kind) => `${kind.key} (${kind.credits} credits)`)
      .join(", ");
    console.log(`${pad}Kinds of post: ${kinds}`);
    if (account.brand) console.log(`${pad}Brand: ${account.brand.name}`);
    if (account.next_free_at_text) console.log(`${pad}Next free posting time: ${account.next_free_at_text}`);
    console.log();
  }
});
