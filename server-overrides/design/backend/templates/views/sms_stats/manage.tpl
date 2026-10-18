{* Все витрины: «Покупатели → СМС: работа и баланс» (dispatch=sms_stats.manage). Данные – sms_stats_overview() в app/sms_stats/stats.php. *}
{capture name="mainbox"}
<style>{literal}
.sms-tabs{display:flex;flex-wrap:wrap;gap:6px;margin:0 0 14px}
.sms-tabs a{padding:6px 12px;border:1px solid #d7dbe0;border-radius:16px;color:#333;text-decoration:none}
.sms-tabs a.on{border-color:#0071e3;background:#0071e3;color:#fff}
.sms-kpi{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:10px;margin:0 0 18px}
.sms-kpi div{padding:12px 14px;border:1px solid #e3e6ea;border-radius:10px;background:#fff}
.sms-kpi b{display:block;font-size:22px;line-height:1.2}.sms-kpi span{font-size:12px;color:#777}
.sms-kpi .bad{border-color:#e8b4ae;background:#fdf3f2}.sms-kpi .bad b{color:#c0392b}.sms-kpi .good b{color:#1e8e3e}
.sms-cols{display:grid;grid-template-columns:repeat(auto-fit,minmax(380px,1fr));gap:20px}
.sms-note{color:#777;font-size:12px;margin:4px 0 12px}
.sms-alert{padding:10px 14px;border-radius:8px;background:#fdf3f2;border:1px solid #e8b4ae;color:#8a2a20;margin:0 0 14px}
.sms-warn{color:#c0392b}
{/literal}</style>

<div class="sms-tabs">
    <a href="{"sms_stats.manage?days=0"|fn_url}" {if $sms_days == 0}class="on"{/if}>Сегодня</a>
    <a href="{"sms_stats.manage?days=7"|fn_url}" {if $sms_days == 7}class="on"{/if}>7 дней</a>
    <a href="{"sms_stats.manage?days=30"|fn_url}" {if $sms_days == 30}class="on"{/if}>30 дней</a>
    <a href="{"sms_stats.manage?days=90"|fn_url}" {if $sms_days == 90}class="on"{/if}>90 дней</a>
</div>

{if $sms.collect_error}
    <div class="sms-alert">Последний сбор данных оператора ({$sms.collected|default:"–"}) не удался: {$sms.collect_error}. Баланс и доставка показаны по последнему удачному сбору.</div>
{/if}
{if $sms.balance_low}
    <div class="sms-alert">На балансе Таргет СМС меньше {$sms.low_limit} ₽ – пополните, иначе коды подтверждения и СМС о статусах заказов перестанут уходить.</div>
{/if}

<h4>Баланс Таргет СМС (один кабинет на все витрины)</h4>
<div class="sms-kpi">
    <div class="{if $sms.balance_low}bad{/if}"><b>{$sms.balance_fmt} ₽</b><span>на балансе{if $sms.balance_at}, на {$sms.balance_at}{/if}</span></div>
    <div><b>{$sms.spent_day_fmt} ₽</b><span>расход в день (среднее за 7 дней)</span></div>
    <div class="{if $sms.days_left !== null && $sms.days_left < 14}bad{/if}"><b>{if $sms.days_left !== null}≈ {$sms.days_left} дн.{else}–{/if}</b><span>хватит при таком расходе</span></div>
    <div><b>{$sms.total.msgs}</b><span>СМС за период, на {$sms.total.cost_fmt} ₽</span></div>
    <div class="{if $sms.total.msgs && $sms.total.deliver_pct < 90}bad{else}good{/if}"><b>{$sms.total.deliver_pct}%</b><span>доставлено ({$sms.total.deliver} из {$sms.total.msgs}{if $sms.total.waiting}, ждут статуса – {$sms.total.waiting}{/if})</span></div>
</div>
<p class="sms-note">Данные оператора собираются раз в час (в :05), последний сбор – {$sms.collected|default:"ещё не было"}. Данные кабинетов (запросы и ввод кодов) – на момент открытия страницы. Период – с {$sms.since}.</p>

<h4>Коды подтверждения: путь покупателя</h4>
<div class="table-responsive-wrapper">
<table class="table table-middle">
    <thead><tr><th>&nbsp;</th>{foreach $sms.stores as $s}<th class="right">{$s.name}</th>{/foreach}</tr></thead>
    <tbody>
        <tr><td>Покупателей (разных номеров) запросили код</td>{foreach $sms.stores as $s}<td class="right"><strong>{$s.customers}</strong></td>{/foreach}</tr>
        <tr><td>Запросов кода</td>{foreach $sms.stores as $s}<td class="right">{$s.requested}</td>{/foreach}</tr>
        <tr><td>Отправлено оператору <span class="muted">(подтверждение номера / восстановление пароля)</span></td>{foreach $sms.stores as $s}<td class="right">{$s.sent} <span class="muted">({$s.sent_verify} / {$s.sent_recover})</span></td>{/foreach}</tr>
        <tr><td>Не отправлено: ошибка оператора</td>{foreach $sms.stores as $s}<td class="right {if $s.errors}sms-warn{/if}">{$s.errors}</td>{/foreach}</tr>
        <tr><td>Не отправлено: слишком много запросов (лимит)</td>{foreach $sms.stores as $s}<td class="right">{$s.limits}</td>{/foreach}</tr>
        <tr><td>Доставлено на телефон <span class="muted">(по данным оператора)</span></td>{foreach $sms.stores as $s}<td class="right">{$s.delivered} <span class="muted">({$s.deliver_pct}%)</span>{if $s.op_failed} <span class="sms-warn">не доставлено {$s.op_failed}</span>{/if}{if $s.op_waiting} <span class="muted">ждут {$s.op_waiting}</span>{/if}</td>{/foreach}</tr>
        <tr><td>Ввели верный код <span class="muted">(подтверждение / восстановление)</span></td>{foreach $sms.stores as $s}<td class="right"><strong>{$s.confirmed}</strong> <span class="muted">({$s.confirmed_verify} / {$s.confirmed_recover})</span></td>{/foreach}</tr>
        <tr><td>Доля отправленных кодов, которые ввели</td>{foreach $sms.stores as $s}<td class="right {if $s.sent && $s.confirm_pct < 50}sms-warn{/if}">{$s.confirm_pct}%</td>{/foreach}</tr>
        <tr><td>Неверный код</td>{foreach $sms.stores as $s}<td class="right">{$s.code_bad}</td>{/foreach}</tr>
        <tr><td>Неверный пароль при входе по номеру</td>{foreach $sms.stores as $s}<td class="right">{$s.login_bad}</td>{/foreach}</tr>
        <tr><td>Блокировки (вход или номер)</td>{foreach $sms.stores as $s}<td class="right">{$s.locks}</td>{/foreach}</tr>
        <tr><td>Стоимость кодов</td>{foreach $sms.stores as $s}<td class="right">{$s.cost_fmt} ₽</td>{/foreach}</tr>
    </tbody>
</table>
</div>

<h4>Все СМС кабинета оператора по видам</h4>
<div class="table-responsive-wrapper">
<table class="table table-middle">
    <thead><tr><th>Вид</th><th class="right">СМС</th><th class="right">Разных номеров</th><th class="right">Доставлено</th><th class="right">Не доставлено</th><th class="right">Стоимость</th><th class="right">Средняя цена</th></tr></thead>
    <tbody>
    {foreach $sms.kinds as $k}
        <tr><td>{$k.title}</td><td class="right">{$k.msgs}</td><td class="right">{$k.phones}</td><td class="right">{$k.deliver_pct}%</td><td class="right {if $k.failed}sms-warn{/if}">{$k.failed}</td><td class="right">{$k.cost_fmt} ₽</td><td class="right">{$k.avg_fmt} ₽</td></tr>
    {foreachelse}
        <tr><td colspan="7" class="no-items">Нет данных за период</td></tr>
    {/foreach}
    </tbody>
</table>
</div>
<p class="sms-note">Цена – по данным оператора, за все части СМС. «Статусы заказов из МойСклад» и «Шлюз GET» отправляют не кабинеты сайта, а другие программы с тем же кабинетом оператора.</p>

<div class="sms-cols">
<div>
    <h4>От чьего имени приходят коды</h4>
    <table class="table table-middle">
        <thead><tr><th>Коды</th><th>Отправитель у покупателя</th><th>Оператор связи</th><th class="right">СМС</th></tr></thead>
        <tbody>
        {foreach $sms.originators as $r}
            <tr><td>{$r.title}</td><td class="{if !$r.own}sms-warn{/if}">{$r.originator|default:"–"}</td><td>{$r.operator}</td><td class="right">{$r.n}</td></tr>
        {foreachelse}
            <tr><td colspan="4" class="no-items">Нет данных за период</td></tr>
        {/foreach}
        </tbody>
    </table>
    <p class="sms-note">Красным – коды пришли не от имени витрины: оператор связи подставил своё имя (имя отправителя не зарегистрировано у этого оператора).</p>
</div>
<div>
    <h4>Операторы связи</h4>
    <table class="table table-middle">
        <thead><tr><th>Оператор</th><th class="right">СМС</th><th class="right">Доставлено</th></tr></thead>
        <tbody>
        {foreach $sms.operators as $r}
            <tr><td>{$r.operator|default:"–"}</td><td class="right">{$r.msgs}</td><td class="right {if $r.deliver_pct < 90}sms-warn{/if}">{$r.deliver_pct}%</td></tr>
        {foreachelse}
            <tr><td colspan="3" class="no-items">Нет данных за период</td></tr>
        {/foreach}
        </tbody>
    </table>
</div>
</div>

<div class="sms-cols">
<div>
    <h4>Ошибки отправки кодов</h4>
    <table class="table table-middle">
        <thead><tr><th>Когда</th><th>Витрина</th><th>Номер</th><th>Ответ оператора</th></tr></thead>
        <tbody>
        {foreach $sms.send_errors as $r}
            <tr><td class="nowrap">{$r.when}</td><td>{$r.store}</td><td class="nowrap">{$r.phone}</td><td>{$r.info}</td></tr>
        {foreachelse}
            <tr><td colspan="4" class="no-items">Ошибок не было</td></tr>
        {/foreach}
        </tbody>
    </table>
</div>
<div>
    <h4>Недоставленные СМС</h4>
    <table class="table table-middle">
        <thead><tr><th>Когда</th><th>Вид</th><th>Оператор</th><th>Номер</th><th>Статус</th></tr></thead>
        <tbody>
        {foreach $sms.failed as $r}
            <tr><td class="nowrap">{$r.when}</td><td>{$r.title}</td><td>{$r.operator}</td><td class="nowrap">…{$r.phone_tail}</td><td class="sms-warn">{$r.status_title}</td></tr>
        {foreachelse}
            <tr><td colspan="5" class="no-items">Все доставлены</td></tr>
        {/foreach}
        </tbody>
    </table>
</div>
</div>

<h4>По дням</h4>
<div class="table-responsive-wrapper">
<table class="table table-middle">
    <thead><tr><th>День</th><th class="right">Кодов GOODMi: отправлено / ввели</th><th class="right">Кодов Maxmobiles: отправлено / ввели</th><th class="right">Ошибок отправки</th><th class="right">Всех СМС</th><th class="right">Расход</th></tr></thead>
    <tbody>
    {foreach $sms.days as $d}
        <tr><td>{$d.d}</td><td class="right">{$d.gm_sent} / {$d.gm_ok}</td><td class="right">{$d.mm_sent} / {$d.mm_ok}</td><td class="right {if $d.errors}sms-warn{/if}">{$d.errors}</td><td class="right">{$d.msgs}</td><td class="right">{$d.cost_fmt} ₽</td></tr>
    {foreachelse}
        <tr><td colspan="6" class="no-items">Нет данных за период</td></tr>
    {/foreach}
    </tbody>
</table>
</div>
{/capture}
{include file="common/mainbox.tpl" title="СМС: работа и баланс" content=$smarty.capture.mainbox}
