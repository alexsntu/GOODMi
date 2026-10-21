{* GOODMi: «Маркетинг-портал → План» (2026-10-21) – маркетинговый календарь; routine «Идеи акций» читает ближайшие 4 недели плана *}
{capture name="mainbox"}
{include file="addons/gm_marketing/views/gm_marketing/components/nav.tpl"}
<div class="gm-mkt">
{include file="common/subheader.tpl" title="Новое мероприятие" target="#gm_mkt_plan_add"}
<div id="gm_mkt_plan_add" class="collapse {if !$gm_mkt_plan}in{/if}">
<form action="{""|fn_url}" method="post" class="gm-mkt-form">
    {include file="addons/gm_marketing/views/gm_marketing/components/plan_form.tpl" item=[]}
    <p><button type="submit" class="btn btn-primary" name="dispatch[gm_marketing.add_plan]">Добавить в план</button></p>
</form>
</div>

{include file="common/subheader.tpl" title="Маркетинговый план" target="#gm_mkt_plan_list"}
<div id="gm_mkt_plan_list" class="in collapse">
<div class="gm-mkt-filters">
    <a class="{if $gm_mkt_view == "list"}active{/if}" href="{"gm_marketing.plan?view=list&status=`$gm_mkt_status`"|fn_url}">Список</a>
    <a class="{if $gm_mkt_view == "calendar"}active{/if}" href="{"gm_marketing.plan?view=calendar&status=`$gm_mkt_status`&month=`$gm_mkt_month`"|fn_url}">Календарь</a>
</div>
<div class="gm-mkt-filters">
    {foreach from=$gm_mkt_plan_statuses key="st" item="label"}
        <a class="{if $gm_mkt_status == $st}active{/if}" href="{"gm_marketing.plan?view=`$gm_mkt_view`&status=`$st`&month=`$gm_mkt_month`"|fn_url}">{$label}</a>
    {/foreach}
    <a class="{if $gm_mkt_status == "all"}active{/if}" href="{"gm_marketing.plan?view=`$gm_mkt_view`&status=all&month=`$gm_mkt_month`"|fn_url}">Все</a>
</div>

{if $gm_mkt_view == "calendar"}
    <p>
        <a class="btn btn-small" href="{"gm_marketing.plan?view=calendar&status=`$gm_mkt_status`&month=`$gm_mkt_prev_month`"|fn_url}">&#8592; Пред.</a>
        <strong style="margin: 0 12px;">{$gm_mkt_month_title}</strong>
        <a class="btn btn-small" href="{"gm_marketing.plan?view=calendar&status=`$gm_mkt_status`&month=`$gm_mkt_next_month`"|fn_url}">След. &#8594;</a>
    </p>
    <table class="gm-mkt-cal">
        <thead><tr><th>Пн</th><th>Вт</th><th>Ср</th><th>Чт</th><th>Пт</th><th>Сб</th><th>Вс</th></tr></thead>
        <tbody>
        {foreach from=$gm_mkt_weeks item="week"}
            <tr>
            {foreach from=$week item="cell"}
                {if !$cell}<td style="background: #fafafa;"></td>{else}
                <td><div class="day">{$cell.day}</div>
                    {foreach from=$cell.items item="it"}<a class="pill gm-mkt-c{$it.color}" href="{"gm_marketing.plan_edit?id=`$it.id`"|fn_url}" title="{$it.title|escape}">{$it.title|escape}</a>{/foreach}
                </td>{/if}
            {/foreach}
            </tr>
        {/foreach}
        </tbody>
    </table>
{else}
    {if !$gm_mkt_plan}<p class="muted">В плане пусто – добавьте мероприятие выше.</p>{/if}
    {foreach from=$gm_mkt_plan item="i"}
        <div class="gm-mkt-card gm-mkt-plan gm-mkt-c{$i.color}">
            <div class="gm-mkt-meta">
                {if $i.type}<span class="gm-mkt-tag">{$gm_mkt_plan_types[$i.type]|default:$i.type}</span>{/if}
                {if $i.start_date}<span>{$i.start_date}{if $i.end_date && $i.end_date != $i.start_date} &#8211; {$i.end_date}{/if}</span>{/if}
                {if $i.channels}<span>{$i.channels}</span>{/if}
                <span>{$gm_mkt_plan_statuses[$i.status]|default:$i.status}</span>
            </div>
            <div class="gm-mkt-title">{$i.title|escape}</div>
            {if $i.description}<div class="gm-mkt-text">{$i.description|escape}</div>{/if}
            <div class="gm-mkt-actions">
                <a class="btn btn-small" href="{"gm_marketing.plan_edit?id=`$i.id`"|fn_url}">Редактировать</a>
                {foreach from=$gm_mkt_plan_statuses key="st" item="label"}
                    {if $st != $i.status}{include file="addons/gm_marketing/views/gm_marketing/components/status_btn.tpl" type="plan" id=$i.id status=$st label=$label}{/if}
                {/foreach}
                <form action="{""|fn_url}" method="post">
                    <input type="hidden" name="id" value="{$i.id}" />
                    <input type="hidden" name="redirect_url" value="{$config.current_url}" />
                    <button type="submit" class="btn btn-small cm-confirm" name="dispatch[gm_marketing.delete_plan]">Удалить</button>
                </form>
            </div>
        </div>
    {/foreach}
{/if}
</div>
</div>
{/capture}
{include file="common/mainbox.tpl" title="Маркетинг-портал: план" content=$smarty.capture.mainbox}
