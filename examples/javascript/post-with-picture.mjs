// Fetch a picture from a web address, describe it, and post it to one or more accounts.
//
// Usage: node post-with-picture.mjs WHEN PICTURE_URL "ALT TEXT" "TEXT" ACCOUNT_ID [ACCOUNT_ID ...]
//
// WHEN is draft, next_free or a time such as 2026-11-27T09:00 (see create-post.mjs).
// The picture must be at a public https address. The alt text goes out with the post
// on the platforms that have alt text.
import { newIdempotencyKey, post, printCreated, run } from "./keepemposted.mjs";

const [when, pictureUrl, altText, text, ...accountIds] = process.argv.slice(2);
if (!accountIds.length) {
  console.error('Usage: node post-with-picture.mjs WHEN PICTURE_URL "ALT TEXT" "TEXT" ACCOUNT_ID [ACCOUNT_ID ...]');
  process.exit(1);
}

await run(async () => {
  const { media } = await post("/media/from-url", { url: pictureUrl, alt_text: altText });
  console.log(`Picture ${media.id}: ${media.width} x ${media.height}, ${Math.floor(media.size_bytes / 1024)} kB`);

  const body = { text, media_ids: [media.id], accounts: accountIds.map((id) => ({ account_id: id })) };
  if (when !== "draft") body.schedule_at = when;
  printCreated(await post("/posts", body, newIdempotencyKey()));
});
