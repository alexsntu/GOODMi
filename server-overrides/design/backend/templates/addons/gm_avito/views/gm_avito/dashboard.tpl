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

{$st = $d.strategy}
{include file="common/subheader.tpl" title="Стратегия продвижения" target="#gm_avito_dash_strategy"}
<div id="gm_avito_dash_strategy" class="in collapse">
    <div style="{$card} min-height: 0; border-left: 4px solid #4a90d9;">
        <p><strong>Что работает сейчас.</strong> {$st.now}</p>
        {if $st.promos_time}<p class="muted" style="font-size: 11px;">Ставки проверены у Авито {$st.promos_time|date_format:"`$settings.Appearance.date_format`, `$settings.Appearance.time_format`"} по {$st.checked} объявлениям; обновляются вместе со статистикой.</p>{/if}
        {if $st.promos}
            <details><summary>Объявления со ставкой ({$st.promos|count})</summary>
            <table class="table table-condensed" style="width: auto;"><thead><tr><th>Объявление</th><th>Настройка</th><th>За что платим</th><th>Ставка / бюджет</th></tr></thead><tbody>
            {foreach from=$st.promos item="r"}<tr><td>{if $r.product_id}<a href="{"products.update?product_id=`$r.product_id`&selected_section=gm_avito"|fn_url}">{$r.product|truncate:60}</a>{else}№ {$r.avito_id}{/if}</td><td>{$r.type}</td><td>{$r.action}</td><td>{$r.value}</td></tr>{/foreach}
            </tbody></table>
            </details>
        {/if}
    </div>
    {if $st.summary}
        <p><strong>Стратегия{if $st.status} – {$st.status}{/if}.</strong> {$st.summary}</p>
        <div class="row-fluid">
            {if $st.inputs}<div class="span4"><h5>Из чего исходим</h5><ul>{foreach from=$st.inputs item="line"}<li>{$line}</li>{/foreach}</ul></div>{/if}
            {if $st.rules}<div class="span4"><h5>Правила</h5><ol>{foreach from=$st.rules item="line"}<li>{$line}</li>{/foreach}</ol></div>{/if}
            {if $st.plan}<div class="span4"><h5>Порядок действий</h5><ol>{foreach from=$st.plan item="line"}<li>{$line}</li>{/foreach}</ol></div>{/if}
        </div>
        {if $st.advice}
            <h5>Кандидаты на ставку по расчёту{if $st.advice_date} от {$st.advice_date}{/if}</h5>
            <p class="muted" style="font-size: 11px;">Ценность просмотра – сколько прибыли в среднем приносит один просмотр при наших правилах; ставка – ступень сетки Авито с наибольшей ожидаемой прибылью; «обходит» – доля конкурентов, которых объявление обойдёт по оценке Авито.</p>
            <div style="max-height: 420px; overflow: auto; margin-bottom: 12px;">
            <table class="table table-condensed"><thead><tr><th>Объявление</th><th>Цена, ₽</th><th>Ценность просмотра, ₽</th><th>Ставка, ₽</th><th>Обходит</th><th>Рекомендация Авито, ₽</th><th>Дневной лимит, ₽</th></tr></thead><tbody>
            {foreach from=$st.advice item="r"}<tr><td>{if $r.product_id}<a href="{"products.update?product_id=`$r.product_id`&selected_section=gm_avito"|fn_url}">{$r.title|truncate:60}</a>{else}{$r.title}{/if}</td><td>{$r.price}</td><td>{$r.view_value}</td><td><strong>{$r.bid}</strong></td><td>{$r.compare}%</td><td>{$r.rec_bid}</td><td>{$r.limit}</td></tr>{/foreach}
            </tbody></table>
            </div>
        {/if}
        {if $st.updated}<p class="muted" style="font-size: 11px;">Стратегия пересмотрена {$st.updated}.{if $st.review} Следующий пересмотр: {$st.review}.{/if}</p>{/if}
    {else}
        <p class="muted">Стратегия для этой витрины ещё не записана.</p>
    {/if}
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

