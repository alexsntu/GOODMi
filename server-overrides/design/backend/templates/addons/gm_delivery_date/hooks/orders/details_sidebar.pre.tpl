{* GOODMi: заказ в админке – над данными покупателя в боковой колонке: телефон из заказа и отметка, подтверждён ли
   он в личном кабинете GOODMi (fn_gm_delivery_date_phone_mark). Только заказы витрины GOODMi. *}
{if $order_info.company_id == 2 && $order_info.phone}
    {$gm_mark = $order_info.phone|fn_gm_delivery_date_phone_mark}
    {if $gm_mark}
    <div class="sidebar-row">
        <h6>Телефон покупателя</h6>
        <p><bdi>{$order_info.phone}</bdi>
            {if $gm_mark == "Y"}<span class="label label-success">подтверждён</span>{else}<span class="label label-important">не подтверждён</span>{/if}
        </p>
        {if $gm_mark == "N"}<p class="muted">Подтвердить вручную: <a href="{"gm_cabinet.phones?q=`$order_info.phone|regex_replace:"/\D+/":""`"|fn_url}">«Подтверждение номера»</a></p>{/if}
    </div>
    {/if}
{/if}
