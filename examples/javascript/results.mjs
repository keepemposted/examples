// How the posts that went out in the last 7, 30 or 90 days are doing, per account
// and per post, as each platform reports it.
// Usage: node results.mjs [7|30|90]
import { get, run } from "./keepemposted.mjs";

const days = Number(process.argv[2] ?? 30);
const plural = (count, word) => `${count} ${word}${count === 1 ? "" : "s"}`;
const numbers = (values, names) =>
  Object.entries(values)
    .map(([key, value]) => `${names[key] ?? key} ${value.toLocaleString("en-US")}`)
    .join(", ");

await run(async () => {
  const data = await get("/results", { days, limit: 10 });
  console.log(`Last ${days} days: ${plural(data.published, "post")} went out`);
  console.log();
  for (const account of data.accounts) {
    if (!account.published) continue;
    let line = `${account.platform_name.padEnd(10)} ${account.handle.padEnd(24)} ${plural(account.published, "post")}  `;
    line += account.readable ? numbers(account.totals, account.names) || "no numbers yet" : account.note;
    const followers = account.followers ?? {};
    if (followers.latest != null) {
      const change = followers.change ? ` (${followers.change > 0 ? "+" : ""}${followers.change.toLocaleString("en-US")})` : "";
      line += `  ${followers.name} ${followers.latest.toLocaleString("en-US")}${change}`;
    }
    console.log(line);
  }

  console.log();
  for (const found of data.posts) {
    const text = (found.text ?? found.title ?? "").split(/\s+/).join(" ").trim();
    console.log(`${found.published_at_text}  ${found.platform}  ${text.slice(0, 60).trimEnd()}`);
    const { metrics, labels, note } = found.results;
    console.log(`  ${numbers(metrics, labels) || note || "no numbers yet"}`);
    if (found.external_url) console.log(`  ${found.external_url}`);
  }
  // A number a platform does not give is left out, never zero, and numbers from
  // different platforms are never added together: a view on YouTube is not a like on Bluesky.
});
