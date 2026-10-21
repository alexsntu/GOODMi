{* GOODMi: журнал операций с бонусами (2026-10-21) – поиск по телефону, фильтр по партии *}
{capture name="mainbox"}
{include file="addons/gm_marketing/views/gm_marketing/components/nav.tpl"}
<div class="gm-mkt">
<form action="{""|fn_url}" method="get" class="form-inline" style="margin-bottom: 12px;">
    <input type="hidden" name="dispatch" value="gm_marketing.bonus_log" />
    <input type="text" name="phone" value="{$gm_mkt_phone|escape}" placeholder="Поиск по телефону" />
    <button type="submit" class="btn">Найти</button>
    {if $gm_mkt_phone || $gm_mkt_batch_id}<a class="btn btn-link" href="{"gm_marketing.bonus_log"|fn_url}">Сбросить</a>{/if}
</form>
{if !$gm_mkt_entries}<p class="muted">Записей не найдено.</p>{else}
<table class="table table-middle">
    <thead><tr><th>Когда (МСК)</th><th>Партия</th><th>Телефон</th><th>Действие</th><th>Сумма</th><th>Детали</th></tr></thead>
    <tbody>
    {foreach from=$gm_mkt_entries item="e"}
        <tr><td>{$e.created_msk}</td><td><a href="{"gm_marketing.bonus_batch?batch_id=`$e.batch_id`"|fn_url}">#{$e.batch_id}</a></td><td>{$e.phone|default:"&#8211;"}</td><td>{$e.action}</td><td>{$e.amount_r|default:"&#8211;"}</td><td>{$e.details|escape}</td></tr>
    {/foreach}
    </tbody>
</table>
{/if}
</div>
{/capture}
{include file="common/mainbox.tpl" title="Маркетинг-портал: журнал бонусов" content=$smarty.capture.mainbox}
