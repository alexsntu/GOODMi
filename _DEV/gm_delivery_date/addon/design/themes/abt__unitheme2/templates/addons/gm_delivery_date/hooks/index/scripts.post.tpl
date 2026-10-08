{* GOODMi: сроки получения товара, компактная кнопка чата Jivo на телефоне и заголовок «О товаре» в карточке – скрипты подключаются только на витрине GOODMi (company_id = 2) *}
{if $runtime.company_id == 2}
    {script src="js/addons/gm_delivery_date/func.js"}
    {script src="js/addons/gm_delivery_date/jivo.js"}
    {script src="js/addons/gm_delivery_date/about.js"}
{/if}
