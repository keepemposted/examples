"""Write one post for one or more accounts: as drafts, for each account's next free
posting time, or for a time you give.

Usage: python create_post.py WHEN "TEXT" ACCOUNT_ID [ACCOUNT_ID ...]

WHEN is draft (nothing is scheduled), next_free (each account's next free posting
time, as set in the app) or a time such as 2026-11-27T09:00, read in the
workspace's time zone. accounts.py lists the account ids.
"""
import sys

from keepemposted import new_idempotency_key, post, print_created, run


def main():
    if len(sys.argv) < 4:
        sys.exit(__doc__)
    when, text, account_ids = sys.argv[1], sys.argv[2], sys.argv[3:]
    body = {"text": text, "accounts": [{"account_id": account_id} for account_id in account_ids]}
    if when != "draft":
        body["schedule_at"] = when
    print_created(post("/posts", body, idempotency_key=new_idempotency_key()))


run(main)
