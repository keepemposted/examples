"""The connected accounts: the id to post to, and the kinds of post each one takes
with what each costs in credits.

Usage: python accounts.py
"""
from keepemposted import get, run


def main():
    accounts = get("/accounts")["accounts"]
    if not accounts:
        print("No accounts yet. Connect one in the app: https://app.keepemposted.com")
        return
    for account in accounts:
        print(f"{account['platform_name']:<10} {account['handle']:<24} {account['id']}")
        if not account["can_publish"]:
            print(f"{'':<11}Connect it again in the app before posting to it ({account['status']}).")
        kinds = ", ".join(f"{kind['key']} ({kind['credits']} credits)"
                          for kind in account["formats"] if not kind.get("blocked"))
        print(f"{'':<11}Kinds of post: {kinds}")
        if account.get("brand"):
            print(f"{'':<11}Brand: {account['brand']['name']}")
        if account.get("next_free_at_text"):
            print(f"{'':<11}Next free posting time: {account['next_free_at_text']}")
        print()


run(main)
