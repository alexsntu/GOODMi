{* GOODMi: «Маркетинг-портал → Задачи» (2026-10-21) – список дел; время напоминания – routine шлёт его в Telegram (будни, раз в час 11–17 МСК) *}
{capture name="mainbox"}
{include file="addons/gm_marketing/views/gm_marketing/components/nav.tpl"}
<div class="gm-mkt">
{include file="common/subheader.tpl" title="Новая задача" target="#gm_mkt_task_add"}
<div id="gm_mkt_task_add" class="in collapse">
<form action="{""|fn_url}" method="post" class="gm-mkt-form">
    <p><input type="text" name="task[title]" placeholder="Что нужно сделать" required /></p>
    <p><textarea name="task[description]" rows="2" placeholder="Подробности (необязательно)"></textarea></p>
    <div class="gm-mkt-row">
        <label>Срок (МСК)<br /><input type="datetime-local" name="task[due_at]" /></label>
        <label>Напомнить в Telegram (МСК)<br /><input type="datetime-local" name="task[remind_at]" /></label>
    </div>
    <p><button type="submit" class="btn btn-primary" name="dispatch[gm_marketing.add_task]">Добавить задачу</button></p>
</form>
</div>

{include file="common/subheader.tpl" title="Задачи" target="#gm_mkt_tasks"}
<div id="gm_mkt_tasks" class="in collapse">
{include file="addons/gm_marketing/views/gm_marketing/components/review_filters.tpl" filters=["pending" => "Открытые", "done" => "Сделанные", "all" => "Все"]}
{if !$gm_mkt_tasks}<p class="muted">Задач нет.</p>{/if}
{foreach from=$gm_mkt_tasks item="t"}
    <div class="gm-mkt-card">
        <div class="gm-mkt-meta">
            {if $t.due_msk}<span class="gm-mkt-tag">Срок: {$t.due_msk} МСК</span>{/if}
            {if $t.remind_msk}<span>Напоминание: {$t.remind_msk} МСК{if $t.reminded} (отправлено){/if}</span>{/if}
        </div>
        <div class="gm-mkt-title">{$t.title|escape}</div>
        {if $t.description}<div class="gm-mkt-text">{$t.description|escape}</div>{/if}
        <div class="gm-mkt-actions">
            {if $t.status != "done"}
                {include file="addons/gm_marketing/views/gm_marketing/components/status_btn.tpl" type="task" id=$t.id status="done" label="Сделано" class="btn-primary"}
            {else}
                {include file="addons/gm_marketing/views/gm_marketing/components/status_btn.tpl" type="task" id=$t.id status="pending" label="Вернуть в открытые"}
            {/if}
            <form action="{""|fn_url}" method="post">
                <input type="hidden" name="id" value="{$t.id}" />
                <input type="hidden" name="redirect_url" value="{$config.current_url}" />
                <button type="submit" class="btn btn-small cm-confirm" name="dispatch[gm_marketing.delete_task]">Удалить</button>
            </form>
        </div>
    </div>
{/foreach}
</div>
</div>
{/capture}
{include file="common/mainbox.tpl" title="Маркетинг-портал: задачи" content=$smarty.capture.mainbox}
