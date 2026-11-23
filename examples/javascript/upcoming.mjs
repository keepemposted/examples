// What waits for a person's approval and what is scheduled, in the order it goes out.
// Usage: node upcoming.mjs
import { get, run } from "./keepemposted.mjs";

const WORDS = { pending_approval: "needs approval", scheduled: "scheduled" };

// Every delivery in this status, one page of posts at a time.
async function deliveries(status) {
  const rows = [];
  let cursor = null;
  do {
    const page = await get("/posts", { status, limit: 50, cursor });
    for (const found of page.posts) {
      for (const delivery of found.deliveries) {
        if (delivery.status === status) rows.push({ delivery, found });
      }
    }
    cursor = page.next_cursor;
  } while (cursor);
  return rows;
}

await run(async () => {
  const rows = [...(await deliveries("pending_approval")), ...(await deliveries("scheduled"))];
  if (!rows.length) {
    console.log("Nothing waits for approval and nothing is scheduled.");
    return;
  }
  // What goes out the moment it is approved has no time and comes first.
  const at = ({ delivery }) => (delivery.scheduled_at ? Date.parse(delivery.scheduled_at) : -Infinity);
  rows.sort((a, b) => (at(a) === at(b) ? 0 : at(a) - at(b)));
  for (const { delivery, found } of rows) {
    const when = delivery.scheduled_at_text ?? "As soon as it is approved";
    const state = delivery.on_its_way ? "going out" : WORDS[delivery.status];
    const text = (delivery.text ?? found.text ?? found.title ?? "").split(/\s+/).join(" ").trim();
    console.log(`${when.padEnd(42)} ${state.padEnd(15)} ${delivery.account.handle.padEnd(22)} ${text.slice(0, 60).trimEnd()}`);
  }
});
