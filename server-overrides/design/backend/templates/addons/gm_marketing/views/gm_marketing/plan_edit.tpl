{* GOODMi: «Маркетинг-портал → План» – правка мероприятия (2026-10-21) *}
{capture name="mainbox"}
{include file="addons/gm_marketing/views/gm_marketing/components/nav.tpl"}
<div class="gm-mkt">
<form action="{""|fn_url}" method="post" class="gm-mkt-form">
    <input type="hidden" name="id" value="{$gm_mkt_item.id}" />
    {include file="addons/gm_marketing/views/gm_marketing/components/plan_form.tpl" item=$gm_mkt_item}
    <p>
        <button type="submit" class="btn btn-primary" name="dispatch[gm_marketing.update_plan]">Сохранить</button>
        <a class="btn" href="{"gm_marketing.plan"|fn_url}">Отмена</a>
    </p>
</form>
</div>
{/capture}
{include file="common/mainbox.tpl" title="Маркетинг-портал: мероприятие плана" content=$smarty.capture.mainbox}
