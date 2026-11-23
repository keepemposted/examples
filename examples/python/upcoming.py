"""What waits for a person's approval and what is scheduled, in the order it goes out.

Usage: python upcoming.py
"""
from datetime import datetime

from keepemposted import get, run

WORDS = {"pending_approval": "needs approval", "scheduled": "scheduled"}


def deliveries(status):
    """Every delivery in this status, one page of posts at a time."""
    cursor = None
    while True:
        page = get("/posts", {"status": status, "limit": 50, "cursor": cursor})
        for found in page["posts"]:
            for delivery in found["deliveries"]:
                if delivery["status"] == status:
                    yield delivery, found
        cursor = page["next_cursor"]
        if not cursor:
            return


def main():
    rows = [row for status in WORDS for row in deliveries(status)]
    if not rows:
        print("Nothing waits for approval and nothing is scheduled.")
        return
    # What goes out the moment it is approved has no time and comes first.
    rows.sort(key=lambda row: (row[0]["scheduled_at"] is not None,
                               datetime.fromisoformat(row[0]["scheduled_at"]) if row[0]["scheduled_at"] else 0))
    for delivery, found in rows:
        when = delivery["scheduled_at_text"] or "As soon as it is approved"
        state = "going out" if delivery.get("on_its_way") else WORDS[delivery["status"]]
        text = " ".join((delivery["text"] or found["text"] or found["title"] or "").split())
        print(f"{when:<42} {state:<15} {delivery['account']['handle']:<22} {text[:60].rstrip()}")


run(main)
