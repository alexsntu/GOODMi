{* GOODMi: «Товары → Авито → Обзор» (2026-10-20) – файл, последняя загрузка Авито, ошибки, баланс, статистика и подсказки *}
{capture name="mainbox"}
{include file="addons/gm_avito/views/gm_avito/components/nav.tpl"}
{if !$gm_avito_allowed}
    <p>Выберите витрину кнопками вверху.</p>
{else}
{$d = $gm_avito_dash}
{$card = "border: 1px solid #e3e3e3; border-radius: 6px; padding: 12px 16px; margin-bottom: 14px; background: #fff; min-height: 150px;"}
<form action="{""|fn_url}" method="post" name="gm_avito_dashboard_form">
<input type="hidden" name="return_to" value="dashboard" />
{if $d.account_name}<p class="muted">Аккаунт Авито: {$d.account_name}</p>{/if}
<div class="row-fluid">
    <div class="span3"><div style="{$card}">
        <h4>Файл для Авито</h4>
        {if $d.build.time}
            <p>Собран {$d.build.time|date_format:"`$settings.Appearance.date_format`, `$settings.Appearance.time_format`"}</p>
            <p><strong style="font-size: 20px;">{$d.build.ads}</strong> объявлений в файле{if $d.build.skipped}, не попали {$d.build.skipped}{/if}</p>
            {if $d.build.reasons}<ul class="muted">{foreach from=$d.build.reasons key="reason" item="n"}<li>{$reason}: {$n}</li>{/foreach}</ul>{/if}
        {else}<p class="muted">ещё не собирался</p>{/if}
        <p class="muted">Сборка каждый час в :{$d.cron.build|string_format:"%02d"}</p>
        <input type="submit" class="btn btn-small" name="dispatch[gm_avito.build]" value="Собрать сейчас" />
    </div></div>
    <div class="span3"><div style="{$card}">
        <h4>Последняя загрузка Авито</h4>
        {if $d.report_started}
            <p>{$d.report_started} (UTC) &#183; {if $d.report.upload_status == "success"}успешно{elseif $d.report.upload_status == "success_warning"}успешно, есть замечания{else}{$d.report.upload_status}{/if}</p>
            <ul>{foreach from=$d.report.stats.sections item="s"}<li>{$s.title}: <strong>{$s.count}</strong></li>{/foreach}</ul>
        {else}<p class="muted">отчёта ещё нет</p>{/if}
        <p class="muted">Файл в кабинете: {$d.feed|default:"&#8211;"}{if $d.profile.time && !$d.profile.autoload_enabled} &#183; <strong class="text-error">автозагрузка выключена</strong>{/if}{if $d.hours}<br />Авито забирает в: {$d.hours} ч{/if}</p>
        <input type="submit" class="btn btn-small" name="dispatch[gm_avito.sync]" value="Обновить отчёт" />
    </div></div>
    <div class="span3"><div style="{$card}">
        <h4>Объявления</h4>
        <p><strong style="font-size: 20px; color: #3c9a3c;">{$d.published}</strong> опубликовано без замечаний</p>
        <p><strong style="font-size: 20px; {if $d.problems}color: #c0392b;{/if}">{$d.problems}</strong> с замечаниями Авито</p>
        {if $d.check.time}<p class="muted">Включено {$d.check.enabled}, в файле {$d.check.in_file}; категорий с незаполненными полями {$d.check.missing|count}, заголовков длиннее 50 знаков {$d.check.long_titles|count}</p>{/if}
        <a class="btn btn-small" href="{"gm_avito.problems"|fn_url}">Проблемы и отчёт &#8594;</a>
    </div></div>
    <div class="span3"><div style="{$card}">
        <h4>Баланс кабинета Авито</h4>
        {if $d.balance.time}
            <p>Кошелёк: <strong style="font-size: 18px;">{$d.balance.wallet_fmt} ₽</strong>{if $d.balance.bonus} + бонусы {$d.balance.bonus_fmt}{/if}</p>
            {if $d.balance.cpa_fmt}<p>Счёт продвижения и целевых действий: <strong>{$d.balance.cpa_fmt} ₽</strong></p>{/if}
            <p class="muted">Обновлено {$d.balance.time|date_format:"`$settings.Appearance.date_format`, `$settings.Appearance.time_format`"}</p>
        {else}<p class="muted">ещё не получали</p>{/if}
        <p class="muted">Баланс и статистика обновляются в 9:30 и 15:30</p>
        <input type="submit" class="btn btn-small" name="dispatch[gm_avito.stats]" value="Обновить сейчас" />
    </div></div>
</div>

