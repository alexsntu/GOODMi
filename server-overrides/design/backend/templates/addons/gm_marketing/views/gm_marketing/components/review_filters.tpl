{* GOODMi: фильтр по статусу для страниц «на одобрение» (темы, идеи акций, репосты) *}
<div class="gm-mkt-filters">
    {foreach from=$filters key="st" item="label"}
        <a class="{if $gm_mkt_status == $st}active{/if}" href="{"gm_marketing.`$runtime.mode`?status=`$st`"|fn_url}">{$label}</a>
    {/foreach}
</div>
