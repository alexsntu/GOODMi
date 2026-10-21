{* GOODMi: кнопка смены статуса записи (type: draft, topic, promo_idea, repost, task, plan) – gm_marketing.set_status, после – обратно на эту страницу *}
<form action="{""|fn_url}" method="post">
    <input type="hidden" name="type" value="{$type}" />
    <input type="hidden" name="id" value="{$id}" />
    <input type="hidden" name="status" value="{$status}" />
    <input type="hidden" name="redirect_url" value="{$config.current_url}" />
    <button type="submit" class="btn btn-small {$class|default:""}" name="dispatch[gm_marketing.set_status]">{$label}</button>
</form>
