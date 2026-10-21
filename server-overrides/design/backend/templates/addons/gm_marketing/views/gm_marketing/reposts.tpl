{* GOODMi: «Маркетинг-портал → Предложение постов» (2026-10-21) – кандидаты на репост из Telegram-каналов, пересказ и ссылка *}
{capture name="mainbox"}
{include file="addons/gm_marketing/views/gm_marketing/components/nav.tpl"}
<div class="gm-mkt">
{include file="addons/gm_marketing/views/gm_marketing/components/review_filters.tpl" filters=["pending" => "Ожидают", "approved" => "Одобренные", "rejected" => "Отклонённые", "all" => "Все"]}
{if !$gm_mkt_rows}<p class="muted">Предложений нет – программа проверяет каналы по будням каждые 2 часа с 10:30 до 16:30 МСК.</p>{/if}
{foreach from=$gm_mkt_rows item="r"}
    <div class="gm-mkt-card">
        <div class="gm-mkt-meta">
            {if $r.source_channel}<span class="gm-mkt-tag">{$r.source_channel}</span>{/if}
            <span>{$r.created_msk} МСК</span>
            {if $r.source_url}<a href="{$r.source_url}" target="_blank" rel="noopener">оригинал</a>{/if}
        </div>
        <div class="gm-mkt-title">{$r.title|escape}</div>
        <div class="gm-mkt-text">{$r.summary|escape}</div>
        {include file="addons/gm_marketing/views/gm_marketing/components/review_actions.tpl" row=$r type="repost"}
    </div>
{/foreach}
</div>
{/capture}
{include file="common/mainbox.tpl" title="Маркетинг-портал: предложение постов" content=$smarty.capture.mainbox}
