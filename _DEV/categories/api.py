# -*- coding: utf-8 -*-
# Небольшая обёртка над API витрины GOODMi (ключи – в cscart-mcp-server/.env, на экран не выводятся).
import re, json, base64, urllib.request, urllib.error, os
ENV = {}
for l in open(os.path.join(os.path.dirname(os.path.abspath(__file__)), '..', '..', 'cscart-mcp-server', '.env'), encoding='utf-8'):
    m = re.match(r'^\s*([A-Z_]+)\s*=\s*(.*?)\s*$', l)
    if m: ENV[m.group(1)] = m.group(2).strip('"\'')
URL = ENV['GOODMI_API_URL'].rstrip('/')
AUTH = base64.b64encode((ENV['GOODMI_API_EMAIL'] + ':' + ENV['GOODMI_API_KEY']).encode()).decode()
def call(method, path, data=None):
    body = json.dumps(data).encode() if data is not None else None
    req = urllib.request.Request(URL + path, data=body, method=method, headers={'Authorization': 'Basic ' + AUTH, 'Content-Type': 'application/json'})
    try:
        r = urllib.request.urlopen(req, timeout=60)
        txt = r.read().decode()
        return r.status, (json.loads(txt) if txt.strip() else {})
    except urllib.error.HTTPError as e:
        return e.code, e.read().decode()[:300]
