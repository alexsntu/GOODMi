{* GOODMi: страница «ничего не нашлось» своего поиска вместо стандартной заглушки (2026-10-21). Подменяет хук
   products:search_results_no_matching_found, только на своей витрине (company_id 2) и когда контроллер поиска
   (gm_delivery_date/controllers/frontend/products.pre.php) положил данные gm_empty (gm_search_empty в app/gm_search/lib.php).
   Пусто (чужая витрина, фильтры, сбой индекса) – подмена ничего не выводит, и тема показывает свою заглушку.
   Оформление – раздел 77 «Своего CSS» витрины (классы gm-ns*). *}
{if $runtime.company_id == 2 && $gm_empty}
{$e = $gm_empty}
<div class="gm-ns">
    <div class="gm-ns__head">
        <svg class="gm-ns__ico" viewBox="0 0 24 24" width="40" height="40" aria-hidden="true"><circle cx="10.5" cy="10.5" r="6.5" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M15.5 15.5 21 21" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M8.3 8.3l4.4 4.4M12.7 8.3l-4.4 4.4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
        <div>
            <div class="gm-ns__title">По запросу «{$e.q}» ничего не нашлось</div>
            <div class="gm-ns__tips">Проверьте, нет ли опечатки, или напишите проще – например, тип товара и бренд вместо полного названия модели.</div>
        </div>
    </div>
    {if $e.alts}
    <div class="gm-ns__block">
        <div class="gm-ns__h">Возможно, вы искали</div>
        <div class="gm-ns__chips">{foreach $e.alts as $a}{$u = $a.text|escape:url}<a class="gm-ns__chip gm-ns__chip--alt" href="{"products.search?search_performed=Y&q=`$u`"|fn_url}">{$a.text}<span>{$a.count}</span></a>{/foreach}</div>
    </div>
    {/if}
    {if $e.queries}
    <div class="gm-ns__block">
        <div class="gm-ns__h">Часто ищут</div>
        <div class="gm-ns__chips">{foreach $e.queries as $pq}{$u = $pq|escape:url}<a class="gm-ns__chip" href="{"products.search?search_performed=Y&q=`$u`"|fn_url}">{$pq}</a>{/foreach}</div>
    </div>
    {/if}
    {if $e.cats}
    <div class="gm-ns__block">
        <div class="gm-ns__h">Популярные разделы</div>
        <div class="gm-ns__cats">{foreach $e.cats as $c}<a class="gm-ns__cat" href="{$c.url}">{if $c.img}<img src="{$c.img}" alt="" loading="lazy">{else}<span class="gm-ns__noimg"></span>{/if}<span>{$c.name}</span></a>{/foreach}</div>
    </div>
    {/if}
    {if $e.products}
    <div class="gm-ns__block">
        <div class="gm-ns__h">Популярные товары</div>
        <div class="gm-ns__prods">{foreach $e.products as $p}<a class="gm-ns__prod" href="{$p.url}"><span class="gm-ns__pimg">{if $p.img}<img src="{$p.img}" alt="" loading="lazy">{/if}</span><span class="gm-ns__pname">{$p.name}</span><b class="gm-ns__price">{$p.price}</b></a>{/foreach}</div>
    </div>
    {/if}
    <div class="gm-ns__help">
        <div><b>Не нашли нужное?</b> Позвоните или напишите в чат – подберём замену или привезём под заказ.</div>
        <div class="gm-ns__help-btns"><a class="gm-ns__btn gm-ns__btn--main" href="tel:+78002501700">8 (800) 250-17-00</a><a class="gm-ns__btn" href="/contacts/" onclick="if (window.jivo_api) { jivo_api.open(); return false; }">Написать в чат</a></div>
    </div>
</div>
{/if}
