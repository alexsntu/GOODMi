"""Вызов инструмента Claude in Chrome напрямую через сокет моста: cb.py <tool> '<json args>'"""
import socket, struct, json, glob, sys

tool = sys.argv[1]
args = json.loads(sys.argv[2]) if len(sys.argv) > 2 else {}
p = glob.glob('/tmp/claude-mcp-browser-bridge-a0000/*.sock')[0]
s = socket.socket(socket.AF_UNIX)
s.settimeout(180)
s.connect(p)
m = json.dumps({"method": "execute_tool", "params": {"tool": tool, "args": args}}).encode()
s.sendall(struct.pack('<I', len(m)) + m)
hdr = b''
while len(hdr) < 4:
    hdr += s.recv(4 - len(hdr))
n = struct.unpack('<I', hdr)[0]
buf = b''
while len(buf) < n:
    buf += s.recv(n - len(buf))
res = json.loads(buf)
for c in res.get('result', {}).get('content', []):
    if c.get('type') == 'text' and not c['text'].startswith('<system-reminder>'):
        print(c['text'])
if 'error' in res:
    print('ERROR', res['error'])
