{* GOODMi: «Товары → Авито: проблемы» (2026-10-19) – итог fn_gm_avito_check (после сборки файла и по кнопке) *}
{capture name="mainbox"}
{include file="addons/gm_avito/views/gm_avito/components/nav.tpl"}
{if !$gm_avito_allowed}
    <p>Выберите витрину GOODMi или Maxmobiles в переключателе витрин вверху админки.</p>
{else}
<form action="{""|fn_url}" method="post" name="gm_avito_problems_form">
    {$c = $gm_avito_check}
    {if !$c.time}
        <p>Проверки ещё не было &#8211; нажмите «Проверить сейчас».</p>
    {else}
    <p class="muted">Проверено {$c.time|date_format:"`$settings.Appearance.date_format`, `$settings.Appearance.time_format`"} (после каждой сборки файла). Товаров, включённых на сайте: {$c.products}.</p>

    <div class="table-responsive-wrapper">
    <table class="table table-middle">
        <tbody>
            <tr><td width="40%"><strong>Продаётся на Авито (включено у товара)</strong></td><td>{$c.enabled}</td></tr>
            <tr><td><strong>Попадает в файл</strong></td><td>{$c.in_file}</td></tr>
            {foreach from=$c.reasons key="reason" item="count"}
                <tr><td>&#8211; не попадает: {$reason}</td><td>{$count}</td></tr>
            {/foreach}
            <tr><td><strong>Заголовков длиннее 50 знаков</strong></td><td>{$c.long_titles|count}</td></tr>
            <tr><td><strong>Категорий с незаполненными полями Авито</strong></td><td>{$c.missing|count}</td></tr>
            <tr><td><strong>Категорий без шаблона</strong></td><td>{$c.no_template|count}</td></tr>
            <tr><td><strong>Отчёт Авито о последней загрузке</strong></td><td>{if $gm_avito_report.time}{$gm_avito_report.started_at|replace:"T":" "|replace:"Z":" UTC"} &#183; объявлений {$gm_avito_report.ads}{if $gm_avito_report.feed_label} &#183; файл: {$gm_avito_report.feed_label}{/if}<br /><span class="muted">получен {$gm_avito_report.time|date_format:"`$settings.Appearance.date_format`, `$settings.Appearance.time_format`"}</span>{else}<span class="muted">ещё не получали &#8211; кнопка «Обновить отчёт Авито»</span>{/if}</td></tr>
            {foreach from=$gm_avito_report.stats.sections item="s"}
                <tr><td>&#8211; {$s.title}</td><td>{$s.count}{if $s.sections} ({foreach from=$s.sections item="ss" name="ss"}{$ss.title|lower} {$ss.count}{if !$smarty.foreach.ss.last}, {/if}{/foreach}){/if}</td></tr>
            {/foreach}
        </tbody>
    </table>
    </div>

    {if $gm_avito_report_problems}
    {include file="common/subheader.tpl" title="Ошибки и предупреждения Авито (последняя загрузка)" target="#gm_avito_p_report"}
    <div id="gm_avito_p_report" class="in collapse">
        <table class="table table-middle"><thead><tr><th width="25%">Товар</th><th width="15%">Раздел отчёта</th><th>Что пишет Авито</th></tr></thead><tbody>
        {foreach from=$gm_avito_report_problems item="r"}
            <tr>
                <td>{if $r.product_id}<a href="{"products.update?product_id=`$r.product_id`&selected_section=gm_avito"|fn_url}">{$r.product}</a>{/if}{if $r.url}<br /><a href="{$r.url}" target="_blank" class="muted">на Авито</a>{/if}{if $r.enabled != "Y"} <span class="muted">(выключен)</span>{/if}</td>
                <td>{$r.section}{if $r.avito_status}<br /><span class="muted">{$r.avito_status}</span>{/if}</td>
                <td>{foreach from=$r.messages item="m"}<span class="{if $m.type == "error"}text-error{else}text-warning{/if}">{$m.title}</span> <span class="muted">{$m.text|truncate:220}</span><br />{foreachelse}<span class="muted">без сообщений</span>{/foreach}</td>
            </tr>
        {/foreach}
        </tbody></table>
    </div>
    {/if}

    {if $c.by_category}
    {include file="common/subheader.tpl" title="Товары по категориям: сколько продаётся на Авито" target="#gm_avito_p_bycat"}
    <div id="gm_avito_p_bycat" class="collapse">
        <p class="muted">Включённые на сайте товары витрины. «Не включено» – товары, у которых на вкладке «Авито» не отмечено «Продавать на Авито». Сначала категории, где больше всего товаров в наличии не выставлено.</p>
        <input type="text" class="input-xlarge gm-avito-filter" placeholder="Найти категорию" data-target="gm_avito_bycat_table" />
        <table class="table table-middle" id="gm_avito_bycat_table">
            <thead><tr><th>Категория</th><th>Товаров / в наличии</th><th>Продаётся на Авито</th><th>Не включено</th><th>Не включено, в наличии</th><th>Шаблон</th></tr></thead>
            <tbody>
            {foreach from=$c.by_category key="category_id" item="b"}
                <tr class="gm-avito-filter-item">
                    <td><a href="{"products.manage?cid=`$category_id`"|fn_url}">{$b.path}</a></td>
                    <td>{$b.total} / {$b.in_stock}</td>
                    <td>{$b.enabled}</td>
                    <td>{$b.off}</td>
                    <td>{if $b.off_in_stock}<strong>{$b.off_in_stock}</strong>{else}0{/if}</td>
                    <td>{if $b.template}есть{else}<span class="text-warning">нет</span>{/if}</td>
                </tr>
            {/foreach}
            </tbody>
        </table>
    </div>
    {/if}

    {if $c.enabled_problems}
    {include file="common/subheader.tpl" title="Включены, но не попадают в файл" target="#gm_avito_p_enabled"}
    <div id="gm_avito_p_enabled" class="in collapse">
        <table class="table table-middle"><thead><tr><th>Товар</th><th>Причина</th></tr></thead><tbody>
        {foreach from=$c.enabled_problems item="row"}
            <tr><td><a href="{"products.update?product_id=`$row.0`&selected_section=gm_avito"|fn_url}">{$row.1}</a></td><td>{$row.2}</td></tr>
        {/foreach}
        </tbody></table>
    </div>
    {/if}

    {if $c.missing}
    {include file="common/subheader.tpl" title="Не заполнены обязательные поля Авито" target="#gm_avito_p_missing"}
    <div id="gm_avito_p_missing" class="in collapse">
        <p class="muted">Товар можно включить, но в файл он не попадёт, пока поля не заполнены (вкладка «Авито» в карточке товара, «Поля объявления»). Примеры – товары в наличии.</p>
        <table class="table table-middle"><thead><tr><th width="30%">Категория</th><th width="10%">Товаров / в наличии</th><th width="20%">Не хватает</th><th>Примеры</th></tr></thead><tbody>
        {foreach from=$c.missing item="m"}
            <tr>
                <td>{$m.path}</td>
                <td>{$m.count} / {$m.in_stock}</td>
                <td>{foreach from=$m.fields key="tag" item="count" name="mf"}{$tag} ({$count}){if !$smarty.foreach.mf.last}, {/if}{/foreach}</td>
                <td>{foreach from=$m.examples item="row"}<a href="{"products.update?product_id=`$row.0`&selected_section=gm_avito"|fn_url}">{$row.1}</a> <span class="muted">&#8211; {$row.2}</span><br />{/foreach}</td>
            </tr>
        {/foreach}
        </tbody></table>
    </div>
    {/if}

    {if $c.long_titles}
    {include file="common/subheader.tpl" title="Заголовки длиннее 50 знаков" target="#gm_avito_p_titles"}
    <div id="gm_avito_p_titles" class="in collapse">
        <table class="table table-middle"><thead><tr><th>Товар</th><th>Заголовок на Авито</th><th>Знаков</th></tr></thead><tbody>
        {foreach from=$c.long_titles item="row"}
            <tr><td><a href="{"products.update?product_id=`$row.0`&selected_section=gm_avito"|fn_url}">{$row.1}</a></td><td>{$row.2}</td><td>{$row.3}</td></tr>
        {/foreach}
        </tbody></table>
    </div>
    {/if}

    {if $c.no_template}
    {include file="common/subheader.tpl" title="Категории без шаблона Авито" target="#gm_avito_p_notpl"}
    <div id="gm_avito_p_notpl" class="in collapse">
        <p class="muted">Шаблон добавляется на странице «Товары &#8594; Авито: шаблоны категорий». Уценка, сертификаты и общие разделы оставлены без шаблона намеренно.</p>
        <table class="table table-middle"><thead><tr><th>Категория</th><th>Id</th><th>Товаров / в наличии</th></tr></thead><tbody>
        {foreach from=$c.no_template key="category_id" item="m"}
            <tr><td>{$m.path}</td><td>{$category_id}</td><td>{$m.count} / {$m.in_stock}</td></tr>
        {/foreach}
        </tbody></table>
    </div>
    {/if}
    {/if}

    <div class="buttons-container">
        <input type="submit" class="btn btn-primary" name="dispatch[gm_avito.check]" value="Проверить сейчас" />
        <input type="submit" class="btn" name="dispatch[gm_avito.sync]" value="Обновить отчёт Авито" />
    </div>
</form>
{/if}
{literal}
<script>
document.addEventListener('input', function (e) {
    if (!e.target.classList || !e.target.classList.contains('gm-avito-filter')) return;
    var q = e.target.value.toLowerCase().trim();
    var box = document.getElementById(e.target.getAttribute('data-target'));
    if (!box) return;
    box.querySelectorAll('.gm-avito-filter-item').forEach(function (el) {
        el.style.display = !q || el.textContent.toLowerCase().indexOf(q) !== -1 ? '' : 'none';
    });
});
</script>
{/literal}
{/capture}
{if $gm_avito_company}{$gm_avito_title = "Авито: проблемы &#8211; `$gm_avito_company`"}{else}{$gm_avito_title = "Авито: проблемы"}{/if}
{include file="common/mainbox.tpl" title=$gm_avito_title content=$smarty.capture.mainbox}
