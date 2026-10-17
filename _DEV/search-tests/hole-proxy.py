# Тест 2026-10-17: посредник 127.0.0.1:9399 → Manticore 9306. Всё пропускает, но запрос со словом hangtest «проглатывает»:
# соединение открыто, ответа нет – как зависший Manticore 16.10. Режим silent – вообще не отвечает (даже приветствием).
import socket, sys, threading

mode = sys.argv[1] if len(sys.argv) > 1 else 'proxy'
keep = []

def pump(src, dst, watch):
    try:
        while True:
            data = src.recv(65536)
            if not data:
                break
            if watch and b'hangtest' in data:
                return  # молчим: дальше ничего не передаём, соединение держим открытым
            dst.sendall(data)
    except OSError:
        pass

def handle(c):
    keep.append(c)
    if mode == 'silent':
        return
    u = socket.create_connection(('127.0.0.1', 9306))
    keep.append(u)
    threading.Thread(target=pump, args=(u, c, False), daemon=True).start()
    threading.Thread(target=pump, args=(c, u, True), daemon=True).start()

s = socket.socket()
s.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
s.bind(('127.0.0.1', 9399))
s.listen(50)
while True:
    c, _ = s.accept()
    handle(c)
