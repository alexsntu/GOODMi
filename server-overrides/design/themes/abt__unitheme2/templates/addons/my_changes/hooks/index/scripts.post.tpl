{* Maxmobiles (company_id = 1): ссылки на сравнение открывают страницу, а не окно списков (compare_notice.js); на оформлении заказа нажатие на город открывает окно выбора города (checkout_city.js); вход и регистрация по номеру телефона – заглушка почты @maxmobiles.ru (auth.js) *}
{if $runtime.company_id == 1}
    {script src="js/addons/my_changes/compare_notice.js"}
    {script src="js/addons/my_changes/checkout_city.js"}
    {* Подтверждённый номер на оформлении заказа – только для чтения (флаг ставит checkout.pre.php модуля my_changes) *}
    {if $gm_phone_locked && $runtime.controller == "checkout"}<script>Tygh.gm_phone_locked = true;</script>{/if}
    {script src="js/addons/my_changes/auth.js"}
    {* Свой поиск, этап 2: подсказки только в браузере с кукой mm_search_test (вместо Diginetica, см. common/scripts.tpl) *}
    {if isset($smarty.cookies.mm_search_test) && $smarty.cookies.mm_search_test == "1"}<link rel="stylesheet" href="/mm-search.php?asset=css&amp;v={$smarty.const.TIME}"><script src="/mm-search.php?asset=js&amp;v={$smarty.const.TIME}"></script>{/if}
{/if}
