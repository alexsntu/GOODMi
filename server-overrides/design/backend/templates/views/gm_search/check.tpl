{* GOODMi: свой поиск – проверка запроса (dispatch=gm_search.check&q=). Данные – gm_search_explain, gm_search_suggest. *}
{capture name="mainbox"}
{include file="views/gm_search/components/tabs.tpl"}
<form action="{""|fn_url}" method="get" class="form-inline" style="margin-bottom:14px">
    <input type="hidden" name="dispatch" value="gm_search.check">
    <input type="text" name="q" value="{$gm_q}" placeholder="Запрос, как его пишут покупатели" class="input-xlarge" autofocus> <button type="submit" class="btn btn-primary">Проверить</button>
</form>
{if !$gm_engine}<p class="text-error">Поисковый движок не отвечает (служба manticore на сервере).</p>{/if}
{if $gm_e}
{$e = $gm_e}
<table class="table table-middle" style="max-width:900px">
<tbody>
<tr><td style="width:220px">После обработки</td><td><b>{$e.norm}</b></td></tr>
<tr><td>Как нашлось</td><td>{$e.mode_text} <span class="muted">({$e.mode})</span>, найдено <b>{$e.total}</b></td></tr>
<tr><td>Синонимы</td><td>{$e.synonyms_text|default:"нет"}</td></tr>
{if $e.translit}<tr><td>Латиницей</td><td>{$e.translit}</td></tr>{/if}
<tr><td>Слова в каталоге</td><td>{foreach $e.keywords as $k}<span class="gm-st-f">{$k.word}{if $k.form != $k.word} → {$k.form}{/if}: {if $k.docs}{$k.docs} тов.{else}<span class="label label-important">нет в каталоге</span>{/if}</span>&nbsp;&nbsp; {/foreach}</td></tr>
<tr><td>Выражение для движка</td><td><code>{$e.expr}</code></td></tr>
{if $gm_sg.queries}<tr><td>Подсказки</td><td>{foreach $gm_sg.queries as $s}{$s.text}{if !$s@last} · {/if}{/foreach}</td></tr>{/if}
</tbody></table>
<h4>Выдача и почему товары стоят в таком порядке</h4>
<p class="gm-st-note">Сначала товары в наличии, затем по оценке: совпадение слов × множители. Совпадение выше, если слова идут подряд и с начала названия; название весит больше бренда, модели и категории.</p>
<table class="table table-middle">
<thead><tr><th>#</th><th>Товар</th><th class="right">Совпадение</th><th>Множители</th><th class="right">Оценка</th></tr></thead>
<tbody>
{foreach $e.items as $i}<tr>
    <td>{$i.pos}</td>
    <td><a href="{$i.url}" target="_blank">{$i.name}</a> <span class="muted">#{$i.id}</span>{if !$i.in_stock} <span class="label">нет в наличии</span>{/if}</td>
    <td class="right">{$i.w}</td>
    <td class="gm-st-f">{foreach $i.factors as $fname => $fv}{if $fv != 1}{$fname} ×{$fv}{if !$fv@last}; {/if}{/if}{/foreach}{if $i.order}<br><b>{$i.order}</b>{/if}</td>
    <td class="right">{$i.score}</td>
</tr>{foreachelse}<tr><td colspan="5" class="no-items">Ничего не найдено</td></tr>{/foreach}
</tbody></table>
{/if}
{/capture}
{include file="common/mainbox.tpl" title="Поиск GOODMi: проверка запроса" content=$smarty.capture.mainbox}