{$sm = $d.summary}
{include file="common/subheader.tpl" title="Статистика за 30 дней" target="#gm_avito_dash_stats"}
<div id="gm_avito_dash_stats" class="in collapse">
    {if !$sm.total.impressions && !$sm.total.views}
        <p class="muted">Статистики ещё нет – она собирается в 9:30 и 15:30 (или кнопкой «Обновить сейчас»).</p>
    {else}
    <div class="row-fluid">
        {foreach from=$sm.metrics item="m"}
            <div class="span2"><div style="{$card} min-height: 80px;">
                <div class="muted">{$m.label}</div>
                <div style="font-size: 22px; font-weight: bold;">{$m.value}</div>
                {if $m.delta !== null}<div class="{if $m.delta >= 0}text-success{else}text-error{/if}">{if $m.delta >= 0}+{/if}{$m.delta}% к прошлым 30 дням</div>{/if}
            </div></div>
        {/foreach}
        <div class="span2"><div style="{$card} min-height: 80px;"><div class="muted">Показ → просмотр</div><div style="font-size: 22px; font-weight: bold;">{$sm.ctr}%</div></div></div>
        <div class="span2"><div style="{$card} min-height: 80px;"><div class="muted">Просмотр → контакт</div><div style="font-size: 22px; font-weight: bold;">{$sm.cr}%</div></div></div>
    </div>
    <p class="muted">Просмотры по дням:</p>
    <div style="display: flex; align-items: flex-end; gap: 3px; height: 120px; border-bottom: 1px solid #ccc; margin-bottom: 4px;">
        {foreach from=$sm.by_day item="day"}
            <div title="{$day.date}: просмотров {$day.views}, контактов {$day.contacts}, показов {$day.impressions}" style="flex: 1; background: #4a90d9; height: {$day.h}px;"></div>
        {/foreach}
    </div>
    {if $sm.by_day}<p class="muted" style="font-size: 11px;">{$sm.first_day} – {$sm.last_day}; наведите на столбик, чтобы увидеть цифры</p>{/if}
    {/if}
</div>

{include file="common/subheader.tpl" title="Подсказки" target="#gm_avito_dash_hints"}
<div id="gm_avito_dash_hints" class="in collapse">
    {if $sm.silent_count}<p><strong>{$sm.silent_count}</strong> опубликованных объявлений за 30 дней не получили ни одного показа – проверьте раздел, заголовок (что ищут люди) и цену.</p>{/if}
    <div class="row-fluid">
        <div class="span4">
            <h5>Лучшие по контактам</h5>
            <table class="table table-condensed"><thead><tr><th>Объявление</th><th>Просм.</th><th>Конт.</th></tr></thead><tbody>
            {foreach from=$sm.top item="r"}<tr><td>{if $r.product_id}<a href="{"products.update?product_id=`$r.product_id`&selected_section=gm_avito"|fn_url}">{$r.product|truncate:45}</a>{else}№ {$r.avito_id}{/if}</td><td>{$r.views}</td><td><strong>{$r.contacts}</strong></td></tr>{foreachelse}<tr><td colspan="3" class="muted">пока нет</td></tr>{/foreach}
            </tbody></table>
        </div>
        <div class="span4">
            <h5>Много показов – мало просмотров</h5>
            <p class="muted" style="font-size: 11px;">Видят в поиске, но не открывают: первое фото, цена, заголовок.</p>
            <table class="table table-condensed"><thead><tr><th>Объявление</th><th>Показы</th><th>Просм.</th></tr></thead><tbody>
            {foreach from=$sm.no_views item="r"}<tr><td>{if $r.product_id}<a href="{"products.update?product_id=`$r.product_id`&selected_section=gm_avito"|fn_url}">{$r.product|truncate:45}</a>{else}№ {$r.avito_id}{/if}</td><td>{$r.impressions}</td><td>{$r.views}</td></tr>{foreachelse}<tr><td colspan="3" class="muted">нет</td></tr>{/foreach}
            </tbody></table>
        </div>
        <div class="span4">
            <h5>Смотрят, но не пишут</h5>
            <p class="muted" style="font-size: 11px;">Много просмотров без контактов: цена выше рынка или текст не отвечает на вопросы.</p>
            <table class="table table-condensed"><thead><tr><th>Объявление</th><th>Просм.</th><th>Конт.</th></tr></thead><tbody>
            {foreach from=$sm.no_contacts item="r"}<tr><td>{if $r.product_id}<a href="{"products.update?product_id=`$r.product_id`&selected_section=gm_avito"|fn_url}">{$r.product|truncate:45}</a>{else}№ {$r.avito_id}{/if}</td><td>{$r.views}</td><td>0</td></tr>{foreachelse}<tr><td colspan="3" class="muted">нет</td></tr>{/foreach}
            </tbody></table>
        </div>
    </div>
    {if $d.stats_sync.time}<p class="muted">Статистика собрана {$d.stats_sync.time|date_format:"`$settings.Appearance.date_format`, `$settings.Appearance.time_format`"}; объявлений со статистикой {$sm.items_with_stats}.</p>{/if}
</div>
</form>
{/if}
{/capture}
{if $gm_avito_dash.company}{$gm_avito_title = "Авито: обзор &#8211; `$gm_avito_dash.company`"}{else}{$gm_avito_title = "Авито: обзор"}{/if}
{include file="common/mainbox.tpl" title=$gm_avito_title content=$smarty.capture.mainbox}
