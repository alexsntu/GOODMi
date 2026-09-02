"""Шаг 1: превью списания бонусов по списку 'телефон сумма'.

Использование:
    python deduct_preview.py lists/deduct_2026-09-02.txt

Для каждого телефона запрашивает текущий баланс в БонусПлюс и считает,
сколько реально спишется (не больше активного баланса, до 0).
Сохраняет план в <файл>_plan.json — он нужен для deduct_execute.py.
Печатает список клиентов, где активных бонусов не хватает и есть
"ожидающие" (notActiveBonuses) — с ними отдельный процесс, см. SKILL.md.
"""
import json
import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parent))
from bp_lib import api_get, load_phone_amount_list  # noqa: E402


def main():
    if len(sys.argv) != 2:
        raise SystemExit("Использование: python deduct_preview.py <файл со списком>")
    list_path = Path(sys.argv[1])
    totals, dup_counts = load_phone_amount_list(list_path)

    dups = {p: c for p, c in dup_counts.items() if c > 1}
    if dups:
        print("Телефоны, встретившиеся несколько раз (суммы сложены):")
        for p, c in dups.items():
            print(f"  {p}: {c} строк, сумма = {totals[p]}")
        print()

    print(f"{'Телефон':<13}{'Имя':<22}{'Запрошено':>10}{'Баланс':>10}{'Спишем':>10}{'Останется':>11}{'Ожидает':>10}")
    rows = []
    pending_warnings = []
    for i, (phone, deduct) in enumerate(totals.items(), 1):
        customer = api_get(f"/customer?phone={phone}")
        balance = customer.get("availableBonuses", 0) or 0
        pending = customer.get("notActiveBonuses", 0) or 0
        actual = min(deduct, balance)
        new_balance = balance - actual
        name = (customer.get("person") or {}).get("fn", "")
        rows.append({
            "phone": phone,
            "name": name,
            "requested_deduct": deduct,
            "current_balance": balance,
            "pending": pending,
            "actual_deduct": actual,
            "new_balance": new_balance,
        })
        capped = actual < deduct
        flag = "  <- НЕ ХВАТАЕТ" if capped else ""
        if capped and pending > 0:
            pending_warnings.append(phone)
        print(f"{phone:<13}{name[:21]:<22}{deduct:>10.0f}{balance:>10.2f}{actual:>10.2f}{new_balance:>11.2f}{pending:>10.2f}{flag}")
        if i % 20 == 0:
            print(f"...обработано {i}/{len(totals)}", file=sys.stderr)

    total_requested = sum(r["requested_deduct"] for r in rows)
    total_actual = sum(r["actual_deduct"] for r in rows)
    print()
    print(f"Итого запрошено: {total_requested}")
    print(f"Итого реально спишется в этом проходе: {total_actual}")
    if pending_warnings:
        print(f"\nВНИМАНИЕ: {len(pending_warnings)} клиентам не хватает активного баланса, НО есть ожидающие бонусы:")
        for p in pending_warnings:
            print(f"  {p}")
        print("Для них нужна ручная активация в админке БонусПлюс перед вторым проходом — см. SKILL.md.")

    plan_path = list_path.with_name(list_path.stem + "_plan.json")
    plan_path.write_text(json.dumps(rows, ensure_ascii=False, indent=2), encoding="utf-8")
    print(f"\nПлан сохранён: {plan_path}")


if __name__ == "__main__":
    main()
