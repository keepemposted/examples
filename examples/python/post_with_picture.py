"""Fetch a picture from a web address, describe it, and post it to one or more accounts.

Usage: python post_with_picture.py WHEN PICTURE_URL "ALT TEXT" "TEXT" ACCOUNT_ID [ACCOUNT_ID ...]

WHEN is draft, next_free or a time such as 2026-11-27T09:00 (see create_post.py).
The picture must be at a public https address. The alt text goes out with the post
on the platforms that have alt text.
"""
import sys

from keepemposted import new_idempotency_key, post, print_created, run


def main():
    if len(sys.argv) < 6:
        sys.exit(__doc__)
    when, picture_url, alt_text, text, account_ids = sys.argv[1], sys.argv[2], sys.argv[3], sys.argv[4], sys.argv[5:]

    media = post("/media/from-url", {"url": picture_url, "alt_text": alt_text})["media"]
    print(f"Picture {media['id']}: {media['width']} x {media['height']}, {media['size_bytes'] // 1024} kB")

    body = {"text": text, "media_ids": [media["id"]],
            "accounts": [{"account_id": account_id} for account_id in account_ids]}
    if when != "draft":
        body["schedule_at"] = when
    print_created(post("/posts", body, idempotency_key=new_idempotency_key()))


run(main)
