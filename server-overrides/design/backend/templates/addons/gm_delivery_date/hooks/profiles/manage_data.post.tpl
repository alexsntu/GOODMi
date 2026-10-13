{* GOODMi: список покупателей в админке – подтверждён ли номер телефона покупателя (fn_gm_delivery_date_phone_mark).
   Только покупатели витрины GOODMi. *}
<td width="8%" class="row-status gm-mark-cell" data-th="Номер">{if $user.company_id == 2 && $user.user_type == "C" && $user.phone}{$gm_mark = $user.phone|fn_gm_delivery_date_phone_mark:$user.user_id}{if $gm_mark == "Y"}<span class="label label-success" title="Номер телефона подтверждён">подтверждён</span>{elseif $gm_mark == "N"}<span class="label" title="Номер телефона не подтверждён">не подтв.</span>{/if}{/if}</td>
