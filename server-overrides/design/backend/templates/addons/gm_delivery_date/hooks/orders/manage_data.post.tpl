{* GOODMi: список заказов в админке – подтверждён ли номер телефона из заказа в личном кабинете GOODMi
   (fn_gm_delivery_date_phone_mark). Только заказы витрины GOODMi. *}
<td class="gm-mark-cell" width="8%" data-th="Номер">{if $o.company_id == 2 && $o.phone}{$gm_mark = $o.phone|fn_gm_delivery_date_phone_mark}{if $gm_mark == "Y"}<span class="label label-success" title="Номер телефона подтверждён в личном кабинете">подтверждён</span>{elseif $gm_mark == "N"}<span class="label" title="Номер телефона не подтверждён">не подтв.</span>{/if}{/if}</td>