{$sc = $sm.spend_card}
{include file="common/subheader.tpl" title="Расходы на Авито за 30 дней" target="#gm_avito_dash_spend"}
<div id="gm_avito_dash_spend" class="in collapse">
    {if !$sm.total.spending}
        <p class="muted">Расходов пока нет или они ещё не собраны – собираются вместе со статистикой в 9:30 и 15:30.</p>
    {else}
    <div class="row-fluid">
        <div class="span3"><div style="{$card} min-height: 80px;">
            <div class="muted">Потрачено</div>
            <div style="font-size: 22px; font-weight: bold;">{$sc.total} ₽</div>
            {if $sc.delta !== null}<div class="{if $sc.delta > 0}text-error{else}text-success{/if}">{if $sc.delta >= 0}+{/if}{$sc.delta}% к прошлым 30 дням</div>{/if}
        </div></div>
        <div class="span3"><div style="{$card} min-height: 80px;">
            <div class="muted">В среднем за день</div>
            <div style="font-size: 22px; font-weight: bold;">{$sc.per_day} ₽</div>
            <div class="muted">размещение{if $sm.total.promo_spending} и продвижение {$sc.promo} ₽{else}, продвижения не было{/if}</div>
        </div></div>
        <div class="span3"><div style="{$card} min-height: 80px;">
            <div class="muted">Один контакт обходится в</div>
            <div style="font-size: 22px; font-weight: bold;">{if $sc.cpc}{$sc.cpc} ₽{else}&#8211;{/if}</div>
            {if $sc.cpc_was}<div class="muted">прошлые 30 дней: {$sc.cpc_was} ₽</div>{/if}
        </div></div>
        <div class="span3"><div style="{$card} min-height: 80px;">
            <div class="muted">Объявлений с расходом</div>
            <div style="font-size: 22px; font-weight: bold;">{$sm.spend|count}</div>
            <div class="muted">остальные размещаются бесплатно</div>
        </div></div>
    </div>
    <p class="muted">Расход по дням:</p>
    <div style="display: flex; align-items: flex-end; gap: 3px; height: 120px; border-bottom: 1px solid #ccc; margin-bottom: 4px;">
        {foreach from=$sm.by_day item="day"}
            <div title="{$day.date}: расход {$day.spending_rub} ₽, просмотров {$day.views}, контактов {$day.contacts}" style="flex: 1; background: #e08a2c; height: {$day.hs}px;"></div>
        {/foreach}
    </div>
    <p class="muted" style="font-size: 11px;">{$sm.first_day} – {$sm.last_day}; наведите на столбик, чтобы увидеть цифры</p>
    <details style="margin-bottom: 12px;"><summary>Таблица по дням</summary>
        <table class="table table-condensed" style="width: auto;"><thead><tr><th>День</th><th>Расход, ₽</th><th>Просмотры</th><th>Контакты</th><th>₽ за контакт</th></tr></thead><tbody>
        {foreach from=$sm.by_day_desc item="day"}<tr><td>{$day.date}</td><td><strong>{$day.spending_rub}</strong>{if $day.promo_spending} <span class="muted">(продв. {$day.promo_rub})</span>{/if}</td><td>{$day.views}</td><td>{$day.contacts}</td><td>{$day.cpc_rub|default:"&#8211;"}</td></tr>{/foreach}
        <tr><td><strong>Итого</strong></td><td><strong>{$sm.total.spending_rub}</strong></td><td>{$sm.total.views}</td><td>{$sm.total.contacts}</td><td>{$sm.total.cpc_rub|default:"&#8211;"}</td></tr>
        </tbody></table>
    </details>
    <h5>Расходы по объявлениям</h5>
    <p class="muted" style="font-size: 11px;">Сначала самые дорогие. Клик по названию – вкладка «Авито» товара, там расход по дням.</p>
    <div style="max-height: 420px; overflow: auto; margin-bottom: 12px;">
    <table class="table table-condensed"><thead><tr><th>Объявление</th><th>Расход, ₽</th><th>Просм.</th><th>Конт.</th><th>₽ за контакт</th>{if $sm.in_stock_only}<th>Остаток</th>{/if}</tr></thead><tbody>
    {foreach from=$sm.spend item="r"}<tr><td>{if $r.product_id}<a href="{"products.update?product_id=`$r.product_id`&selected_section=gm_avito"|fn_url}">{$r.product|truncate:60}</a>{else}№ {$r.avito_id}{/if}</td><td><strong>{$r.spending_rub}</strong></td><td>{$r.views}</td><td>{$r.contacts}</td><td>{$r.cpc_rub|default:"&#8211;"}</td>{if $sm.in_stock_only}<td>{if $r.product_id}{$r.amount}{/if}</td>{/if}</tr>{/foreach}
    </tbody></table>
    </div>
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
    <div class="row-fluid">
        <div class="span6">
            <h5>Тратит деньги, контактов нет</h5>
            <p class="muted" style="font-size: 11px;">От 100 ₽ за 30 дней и ни одного контакта: проверьте цену, фото и текст или снимите галочку «Продавать на Авито».</p>
            <table class="table table-condensed"><thead><tr><th>Объявление</th><th>Расход, ₽</th><th>Просм.</th></tr></thead><tbody>
            {foreach from=$sm.spend_no_contacts item="r"}<tr><td>{if $r.product_id}<a href="{"products.update?product_id=`$r.product_id`&selected_section=gm_avito"|fn_url}">{$r.product|truncate:55}</a>{else}№ {$r.avito_id}{/if}</td><td><strong>{$r.spending_rub}</strong></td><td>{$r.views}</td></tr>{foreachelse}<tr><td colspan="3" class="muted">нет</td></tr>{/foreach}
            </tbody></table>
        </div>
        {if $sm.in_stock_only}
        <div class="span6">
            <h5>Платим за товар без остатка</h5>
            <p class="muted" style="font-size: 11px;">На сайте остаток 0, а за последние 3 дня было списание. Обычно такие объявления снимаются при следующей сборке файла.</p>
            <table class="table table-condensed"><thead><tr><th>Объявление</th><th>Расход, ₽</th><th>Остаток</th></tr></thead><tbody>
            {foreach from=$sm.paid_no_stock item="r"}<tr><td><a href="{"products.update?product_id=`$r.product_id`&selected_section=gm_avito"|fn_url}">{$r.product|truncate:55}</a></td><td><strong>{$r.spending_rub}</strong></td><td>{$r.amount}</td></tr>{foreachelse}<tr><td colspan="3" class="muted">нет</td></tr>{/foreach}
            </tbody></table>
        </div>
        {/if}
    </div>
    {if $sm.few_photos_count}
    <h5>Мало фото – меньше 7 ({$sm.few_photos_count} из {$sm.enabled_with_stats})</h5>
    <p class="muted" style="font-size: 11px;">В объявление попадают фото из карточки товара на сайте (до 10). Добавьте в карточку настоящие фото товара – объявление обновится со следующей выгрузкой. Сверху – больше всего просмотров: там фото дадут больше всего. Для б/у – живые фото именно этого экземпляра.</p>
    <table class="table table-condensed"><thead><tr><th>Товар</th><th>Фото</th><th>Просм.</th><th>Конт.</th></tr></thead><tbody>
    {foreach from=$sm.few_photos item="r"}<tr><td><a href="{"products.update?product_id=`$r.product_id`"|fn_url}">{$r.product|truncate:70}</a></td><td><strong>{$r.photos}</strong></td><td>{$r.views}</td><td>{$r.contacts}</td></tr>{/foreach}
    </tbody></table>
    {/if}
    {if $d.stats_sync.time}<p class="muted">Статистика собрана {$d.stats_sync.time|date_format:"`$settings.Appearance.date_format`, `$settings.Appearance.time_format`"}; объявлений со статистикой {$sm.items_with_stats}.</p>{/if}
</div>
</form>
{/if}
{/capture}
{if $gm_avito_dash.company}{$gm_avito_title = "Авито: обзор &#8211; `$gm_avito_dash.company`"}{else}{$gm_avito_title = "Авито: обзор"}{/if}
{include file="common/mainbox.tpl" title=$gm_avito_title content=$smarty.capture.mainbox}
