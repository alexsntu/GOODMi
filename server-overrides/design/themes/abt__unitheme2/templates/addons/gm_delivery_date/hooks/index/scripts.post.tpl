{* GOODMi: сроки получения товара, компактная кнопка чата Jivo на телефоне, заголовок «О товаре» и кнопка «Поделиться» в карточке – скрипты подключаются только на витрине GOODMi (company_id = 2) *}
{if $runtime.company_id == 2}
    {script src="js/addons/gm_delivery_date/func.js"}
    {script src="js/addons/gm_delivery_date/jivo.js"}
    {script src="js/addons/gm_delivery_date/about.js"}
    {script src="js/addons/gm_delivery_date/share.js"}
{/if}
