{* GOODMi: вкладки разделов маркетинг-портала и общие стили страниц (2026-10-21). Счётчики – сколько ждёт решения *}
<ul class="nav nav-tabs gm-mkt-tabs">
    {foreach from=$gm_mkt_tabs key="tab" item="t"}
        <li class="{if $t.active}active{/if}"><a href="{"gm_marketing.`$tab`"|fn_url}">{$t.title}{if $t.count} <span class="gm-mkt-badge">{$t.count}</span>{/if}</a></li>
    {/foreach}
</ul>
{literal}
<style>
.gm-mkt { max-width: 1200px; }
.gm-mkt-badge { display: inline-block; min-width: 18px; padding: 0 6px; border-radius: 9px; background: #ff6900; color: #fff; font-size: 11px; line-height: 18px; text-align: center; }
.gm-mkt-filters { margin: 0 0 14px; }
.gm-mkt-filters a { display: inline-block; margin: 0 6px 6px 0; padding: 4px 12px; border: 1px solid #ddd; border-radius: 14px; color: #333; text-decoration: none; }
.gm-mkt-filters a.active { background: #333; border-color: #333; color: #fff; }
.gm-mkt-card { margin: 0 0 12px; padding: 12px 16px; border: 1px solid #e3e3e3; border-radius: 6px; background: #fff; }
.gm-mkt-meta { margin: 0 0 8px; color: #888; font-size: 12px; }
.gm-mkt-meta > * { margin-right: 10px; }
.gm-mkt-tag { display: inline-block; padding: 1px 8px; border-radius: 10px; background: #f0f0f0; color: #333; }
.gm-mkt-title { margin: 0 0 6px; font-weight: bold; font-size: 14px; }
.gm-mkt-text { margin: 0 0 10px; white-space: pre-wrap; word-wrap: break-word; font-size: 13px; line-height: 1.5; }
.gm-mkt-actions form { display: inline-block; margin: 0 6px 0 0; }
.gm-mkt-attention { display: flex; flex-wrap: wrap; gap: 12px; margin: 0 0 18px; }
.gm-mkt-attention a { flex: 1 1 200px; padding: 12px 16px; border: 1px solid #e3e3e3; border-radius: 6px; background: #fff; color: #333; text-decoration: none; }
.gm-mkt-attention a.hot { border-color: #ff6900; background: #fff6f0; }
.gm-mkt-attention strong { display: block; font-size: 24px; line-height: 1.2; }
.gm-mkt-attention a.hot strong { color: #e55a00; }
.gm-mkt-routines a { display: inline-block; margin: 0 8px 8px 0; padding: 6px 12px; border: 1px solid #ddd; border-radius: 6px; color: #333; text-decoration: none; }
.gm-mkt-routines small { display: block; color: #888; }
.gm-mkt-form input[type="text"], .gm-mkt-form textarea, .gm-mkt-form select { box-sizing: border-box; width: 100%; max-width: 760px; }
.gm-mkt-form .gm-mkt-row { display: flex; flex-wrap: wrap; gap: 12px; }
.gm-mkt-form .gm-mkt-row label { flex: 0 1 auto; }
.gm-mkt-form .gm-mkt-row input, .gm-mkt-form .gm-mkt-row select { width: auto; }
.gm-mkt-plan { border-left: 4px solid var(--pc-fg, #ccc); }
.gm-mkt-cal { width: 100%; table-layout: fixed; border-collapse: collapse; }
.gm-mkt-cal th { padding: 6px; background: #f7f7f7; text-align: left; }
.gm-mkt-cal td { height: 90px; padding: 4px; border: 1px solid #eee; vertical-align: top; }
.gm-mkt-cal .day { color: #888; font-size: 11px; }
.gm-mkt-cal .pill { display: block; margin: 2px 0; padding: 1px 6px; overflow: hidden; border-radius: 4px; background: var(--pc-bg, #eee); color: var(--pc-fg, #333); font-size: 11px; white-space: nowrap; text-overflow: ellipsis; text-decoration: none; }
.gm-mkt-c0 { --pc-bg: #FFF0E6; --pc-fg: #E55A00; } .gm-mkt-c1 { --pc-bg: #E8F0FE; --pc-fg: #2F6FED; }
.gm-mkt-c2 { --pc-bg: #E6F4EA; --pc-fg: #2E7D46; } .gm-mkt-c3 { --pc-bg: #F1E8FE; --pc-fg: #7C3AED; }
.gm-mkt-c4 { --pc-bg: #FCE8F1; --pc-fg: #D6336C; } .gm-mkt-c5 { --pc-bg: #E1F5F3; --pc-fg: #0E9488; }
.gm-mkt-c6 { --pc-bg: #FEF3E0; --pc-fg: #B7791F; } .gm-mkt-c7 { --pc-bg: #E9E9FB; --pc-fg: #4C51BF; }
.gm-mkt-c8 { --pc-bg: #F5E9DC; --pc-fg: #92400E; } .gm-mkt-c9 { --pc-bg: #E0F7FA; --pc-fg: #0891B2; }
.gm-mkt-c10 { --pc-bg: #F8E1FB; --pc-fg: #A21CAF; } .gm-mkt-c11 { --pc-bg: #E7EBF0; --pc-fg: #475569; }
</style>
{/literal}
