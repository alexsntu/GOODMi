{* GOODMi: вкладка «Авито» в карточке товара (2026-10-19), данные – fn_gm_avito_admin_item, сохранение – хук update_product_post *}
{if $gm_avito}
<div class="hidden" id="content_gm_avito">
    {include file="common/subheader.tpl" title="Объявление на Авито" target="#gm_avito_ad"}
    <div id="gm_avito_ad" class="in collapse">
        <div class="control-group">
            <label class="control-label" for="gm_avito_enabled">Продавать на Авито</label>
            <div class="controls">
                <input type="hidden" name="product_data[gm_avito][enabled]" value="N" />
                <input type="checkbox" id="gm_avito_enabled" name="product_data[gm_avito][enabled]" value="Y" {if $gm_avito.enabled == "Y"}checked="checked"{/if} />
                <p class="muted description">{$gm_avito.state} Выгружаются {$gm_avito.stock_mode} (Товары &#8594; Авито: настройки).</p>
            </div>
        </div>

        <div class="control-group">
            <label class="control-label" for="gm_avito_title">Заголовок</label>
            <div class="controls">
                <input type="text" id="gm_avito_title" name="product_data[gm_avito][title]" value="{$gm_avito.title}" class="input-large span9" />
                <p class="muted description">Знаков: {$gm_avito.title_len} из 50{if $gm_avito.title_len > 50} &#8211; длиннее, чем показывает Авито{/if}.{if $gm_avito.title_auto == "Y"} Составлен автоматически из названия товара.{/if}</p>
            </div>
        </div>

        <div class="control-group">
            <label class="control-label" for="gm_avito_intro">Описание для Авито: о товаре</label>
            <div class="controls">
                <textarea id="gm_avito_intro" name="product_data[gm_avito][intro]" rows="7" class="span9">{$gm_avito.intro}</textarea>
                <p class="muted description">{if $gm_avito.intro_auto == "Y"}Заполнено автоматически из описания и характеристик товара на сайте; после вашей правки больше не перезаписывается.{else}Исправлено вручную.{/if} Каждая строка &#8211; отдельный абзац. Характеристики и общий блок магазина добавляются сами.</p>
                <label class="checkbox"><input type="checkbox" name="product_data[gm_avito][reset_auto]" value="Y" /> Заполнить заголовок и описание заново автоматически</label>
            </div>
        </div>

        <div class="control-group">
            <label class="control-label">Категория Авито</label>
            <div class="controls"><p>{$gm_avito.avito_category}</p></div>
        </div>
        <div class="control-group">
            <label class="control-label" for="gm_avito_fields">Поля объявления</label>
            <div class="controls">
                <textarea id="gm_avito_fields" name="product_data[gm_avito][fields]" rows="5" class="span9" placeholder="Поле = значение">{$gm_avito.own_fields}</textarea>
                <p class="muted description">
                    {if $gm_avito.imported}Перенесены из UnitXML и используются как есть (шаблон категории к ним не добавляется).
                    {else}Только то, что отличается у этого товара; остальное берётся из шаблона категории.{/if}
                    {if $gm_avito.required} Обязательные поля раздела: {$gm_avito.required}.{/if}
                    {if $gm_avito.missing} <strong>Не заполнено: {$gm_avito.missing}</strong> &#8211; без них объявление не выгружается.{/if}
                </p>
                {if $gm_avito.template_fields && !$gm_avito.imported}<p class="muted">Шаблон категории: {$gm_avito.template_fields|nl2br nofilter}{if $gm_avito.template_auto}<br />Из характеристик товара: {$gm_avito.template_auto}{/if}</p>{/if}
            </div>
        </div>
        <div class="control-group">
            <label class="control-label">Объявление</label>
            <div class="controls">
                <p>Id в файле: {$gm_avito.ad_id}{if $gm_avito.avito_id} &#183; номер на Авито: {if $gm_avito.report.url}<a href="{$gm_avito.report.url}" target="_blank">{$gm_avito.avito_id}</a>{else}{$gm_avito.avito_id}{/if}{/if} &#183; цена: {$gm_avito.price|format_price:$currencies.$primary_currency nofilter} &#183; фото: {$gm_avito.images}</p>
                {if $gm_avito.report_status}
                    <p>Авито, последняя загрузка: <strong>{$gm_avito.report.section}</strong>{if $gm_avito.report.avito_status} &#183; статус: {$gm_avito.report.avito_status}{/if}{if $gm_avito.report.date_end} &#183; размещено до {$gm_avito.report.date_end}{/if}</p>
                    {foreach from=$gm_avito.report.messages item="m"}
                        <p class="{if $m.type == "error"}text-error{else}text-warning{/if}">{if $m.type == "error"}Ошибка{else}Предупреждение{/if}: {$m.title} {$m.text}</p>
                    {/foreach}
                {/if}
                <p class="muted">Текст в файле: {if $gm_avito.new_text}новое описание (о товаре + характеристики + общий блок){else}прежнее описание из UnitXML &#8211; новое включается в настройках Авито{/if}.</p>
            </div>
        </div>

        {include file="common/subheader.tpl" title="Так объявление увидят на Авито" target="#gm_avito_preview"}
        <div id="gm_avito_preview" class="in collapse">
            <h4>{$gm_avito.title}</h4>
            <div class="well">{$gm_avito.preview nofilter}</div>
        </div>
    </div>
</div>
{/if}
