{* GOODMi: пункты «Мои заказы» и «Мои бонусы» в меню аккаунта – ведут на страницы кабинета (gm_cabinet.orders, gm_cabinet.bonuses).
   Только витрина GOODMi. Вошедшему показываются оба пункта, а штатные пункты заказов и бонусных баллов скрыты стилями
   (секции 45 и 46). Гостю добавляется только «Мои бонусы»: по нажатию магазин отправит его на страницу входа. *}
{if $runtime.company_id == 2}
{$dropdown_box_item_class = ($block.wrapper === "blocks/wrappers/onclick_dropdown.tpl") ? "ty-dropdown-box__item" : ""}
{if $auth.user_id}
<li class="ty-account-info__item {$dropdown_box_item_class} gm-acc-orders"><a class="ty-account-info__a" href="{"gm_cabinet.orders"|fn_url}" rel="nofollow">Мои заказы</a></li>
{/if}
<li class="ty-account-info__item {$dropdown_box_item_class} gm-acc-bonus"><a class="ty-account-info__a" href="{"gm_cabinet.bonuses"|fn_url}" rel="nofollow">Мои бонусы{if $auth.user_id && $user_info.points > 0}<span class="ty-reward-points-count">{$user_info.points}</span>{/if}</a></li>
{/if}
