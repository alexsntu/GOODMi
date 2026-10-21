{* GOODMi: «Маркетинг-портал → Бонусы» (2026-10-21) – массовое списание (коррекция) бонусов БонусПлюс по списку «телефон сумма» *}
{capture name="mainbox"}
{include file="addons/gm_marketing/views/gm_marketing/components/nav.tpl"}
<div class="gm-mkt">
{if !$gm_mkt_bp_configured}
    <div class="alert alert-error">Не задан ключ API БонусПлюс (файл private/gm_marketing.json на сервере) – списание работать не будет.</div>
{/if}
<p>Массовое списание (коррекция) бонусов у списка клиентов – например, отмена ошибочно начисленных бонусов. <a href="{"gm_marketing.bonus_log"|fn_url}">Журнал всех операций &#8594;</a></p>
<form action="{""|fn_url}" method="post" class="gm-mkt-form">
    <p><strong>Список: телефон и сумма списания, по одному на строку</strong></p>
    <p><textarea name="list" id="gm_mkt_bonus_list" rows="10" placeholder="79991234567 500&#10;79997654321 320" required></textarea></p>
    <p>Или загрузить файл (.txt/.csv, тот же формат): <input type="file" id="gm_mkt_bonus_file" accept=".txt,.csv" /></p>
    <p><button type="submit" class="btn btn-primary" name="dispatch[gm_marketing.bonus_preview]">Посчитать превью</button> <span class="muted">Превью ничего не списывает – только показывает балансы клиентов.</span></p>
</form>
{literal}
<script>
document.getElementById('gm_mkt_bonus_file').addEventListener('change', function (e) {
    var file = e.target.files[0];
    if (!file) return;
    var reader = new FileReader();
    reader.onload = function () { document.getElementById('gm_mkt_bonus_list').value = reader.result; };
    reader.readAsText(file, 'UTF-8');
});
</script>
{/literal}

{include file="common/subheader.tpl" title="Партии" target="#gm_mkt_batches"}
<div id="gm_mkt_batches" class="in collapse">
{if !$gm_mkt_batches}<p class="muted">Партий пока нет.</p>{/if}
{foreach from=$gm_mkt_batches item="b"}
    <a class="gm-mkt-card" href="{"gm_marketing.bonus_batch?batch_id=`$b.id`"|fn_url}" style="display: block; color: inherit; text-decoration: none;">
        <div class="gm-mkt-meta">
            <span class="gm-mkt-tag">#{$b.id}</span>
            <span>{$b.created_msk} МСК</span>
            <span class="gm-mkt-tag">{if $b.status == "preview"}Превью – не списано{elseif $b.status == "executed"}Ждём активации части клиентов{else}Завершено{/if}</span>
        </div>
        Клиентов: {$b.items_count} &#183; запрошено списать: {$b.total_requested_r} &#183; списано: {$b.total_deducted_r}
    </a>
{/foreach}
</div>
</div>
{/capture}
{include file="common/mainbox.tpl" title="Маркетинг-портал: бонусы" content=$smarty.capture.mainbox}
