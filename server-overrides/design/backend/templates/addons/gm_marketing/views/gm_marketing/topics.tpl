{* GOODMi: «Маркетинг-портал → Темы статей» (2026-10-21) – темы блога от routine на одобрение; по одобренным вечером пишутся статьи *}
{capture name="mainbox"}
{include file="addons/gm_marketing/views/gm_marketing/components/nav.tpl"}
<div class="gm-mkt">
{include file="addons/gm_marketing/views/gm_marketing/components/review_filters.tpl" filters=["pending" => "Ожидают", "approved" => "Одобренные", "rejected" => "Отклонённые", "generated" => "Уже статьи", "all" => "Все"]}
{if !$gm_mkt_rows}<p class="muted">Тем нет – дождитесь утреннего запуска программы или запустите её вручную.</p>{/if}
{foreach from=$gm_mkt_rows item="t"}
    <div class="gm-mkt-card">
        <div class="gm-mkt-meta">
            {if $t.category}<span class="gm-mkt-tag">{$t.category}</span>{/if}
            <span>{$t.created_msk} МСК</span>
            {if $t.status == "generated"}<span class="gm-mkt-tag">статья написана</span>{/if}
            {if $t.source_url}<a href="{$t.source_url}" target="_blank" rel="noopener">источник</a>{/if}
        </div>
        <div class="gm-mkt-title">{$t.title|escape}</div>
        <div class="gm-mkt-text">{$t.pitch|escape}</div>
        {include file="addons/gm_marketing/views/gm_marketing/components/review_actions.tpl" row=$t type="topic"}
    </div>
{/foreach}
</div>
{/capture}
{include file="common/mainbox.tpl" title="Маркетинг-портал: темы статей" content=$smarty.capture.mainbox}
