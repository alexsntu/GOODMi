{* GOODMi: витрина и вкладки страниц модуля Авито (2026-10-19). У каждой витрины свои настройки, шаблоны, файл,
   аккаунт и расписание Авито; витрина переключается здесь же (switch_company_id) *}
<div class="btn-group" style="margin-bottom: 10px;">
    {foreach from=$gm_avito_companies key="cid" item="cname"}
        <a class="btn {if $cid == $gm_avito_company_id}btn-primary{/if}" href="{"gm_avito.`$runtime.mode`?switch_company_id=`$cid`"|fn_url}">{$cname}</a>
    {/foreach}
</div>
{if $gm_avito_allowed}<span class="muted" style="margin-left: 10px;">Открыты настройки витрины {$gm_avito_companies[$gm_avito_company_id]} &#8211; у каждой витрины свой аккаунт Авито, файл, шаблоны и расписание.</span>{/if}
<ul class="nav nav-tabs">
    <li class="{if $runtime.mode == "manage"}active{/if}"><a href="{"gm_avito.manage"|fn_url}">Настройки</a></li>
    <li class="{if $runtime.mode == "problems"}active{/if}"><a href="{"gm_avito.problems"|fn_url}">Проблемы и отчёт Авито</a></li>
    <li class="{if $runtime.mode == "templates"}active{/if}"><a href="{"gm_avito.templates"|fn_url}">Шаблоны категорий</a></li>
    <li class="{if $runtime.mode == "help"}active{/if}"><a href="{"gm_avito.help"|fn_url}">Инструкция</a></li>
</ul>
