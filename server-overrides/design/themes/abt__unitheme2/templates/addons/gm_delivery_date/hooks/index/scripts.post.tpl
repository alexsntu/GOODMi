{* GOODMi: сроки получения товара, компактная кнопка чата Jivo на телефоне, заголовок «О товаре» кнопка «Поделиться» в карточке, вход по номеру телефона (auth.js), кнопка «Ещё» в строке быстрых ссылок категории (seomenu.js), раскрытие моделей под подразделом в мобильном меню (flymenu.js), кнопка «Список сравнения» в уведомлении о добавлении в сравнение (compare_notice.js) – скрипты подключаются только на витрине GOODMi (company_id = 2) *}
{if $runtime.company_id == 2}
    {script src="js/addons/gm_delivery_date/func.js"}
    {script src="js/addons/gm_delivery_date/jivo.js"}
    {script src="js/addons/gm_delivery_date/about.js"}
    {script src="js/addons/gm_delivery_date/share.js"}
    {* Подтверждённый номер на оформлении заказа – только для чтения (флаг ставит checkout.pre.php модуля gm_delivery_date) *}
    {if $gm_phone_locked && $runtime.controller == "checkout"}<script>Tygh.gm_phone_locked = true;</script>{/if}
    {script src="js/addons/gm_delivery_date/auth.js"}
    {script src="js/addons/gm_delivery_date/seomenu.js"}
    {script src="js/addons/gm_delivery_date/flymenu.js"}
    {script src="js/addons/gm_delivery_date/compare_notice.js"}
    {* Свой поиск, этап 2 (2026-10-16): только в браузере с тестовой отметкой gm_search_test – подсказки вместо Diginetica *}
    {if isset($smarty.cookies.gm_search_test)}<script src="/gm-search.php?asset=js" defer></script>{/if}
{/if}
{* Отложенный запрос сопутствующих товаров в карточке (ia_defer.js) – на витринах Maxmobiles (1) и GOODMi (2), 2026-10-14 *}
{if $runtime.company_id == 1 || $runtime.company_id == 2}
    {script src="js/addons/gm_delivery_date/ia_defer.js"}
{/if}
