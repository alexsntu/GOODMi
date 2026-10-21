{* GOODMi: кнопки «Одобрить» / «Отклонить» / «Вернуть на рассмотрение» *}
<div class="gm-mkt-actions">
    {if $row.status != "approved" && $row.status != "generated"}{include file="addons/gm_marketing/views/gm_marketing/components/status_btn.tpl" type=$type id=$row.id status="approved" label="Одобрить" class="btn-primary"}{/if}
    {if $row.status != "rejected" && $row.status != "generated"}{include file="addons/gm_marketing/views/gm_marketing/components/status_btn.tpl" type=$type id=$row.id status="rejected" label="Отклонить"}{/if}
    {if $row.status == "approved" || $row.status == "rejected"}{include file="addons/gm_marketing/views/gm_marketing/components/status_btn.tpl" type=$type id=$row.id status="pending" label="Вернуть на рассмотрение"}{/if}
</div>
