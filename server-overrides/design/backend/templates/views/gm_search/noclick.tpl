{* GOODMi: свой поиск – раздел «Нашли, но не кликнули» (dispatch=gm_search.noclick). Данные – gm_stats_queries. *}
{capture name="mainbox"}
{include file="views/gm_search/components/tabs.tpl"}
<p class="gm-st-note">Нулевые клики: товары показаны, но ни один не открыли – выдача не та. Главный сигнал для улучшения порядка.</p>
<form action="{""|fn_url}" method="get" class="form-inline" style="margin-bottom:12px">
    <input type="hidden" name="dispatch" value="gm_search.noclick"><input type="hidden" name="days" value="{$gm_days}"><input type="hidden" name="sort" value="{$gm_sort}">
    <input type="text" name="f" value="{$gm_filter}" placeholder="Фильтр по запросу" class="input-large"> <button type="submit" class="btn">Показать</button>
</form>
{include file="views/gm_search/components/queries_table.tpl" rows=$gm_rows sortable=true}
{/capture}
{include file="common/mainbox.tpl" title="Поиск GOODMi: Нашли, но не кликнули" content=$smarty.capture.mainbox}
