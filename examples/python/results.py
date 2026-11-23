"""How the posts that went out in the last 7, 30 or 90 days are doing, per account
and per post, as each platform reports it.

Usage: python results.py [7|30|90]
"""
import sys

from keepemposted import get, run


def numbers(values, names):
    return ", ".join(f"{names.get(key, key)} {value:,}" for key, value in values.items())


def main():
    days = int(sys.argv[1]) if len(sys.argv) > 1 else 30
    data = get("/results", {"days": days, "limit": 10})
    sent = data["published"]
    print(f"Last {days} days: {sent} post{'' if sent == 1 else 's'} went out")
    print()
    for account in data["accounts"]:
        if not account["published"]:
            continue
        sent = account["published"]
        line = f"{account['platform_name']:<10} {account['handle']:<24} {sent} post{'' if sent == 1 else 's'}  "
        if account["readable"]:
            line += numbers(account["totals"], account["names"]) or "no numbers yet"
        else:
            line += account["note"]
        followers = account.get("followers") or {}
        if followers.get("latest") is not None:
            change = followers.get("change")
            line += f"  {followers['name']} {followers['latest']:,}" + (f" ({change:+,})" if change else "")
        print(line)

    print()
    for found in data["posts"]:
        text = " ".join((found["text"] or found["title"] or "").split())
        print(f"{found['published_at_text']}  {found['platform']}  {text[:60].rstrip()}")
        results = found["results"]
        print(f"  {numbers(results['metrics'], results['labels']) or results['note'] or 'no numbers yet'}")
        if found.get("external_url"):
            print(f"  {found['external_url']}")
    # A number a platform does not give is left out, never zero, and numbers from
    # different platforms are never added together: a view on YouTube is not a like on Bluesky.


run(main)
