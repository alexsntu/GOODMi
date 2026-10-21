{* GOODMi: поля мероприятия плана (добавление и правка) *}
<p><input type="text" name="plan[title]" value="{$item.title|default:""|escape}" placeholder="Название (например «Акция ко Дню знаний»)" required /></p>
<div class="gm-mkt-row">
    <label>Тип<br />
        <select name="plan[type]">
            {foreach from=$gm_mkt_plan_types key="k" item="label"}<option value="{$k}" {if $item.type == $k}selected="selected"{/if}>{$label}</option>{/foreach}
        </select>
    </label>
    <label>Начало<br /><input type="date" name="plan[start_date]" value="{$item.start_date|default:""}" /></label>
    <label>Конец<br /><input type="date" name="plan[end_date]" value="{$item.end_date|default:""}" /></label>
    <label>Каналы<br /><input type="text" name="plan[channels]" value="{$item.channels|default:""|escape}" placeholder="сайт, Telegram" /></label>
</div>
<p><textarea name="plan[description]" rows="3" placeholder="Что делаем, какие товары, цели (необязательно)">{$item.description|default:""|escape}</textarea></p>
