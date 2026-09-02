"""Шаг 3 (только если были предупреждения ВНИМАНИЕ в deduct_preview.py):
второй проход списания после того, как пользователь вручную активировал
ожидающие бонусы в админке БонусПлюс (см. SKILL.md — API-способа активации
не существует, только кнопка в личном кабинете БонусПлюс).

Использование:
    python deduct_followup.py lists/deduct_2026-09-02_plan.json           # превью
    python deduct_followup.py lists/deduct_2026-09-02_plan.json --execute # списать

Берёт из плана только клиентов с недосписанным остатком (requested_deduct -
actual_deduct > 0), заново запрашивает баланс (после активации он должен
подрасти) и списывает остаток — не больше свежего активного баланса.
"""
import json
import sys
import urllib.error
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parent))
from bp_lib import api_get, api_patch  # noqa: E402


def main():
    if len(sys.argv) < 2:
        raise SystemExit("Использование: python deduct_followup.py <файл>_plan.json [--execute]")
    plan_path = Path(sys.argv[1])
    execute = "--execute" in sys.argv[2:]
    rows = json.loads(plan_path.read_text(encoding="utf-8"))

    shortfalls = [r for r in rows if (r["requested_deduct"] - r["actual_deduct"]) > 0]
    if not shortfalls:
        print("В этом плане нет недосписанных остатков — второй проход не нужен.")
        return

    print(f"{'Телефон':<13}{'Недосписано':>12}{'Активно сейчас':>16}{'Ожидает':>10}{'Спишем':>9}")
    followup = []
    still_pending = []
    for i, r in enumerate(shortfalls, 1):
        shortfall = r["requested_deduct"] - r["actual_deduct"]
        c = api_get(f"/customer?phone={r['phone']}")
        active = c.get("availableBonuses", 0) or 0
        pending = c.get("notActiveBonuses", 0) or 0
        deduct_now = min(shortfall, active)
        followup.append({"phone": r["phone"], "deduct_now": deduct_now})
        flag = "  <- ЕЩЁ НЕ АКТИВИРОВАНО" if pending > 0 else ("  <- нечего активировать (недосписанное несгораемо)" if deduct_now < shortfall else "")
        if pending > 0:
            still_pending.append(r["phone"])
        print(f"{r['phone']:<13}{shortfall:>12.0f}{active:>16.2f}{pending:>10.2f}{deduct_now:>9.2f}{flag}")
        if i % 20 == 0:
            print(f"...{i}/{len(shortfalls)}", file=sys.stderr)

    if still_pending:
        print(f"\nЕщё не активированы ({len(still_pending)}) — активируйте в админке и запустите скрипт заново:")
        for p in still_pending:
            print(f"  {p}")

    if not execute:
        print("\nЭто превью. Для списания запустите с флагом --execute (после подтверждения пользователем).")
        return

    print("\n=== EXECUTE ===")
    done, errors = [], []
    for f in followup:
        if f["deduct_now"] <= 0:
            continue
        try:
            api_patch(f"/customer/{f['phone']}/balance", {"amount": -f["deduct_now"]})
            done.append(f["phone"])
            print(f"OK  {f['phone']}  -{f['deduct_now']}")
        except urllib.error.HTTPError as e:
            errors.append((f["phone"], e.code, e.read().decode()[:200]))
            print(f"ERR {f['phone']}  HTTP {e.code}")
    print(f"\nСписано у {len(done)} клиентов. Ошибок: {len(errors)}")


if __name__ == "__main__":
    main()
