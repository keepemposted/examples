"""The workspace the key belongs to: its time zone, what happens to what the key
schedules, and what it has to spend.

Usage: KEEPEMPOSTED_API_KEY=kep_live_... python workspace.py
"""
from keepemposted import get, run


def main():
    data = get("/workspace")
    workspace = data["workspace"]
    print(f"{workspace['name']} ({workspace['slug']}), time zone {workspace['timezone']}")
    print(f"Now: {data['now_text']}")
    print()
    print(f"Approval: {data['approval']['mode']}")
    print(f"  {data['approval']['rule']}")
    print()
    credits = data["credits"]
    print(f"Credits: {credits['balance']} ({credits['paid_balance']} paid), "
          f"{credits['committed']} spoken for by what is scheduled")
    counts = ", ".join(f"{count} {status}" for status, count in data["deliveries"].items() if count)
    print(f"Posts per account: {counts or 'none yet'}")
    print(f"This key: {data['key']['name']} ({', '.join(data['key']['scopes'])})")


run(main)
