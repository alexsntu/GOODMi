{* GOODMi: плашка «от N ₽/мес. в кредит на 12 мес.» справа от цены в карточке товара. Подключается из goodmi_card.tpl
   и goodmi_card_mobile.tpl, стили – секция 37 «Своего CSS». Платёж считается здесь же, без скрипта: аннуитет,
   75% годовых, 12 месяцев – множитель 0,12091722 (те же условия, что были в goodmi_kredit_3_col.tpl), с округлением
   вверх. Для товаров дешевле 2000 ₽ плашка не выводится. Изменились условия кредита – пересчитать множитель:
   r = ставка / 12; k = (1 + r)^12; множитель = r * k / (k - 1). *}
{if $product.price >= 2000}
    {$gm_credit_month = ($product.price * 0.12091722)|ceil}
    <a class="gm-credit" href="/credit/">
        <span class="gm-credit__sum">от {include file="common/price.tpl" value=$gm_credit_month}/мес.</span>
        <span class="gm-credit__note">в кредит на 12 мес.</span>
    </a>
{/if}
