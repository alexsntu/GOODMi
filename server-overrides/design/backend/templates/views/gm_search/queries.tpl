{* GOODMi: свой поиск – раздел «Запросы» (dispatch=gm_search.queries). Данные – gm_stats_queries. *}
{capture name="mainbox"}
{include file="views/gm_search/components/tabs.tpl"}
<p class="gm-st-note">Все запросы покупателей за период. Нажмите на запрос, чтобы проверить, что и почему показывает поиск.</p>
<form action="{""|fn_url}" method="get" class="form-inline" style="margin-bottom:12px">
    <input type="hidden" name="dispatch" value="gm_search.queries"><input type="hidden" name="days" value="{$gm_days}"><input type="hidden" name="sort" value="{$gm_sort}">
    <input type="text" name="f" value="{$gm_filter}" placeholder="Фильтр по запросу" class="input-large"> <button type="submit" class="btn">Показать</button>
</form>
{include file="views/gm_search/components/queries_table.tpl" rows=$gm_rows sortable=true}
{/capture}
{include file="common/mainbox.tpl" title="Поиск GOODMi: Запросы" content=$smarty.capture.mainbox}
