import json
import glob
import os

out = []
files = sorted(glob.glob(r"writable/debugbar/debugbar_*.json"), key=os.path.getmtime)[-40:]
for f in files:
    j = json.load(open(f, encoding="utf-8"))
    url = j.get("url", "")
    if "auth/" not in url:
        continue
    auth = None
    for c in j.get("collectors", []):
        title = c.get("title")
        data = c.get("data") or {}
        if title != "Request":
            continue
        h = data.get("headers") or data.get("Headers") or {}
        if isinstance(h, dict):
            for k, v in h.items():
                if "auth" in k.lower() or k.lower() == "http_authorization":
                    auth = v
        elif isinstance(h, list):
            for item in h:
                if isinstance(item, dict):
                    name = item.get("name") or item.get("key") or ""
                    if "auth" in name.lower():
                        auth = item.get("value")
        out.append(f"{os.path.basename(f)} {j.get('method')} {url}")
        keys = list(data.keys())[:25]
        out.append(f"  request_data_keys={keys}")
        out.append(f"  auth={(str(auth)[:80] if auth else None)}")
        out.append(f"  headers_type={type(h).__name__} sample={str(h)[:400]}")

# Also check me responses around login time for null
for f in files:
    j = json.load(open(f, encoding="utf-8"))
    url = j.get("url", "")
    if "auth/me" not in url:
        continue
    # look for response body in collectors
    for c in j.get("collectors", []):
        if c.get("title") in ("Response", "Views", "Vars"):
            s = json.dumps(c.get("data"), ensure_ascii=False)[:500]
            out.append(f"RESP {os.path.basename(f)} {c.get('title')}: {s}")

path = r"writable/auth_debug_extract.txt"
open(path, "w", encoding="utf-8").write("\n".join(out))
print("wrote", path, "lines", len(out))
