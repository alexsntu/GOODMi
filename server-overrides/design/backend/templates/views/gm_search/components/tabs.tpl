{* GOODMi: свой поиск – разделы админки и выбор периода (общая шапка страниц gm_search.*) *}
<style>{literal}
.gm-st-tabs{display:flex;flex-wrap:wrap;gap:6px;margin:0 0 14px}
.gm-st-tabs a{padding:6px 12px;border:1px solid #d7dbe0;border-radius:16px;color:#333;text-decoration:none}
.gm-st-tabs a.on{border-color:#0071e3;background:#0071e3;color:#fff}
.gm-st-per{margin:0 0 16px}.gm-st-per a{margin-right:10px}.gm-st-per a.on{font-weight:bold;color:#000;text-decoration:none}
.gm-st-kpi{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:10px;margin:0 0 20px}
.gm-st-kpi div{padding:12px 14px;border:1px solid #e3e6ea;border-radius:10px;background:#fff}
.gm-st-kpi b{display:block;font-size:22px;line-height:1.2}.gm-st-kpi span{font-size:12px;color:#777}
.gm-st-kpi .bad b{color:#c0392b}.gm-st-kpi .good b{color:#1e8e3e}
.gm-st-cols{display:grid;grid-template-columns:repeat(auto-fit,minmax(380px,1fr));gap:20px}
.gm-st-note{color:#777;font-size:12px;margin:4px 0 12px}
.gm-st-f{white-space:nowrap;font-size:12px;color:#555}
{/literal}</style>
<div class="gm-st-tabs">
    {foreach $gm_tabs as $k => $t}
        <a href="{"gm_search.`$k`?days=`$gm_days`"|fn_url}" class="{if $gm_mode == $k}on{/if}">{$t}</a>
    {/foreach}
</div>
{if $gm_mode != "check" && $gm_mode != "index"}
<div class="gm-st-per">Период:
    {foreach $gm_periods as $d => $t}
        <a href="{"gm_search.`$gm_mode`?days=`$d`"|fn_url}" class="{if $gm_days == $d}on{/if}">{$t}</a>
    {/foreach}
</div>
{/if}
