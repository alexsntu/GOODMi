{* GOODMi: сроки получения товара – скрипт подключается только на витрине GOODMi (company_id = 2) *}
{if $runtime.company_id == 2}
    {script src="js/addons/gm_delivery_date/func.js"}
{/if}
