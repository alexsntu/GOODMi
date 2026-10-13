{* GOODMi: профиль покупателя в админке – первой строкой: подтверждён ли номер телефона (fn_gm_delivery_date_phone_mark).
   Только покупатели витрины GOODMi. *}
{if $id && $user_data.company_id == 2 && $user_data.user_type == "C"}
    {$gm_mark = $user_data.phone|fn_gm_delivery_date_phone_mark:$id}
    <div class="control-group">
        <label class="control-label">Номер телефона</label>
        <div class="controls">
            <p style="padding-top:5px">
                {if $gm_mark == "Y"}<bdi>{$user_data.phone}</bdi> <span class="label label-success">подтверждён</span>
                {elseif $gm_mark == "N"}<bdi>{$user_data.phone}</bdi> <span class="label label-important">не подтверждён</span>
                    &nbsp;<a href="{"gm_cabinet.phones?q=`$user_data.phone|regex_replace:"/\D+/":""`"|fn_url}">подтвердить вручную</a>
                {else}<span class="muted">номер не указан или записан неверно</span>{/if}
            </p>
        </div>
    </div>
{/if}
