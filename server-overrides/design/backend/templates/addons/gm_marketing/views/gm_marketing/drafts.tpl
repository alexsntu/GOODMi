{* GOODMi: «Маркетинг-портал → Черновики» (2026-10-21) – требует внимания, сбор данных (routines и статистика), черновики постов *}
{capture name="mainbox"}
{include file="addons/gm_marketing/views/gm_marketing/components/nav.tpl"}
<div class="gm-mkt">
<h4>Требует внимания</h4>
<div class="gm-mkt-attention">
    <a class="{if $gm_mkt_counts.drafts}hot{/if}" href="{"gm_marketing.drafts?status=new"|fn_url}"><strong>{$gm_mkt_counts.drafts}</strong>новых черновиков</a>
    <a class="{if $gm_mkt_counts.topics}hot{/if}" href="{"gm_marketing.topics"|fn_url}"><strong>{$gm_mkt_counts.topics}</strong>тем блога на рассмотрении</a>
    <a class="{if $gm_mkt_counts.promo_ideas}hot{/if}" href="{"gm_marketing.promo_ideas"|fn_url}"><strong>{$gm_mkt_counts.promo_ideas}</strong>идей акций на рассмотрении</a>
    <a class="{if $gm_mkt_counts.reposts}hot{/if}" href="{"gm_marketing.reposts"|fn_url}"><strong>{$gm_mkt_counts.reposts}</strong>предложений постов на рассмотрении</a>
</div>

{include file="common/subheader.tpl" title="Сбор данных" target="#gm_mkt_collect"}
<div id="gm_mkt_collect" class="in collapse">
    <div class="gm-mkt-routines">
        {foreach from=$gm_mkt_routines item="r"}
            <a href="{$r.url}" target="_blank" rel="noopener">Запустить: {$r.name} &#8594;<small>{$r.cadence}</small></a>
        {/foreach}
    </div>
    <p class="muted">Кнопка открывает программу на claude.ai – там нажмите «Run now» (нужен вход в аккаунт владельца программ).</p>
    <table class="table table-middle" style="width: auto;">
        <thead><tr><th>Источник</th><th>Последний сбор (МСК)</th><th>Всего</th><th>Новых</th><th>Опубл.</th><th>Откл.</th></tr></thead>
        <tbody>
        {foreach from=$gm_mkt_source_stats item="s"}
            <tr><td><a href="{"gm_marketing.drafts?status=all&source=`$s.key`"|fn_url}">{$s.label}</a></td><td>{$s.last_at|default:"&#8211;"}</td><td>{$s.total}</td><td>{$s.new_count}</td><td>{$s.posted_count}</td><td>{$s.dismissed_count}</td></tr>
        {/foreach}
        </tbody>
    </table>
    <p>Темы блога: ожидают <strong>{$gm_mkt_topic_stats.pending}</strong> &#183; одобрено <strong>{$gm_mkt_topic_stats.approved}</strong> &#183; отклонено <strong>{$gm_mkt_topic_stats.rejected}</strong> &#183; готово статей <strong>{$gm_mkt_topic_stats.generated}</strong> &#8211; <a href="{"gm_marketing.topics"|fn_url}">открыть &#8594;</a></p>
</div>

{include file="common/subheader.tpl" title="Черновики" target="#gm_mkt_drafts"}
<div id="gm_mkt_drafts" class="in collapse">
<div class="gm-mkt-filters">
    {foreach from=["new" => "Новые", "posted" => "Опубликованные", "dismissed" => "Отклонённые", "all" => "Все"] key="st" item="label"}
        <a class="{if $gm_mkt_status == $st}active{/if}" href="{"gm_marketing.drafts?status=`$st`&source=`$gm_mkt_source`"|fn_url}">{$label}</a>
    {/foreach}
    {if $gm_mkt_source}<a href="{"gm_marketing.drafts?status=`$gm_mkt_status`"|fn_url}">&#215; {$gm_mkt_sources[$gm_mkt_source]}</a>{/if}
</div>
{if !$gm_mkt_drafts}
    <p class="muted">Черновиков нет – запустите сбор выше или дождитесь расписания.</p>
{/if}
{foreach from=$gm_mkt_drafts item="d"}
    <div class="gm-mkt-card" id="gm_mkt_draft_{$d.id}">
        <div class="gm-mkt-meta">
            <span class="gm-mkt-tag">{$gm_mkt_sources[$d.source]|default:$d.source}</span>
            {if $d.category}<span>{$d.category}</span>{/if}
            <span>{$d.created_msk} МСК</span>
            {if $d.source_url}<a href="{$d.source_url}" target="_blank" rel="noopener">открыть товар</a>{/if}
        </div>
        <div class="gm-mkt-text" id="gm_mkt_draft_text_{$d.id}">{$d.title|escape}

{$d.text|escape}</div>
        <div class="gm-mkt-actions">
            <button type="button" class="btn btn-small" onclick="gmMktCopy({$d.id}, this)">Скопировать</button>
            {if $d.status != "posted"}{include file="addons/gm_marketing/views/gm_marketing/components/status_btn.tpl" type="draft" id=$d.id status="posted" label="Опубликовано" class="btn-primary"}{/if}
            {if $d.status != "dismissed"}{include file="addons/gm_marketing/views/gm_marketing/components/status_btn.tpl" type="draft" id=$d.id status="dismissed" label="Отклонить"}{/if}
            {if $d.status != "new"}{include file="addons/gm_marketing/views/gm_marketing/components/status_btn.tpl" type="draft" id=$d.id status="new" label="Вернуть в новые"}{/if}
        </div>
    </div>
{/foreach}
</div>
</div>
{literal}
<script>
function gmMktCopy(id, btn) {
    var el = document.getElementById('gm_mkt_draft_text_' + id);
    navigator.clipboard.writeText(el.innerText).then(function () {
        var t = btn.textContent; btn.textContent = 'Скопировано'; setTimeout(function () { btn.textContent = t; }, 1500);
    });
}
</script>
{/literal}
{/capture}
{include file="common/mainbox.tpl" title="Маркетинг-портал: черновики" content=$smarty.capture.mainbox}
