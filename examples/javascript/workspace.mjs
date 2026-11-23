// The workspace the key belongs to: its time zone, what happens to what the key
// schedules, and what it has to spend.
// Usage: KEEPEMPOSTED_API_KEY=kep_live_... node workspace.mjs
import { get, run } from "./keepemposted.mjs";

await run(async () => {
  const data = await get("/workspace");
  const { workspace, approval, credits } = data;
  console.log(`${workspace.name} (${workspace.slug}), time zone ${workspace.timezone}`);
  console.log(`Now: ${data.now_text}`);
  console.log();
  console.log(`Approval: ${approval.mode}`);
  console.log(`  ${approval.rule}`);
  console.log();
  console.log(`Credits: ${credits.balance} (${credits.paid_balance} paid), ${credits.committed} spoken for by what is scheduled`);
  const counts = Object.entries(data.deliveries)
    .filter(([, count]) => count)
    .map(([status, count]) => `${count} ${status}`)
    .join(", ");
  console.log(`Posts per account: ${counts || "none yet"}`);
  console.log(`This key: ${data.key.name} (${data.key.scopes.join(", ")})`);
});
