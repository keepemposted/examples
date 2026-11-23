"""Minimal client for the Keep 'em Posted API. Standard library only, Python 3.8+."""
import json
import os
import sys
import urllib.error
import urllib.parse
import urllib.request
import uuid

BASE_URL = os.environ.get("KEEPEMPOSTED_API_URL", "https://api.keepemposted.com").rstrip("/")


class ApiError(Exception):
    """An answer that was not 2xx.

    code is the API's stable error code: invalid_key (401), insufficient_scope (403,
    a read-only key), not_found (404), post_locked (409), not_sendable (422, with the
    platform's issues), rate_limited (429, retry_after holds the seconds to wait).
    """

    def __init__(self, status, code, message, error=None, retry_after=None):
        super().__init__(f"HTTP {status} {code}: {message}")
        self.status = status
        self.code = code
        self.message = message
        self.error = error or {}
        self.retry_after = retry_after


def request(method, path, params=None, body=None, idempotency_key=None):
    """Call a path under /v1 and return the decoded JSON (None when there is no body)."""
    key = os.environ.get("KEEPEMPOSTED_API_KEY")
    if not key:
        sys.exit("Set KEEPEMPOSTED_API_KEY to an API key (app.keepemposted.com, Settings, API keys).")
    url = BASE_URL + "/v1" + path
    if params:
        url += "?" + urllib.parse.urlencode({k: v for k, v in params.items() if v is not None})
    headers = {"Accept": "application/json", "Authorization": f"Bearer {key}",
               "User-Agent": "keepemposted-examples-python"}
    data = None
    if body is not None:
        data = json.dumps(body).encode("utf-8")
        headers["Content-Type"] = "application/json"
    if idempotency_key:
        headers["Idempotency-Key"] = idempotency_key
    req = urllib.request.Request(url, data=data, headers=headers, method=method)
    try:
        with urllib.request.urlopen(req, timeout=60) as response:
            raw = response.read()
            return json.loads(raw) if raw else None
    except urllib.error.HTTPError as error:
        raw = error.read().decode("utf-8", "replace")
        try:
            detail = json.loads(raw)["error"]
        except (ValueError, KeyError, TypeError):
            detail = {"code": f"http_{error.code}", "message": raw[:200]}
        raise ApiError(error.code, detail.get("code"), detail.get("message"), detail,
                       error.headers.get("Retry-After")) from None


def get(path, params=None):
    return request("GET", path, params=params)


def post(path, body=None, idempotency_key=None):
    return request("POST", path, body=body, idempotency_key=idempotency_key)


def new_idempotency_key():
    """A fresh key for one new post. If the request times out, send it again with the
    same key: you get the post that was already made, never a second one."""
    return str(uuid.uuid4())


def print_created(answer):
    """What happened to a new post on each account."""
    refused = {item["delivery_id"]: item for item in answer.get("not_scheduled") or []}
    print(f"Post {answer['post']['id']}")
    for item in answer["result"]:
        print(f"  {item['account']}: {item['standing']}")
        if item["delivery_id"] in refused:
            print(f"    Not scheduled: {refused[item['delivery_id']]['message']}")


def run(main):
    """Run an example, and say what the API refused in its own words."""
    try:
        main()
    except ApiError as error:
        lines = [f"{error.code}: {error.message}"]
        lines += [f"  {issue['message']}" for issue in error.error.get("issues") or []]
        sys.exit("\n".join(lines))
