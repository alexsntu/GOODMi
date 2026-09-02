"""Общая библиотека для скриптов коррекции баланса БонусПлюс.

Читает BONUSPLUS_API_BASE / BONUSPLUS_API_KEY из bonusplus-integration/.env
(тот же файл, что использует прокси-сервис). Ключ в код не хардкодится.
"""
import base64
import json
import os
import urllib.error
import urllib.request
from pathlib import Path

ENV_PATH = Path(__file__).resolve().parent.parent / ".env"


def load_env():
    if not ENV_PATH.exists():
        raise SystemExit(f"Не найден {ENV_PATH} — скопируйте .env.example и впишите BONUSPLUS_API_KEY")
    env = {}
    for line in ENV_PATH.read_text(encoding="utf-8").splitlines():
        line = line.strip()
        if not line or line.startswith("#") or "=" not in line:
            continue
        key, _, value = line.partition("=")
        env[key.strip()] = value.strip()
    return env


_env = load_env()
API_BASE = _env.get("BONUSPLUS_API_BASE", "https://bonusplus.pro/api")
API_KEY = _env.get("BONUSPLUS_API_KEY", "")
if not API_KEY:
    raise SystemExit(f"BONUSPLUS_API_KEY пуст в {ENV_PATH}")
AUTH_HEADER = "ApiKey " + base64.b64encode(API_KEY.encode()).decode()


def api_get(path):
    req = urllib.request.Request(
        API_BASE + path,
        headers={"Authorization": AUTH_HEADER, "Content-Type": "application/json"},
    )
    with urllib.request.urlopen(req) as resp:
        return json.loads(resp.read().decode())


def api_patch(path, body):
    data = json.dumps(body).encode("utf-8")
    req = urllib.request.Request(
        API_BASE + path,
        data=data,
        method="PATCH",
        headers={"Authorization": AUTH_HEADER, "Content-Type": "application/json"},
    )
    with urllib.request.urlopen(req) as resp:
        return json.loads(resp.read().decode())


def normalize_phone(raw):
    digits = "".join(ch for ch in str(raw) if ch.isdigit())
    if len(digits) == 11 and digits.startswith("8"):
        return "7" + digits[1:]
    if len(digits) == 10:
        return "7" + digits
    return digits


def load_phone_amount_list(path):
    """Парсит файл 'телефон сумма' построчно, суммирует дубли телефонов.
    Возвращает (totals: dict[phone->float], dup_counts: dict[phone->int])."""
    totals = {}
    dup_counts = {}
    with open(path, encoding="utf-8") as f:
        for line in f:
            line = line.strip()
            if not line:
                continue
            phone, amount = line.split()
            phone = normalize_phone(phone)
            amount = float(amount)
            totals[phone] = totals.get(phone, 0) + amount
            dup_counts[phone] = dup_counts.get(phone, 0) + 1
    return totals, dup_counts
