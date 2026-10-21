{* GOODMi: «Маркетинг-портал → Идеи акций» (2026-10-21) – еженедельные идеи акций от routine на одобрение *}
{capture name="mainbox"}
{include file="addons/gm_marketing/views/gm_marketing/components/nav.tpl"}
<div class="gm-mkt">
{include file="addons/gm_marketing/views/gm_marketing/components/review_filters.tpl" filters=["pending" => "Ожидают", "approved" => "Одобренные", "rejected" => "Отклонённые", "all" => "Все"]}
{if !$gm_mkt_rows}<p class="muted">Идей нет – дождитесь еженедельного запуска программы или запустите её вручную.</p>{/if}
{foreach from=$gm_mkt_rows item="i"}
    <div class="gm-mkt-card">
        <div class="gm-mkt-meta">
            {if $i.category}<span class="gm-mkt-tag">{$i.category}</span>{/if}
            <span>{$i.created_msk} МСК</span>
        </div>
        <div class="gm-mkt-title">{$i.title|escape}</div>
        {if $i.mechanic}<div class="gm-mkt-text"><strong>Механика:</strong> {$i.mechanic|escape}</div>{/if}
        <div class="gm-mkt-text">{$i.rationale|escape}</div>
        {include file="addons/gm_marketing/views/gm_marketing/components/review_actions.tpl" row=$i type="promo_idea"}
    </div>
{/foreach}
</div>
{/capture}
{include file="common/mainbox.tpl" title="Маркетинг-портал: идеи акций" content=$smarty.capture.mainbox}
