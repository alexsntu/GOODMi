{* GOODMi: левая колонка личного кабинета – имя покупателя и разделы. Данные: $gm_cab.nav (fn_gm_cab_nav). *}
<aside class="gm-lk__side">
    <div class="gm-lk-user">
        <div class="gm-lk-user__ava">{$gm_cab.nav.initial}</div>
        <div class="gm-lk-user__info">
            <div class="gm-lk-user__name">{$gm_cab.nav.name}</div>
            <a class="gm-lk-user__edit" href="{$gm_cab.nav.profile_url}">Изменить профиль</a>
        </div>
    </div>
    <nav class="gm-lk-nav">
        {foreach from=$gm_cab.nav.items item="item"}
            <a class="gm-lk-nav__a{if $item.active} gm-lk-nav__a--on{/if}" href="{$item.url}">{$item.title}</a>
        {/foreach}
        <a class="gm-lk-nav__a gm-lk-nav__a--out" href="{$gm_cab.nav.logout_url}" rel="nofollow">Выйти</a>
    </nav>
</aside>
