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
            <label class="control-label">Объявление</label>
            <div class="controls">
                <p>Id в файле: {$gm_avito.ad_id}{if $gm_avito.avito_id} &#183; номер на Авито: {$gm_avito.avito_id}{/if} &#183; цена: {$gm_avito.price|format_price:$currencies.$primary_currency nofilter} &#183; фото: {$gm_avito.images}</p>
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
