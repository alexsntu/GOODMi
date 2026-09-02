"""Шаг 2: выполняет списание по плану, сохранённому deduct_preview.py.

Использование:
    python deduct_execute.py lists/deduct_2026-09-02_plan.json

Списывает actual_deduct у каждого клиента (PATCH .../balance, amount<0).
Клиентов с actual_deduct == 0 пропускает (списывать нечего).
Требует, чтобы пользователь уже подтвердил план (см. SKILL.md — превью
показывается пользователю перед этим шагом, без подтверждения не запускать).
"""
import json
import sys
import urllib.error
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parent))
from bp_lib import api_patch  # noqa: E402


def main():
    if len(sys.argv) != 2:
        raise SystemExit("Использование: python deduct_execute.py <файл>_plan.json")
    plan_path = Path(sys.argv[1])
    rows = json.loads(plan_path.read_text(encoding="utf-8"))

    done, skipped, errors = [], [], []
    for i, r in enumerate(rows, 1):
        if r["actual_deduct"] <= 0:
            skipped.append(r["phone"])
            continue
        try:
            api_patch(f"/customer/{r['phone']}/balance", {"amount": -r["actual_deduct"]})
            done.append(r["phone"])
            print(f"OK  {r['phone']}  -{r['actual_deduct']}")
        except urllib.error.HTTPError as e:
            body = e.read().decode()[:300]
            errors.append((r["phone"], e.code, body))
            print(f"ERR {r['phone']}  HTTP {e.code}  {body}")
        if i % 20 == 0:
            print(f"...{i}/{len(rows)}", file=sys.stderr)

    print()
    print(f"Списано: {len(done)}. Пропущено (баланс уже 0): {len(skipped)}. Ошибок: {len(errors)}")
    for phone, code, body in errors:
        print(f"  {phone}: HTTP {code} {body}")


if __name__ == "__main__":
    main()
