{* GOODMi: вкладки страниц модуля Авито (2026-10-19) *}
<ul class="nav nav-tabs">
    <li class="{if $runtime.mode == "manage"}active{/if}"><a href="{"gm_avito.manage"|fn_url}">Настройки</a></li>
    <li class="{if $runtime.mode == "problems"}active{/if}"><a href="{"gm_avito.problems"|fn_url}">Проблемы и отчёт Авито</a></li>
    <li class="{if $runtime.mode == "templates"}active{/if}"><a href="{"gm_avito.templates"|fn_url}">Шаблоны категорий</a></li>
</ul>
