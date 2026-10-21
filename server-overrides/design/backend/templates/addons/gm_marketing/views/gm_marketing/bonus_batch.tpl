{* GOODMi: партия списания бонусов (2026-10-21): превью → «Списать по плану» → (если нужно) ручная активация в БонусПлюс → доудержание *}
{capture name="mainbox"}
{include file="addons/gm_marketing/views/gm_marketing/components/nav.tpl"}
{$bb = $gm_mkt_bb}
<div class="gm-mkt">
<h4>Партия #{$bb.batch.id} &#8211; {$bb.batch.created_at_msk} МСК &#8211; {if $bb.batch.status == "preview"}превью, не списано{elseif $bb.batch.status == "executed"}ждём активации части клиентов{else}завершено{/if}</h4>

{if $bb.dups}
    <div class="alert">Во входном списке были повторяющиеся телефоны – суммы сложены в одно списание:
        {foreach from=$bb.dups item="it"}<div>{$it.phone} ({$it.name|default:"без имени"}): {$it.dup_count} строк &#8594; {$it.requested_r}</div>{/foreach}
    </div>
{/if}

<p>Запрошено списать: <strong>{$bb.totals.requested_r}</strong> &#183; Реально списано: <strong>{$bb.totals.deducted_r}</strong> &#183; Осталось недосписано: <strong>{$bb.totals.remaining_r}</strong></p>

{if $bb.batch.status == "preview"}
<form action="{""|fn_url}" method="post">
    <input type="hidden" name="batch_id" value="{$bb.batch.id}" />
    <button type="submit" class="btn btn-primary cm-confirm" data-ca-confirm-text="Списать по этому превью? Это правки по живым клиентским счетам." name="dispatch[gm_marketing.bonus_execute]">Списать по плану</button>
</form>
{/if}

{if $bb.partial || $bb.zero}
    {include file="common/subheader.tpl" title="Нужна ручная активация в БонусПлюс" target="#gm_mkt_bb_act"}
    <div id="gm_mkt_bb_act" class="in collapse">
    <p>У этих клиентов не хватает активного баланса, но есть «ожидающие» бонусы – API их активировать не умеет, только вручную: <strong>bonusplus.pro/lk_new/ &#8594; Клиенты &#8594; карточка клиента &#8594; блок «Бонусы» &#8594; клик по числу под «Неактивные» &#8594; «Да»</strong>. Активируйте всех ниже, затем нажмите кнопку под списками.</p>
    {if $bb.partial}
        <p><strong>Список 1 – уже частично списали, остался хвост ({$bb.partial|count})</strong></p>
        <table class="table table-middle" style="width: auto;"><thead><tr><th>Телефон</th><th>Имя</th><th>Недосписано</th><th>Ожидает (активируйте)</th></tr></thead><tbody>
        {foreach from=$bb.partial item="it"}<tr><td>{$it.phone}</td><td>{$it.name|default:"&#8211;"}</td><td>{$it.shortfall_r}</td><td>{$it.pending_r}</td></tr>{/foreach}
        </tbody></table>
    {/if}
    {if $bb.zero}
        <p><strong>Список 2 – баланс был 0, ничего не списывали ({$bb.zero|count})</strong></p>
        <table class="table table-middle" style="width: auto;"><thead><tr><th>Телефон</th><th>Имя</th><th>Нужно списать</th><th>Ожидает (активируйте)</th></tr></thead><tbody>
        {foreach from=$bb.zero item="it"}<tr><td>{$it.phone}</td><td>{$it.name|default:"&#8211;"}</td><td>{$it.shortfall_r}</td><td>{$it.pending_r}</td></tr>{/foreach}
        </tbody></table>
    {/if}
    <form action="{""|fn_url}" method="post">
        <input type="hidden" name="batch_id" value="{$bb.batch.id}" />
        <button type="submit" class="btn btn-primary cm-confirm" data-ca-confirm-text="Подтвердите, что ожидающие бонусы этим клиентам уже активированы в кабинете БонусПлюс." name="dispatch[gm_marketing.bonus_followup]">Бонусы активированы – списать по списку</button>
    </form>
    </div>
{/if}

{if $bb.uncollectible}
    {include file="common/subheader.tpl" title="Недосписано, активировать нечего" target="#gm_mkt_bb_unc"}
    <div id="gm_mkt_bb_unc" class="in collapse">
    <p class="muted">У этих клиентов нет ни активного, ни ожидающего баланса – остаток можно доудержать только при новых начислениях.</p>
    <table class="table table-middle" style="width: auto;"><thead><tr><th>Телефон</th><th>Имя</th><th>Недосписано</th></tr></thead><tbody>
    {foreach from=$bb.uncollectible item="it"}<tr><td>{$it.phone}</td><td>{$it.name|default:"&#8211;"}</td><td>{$it.shortfall_r}</td></tr>{/foreach}
    </tbody></table>
    </div>
{/if}

{include file="common/subheader.tpl" title="Все клиенты партии" target="#gm_mkt_bb_all"}
<div id="gm_mkt_bb_all" class="in collapse">
<table class="table table-middle">
    <thead><tr><th>Телефон</th><th>Имя</th><th>Запрошено</th><th>Баланс на момент превью</th><th>Списано</th><th>Осталось недосписано</th><th>Статус</th></tr></thead>
    <tbody>
    {foreach from=$bb.items item="it"}
        <tr>
            <td>{$it.phone}</td><td>{$it.name|default:"&#8211;"}</td><td>{$it.requested_r}</td><td>{$it.balance_r}</td>
            <td>{$it.deducted_r}</td><td>{if $it.shortfall > 0}{$it.shortfall_r}{else}0{/if}</td>
            <td>{if $bb.batch.status == "preview"}не списано (превью){elseif $it.error}ошибка: {$it.error|escape}{elseif $it.still_pending}ждём активации{elseif $it.shortfall > 0.5}недосписано, активации нет{else}списано полностью{/if}</td>
        </tr>
    {/foreach}
    </tbody>
</table>
<p><a href="{"gm_marketing.bonus_log?batch_id=`$bb.batch.id`"|fn_url}">Журнал операций партии &#8594;</a></p>
</div>
</div>
{/capture}
{include file="common/mainbox.tpl" title="Маркетинг-портал: партия списания бонусов" content=$smarty.capture.mainbox}
