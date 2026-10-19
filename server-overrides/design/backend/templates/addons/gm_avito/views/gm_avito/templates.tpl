{* GOODMi: «Товары → Авито: шаблоны категорий» (2026-10-19), контроллер – app/addons/gm_avito/controllers/backend/gm_avito.php *}
{capture name="mainbox"}
{if !$gm_avito_allowed}
    <p>Выберите витрину GOODMi или Maxmobiles в переключателе витрин вверху админки.</p>
{else}
<form action="{""|fn_url}" method="post" name="gm_avito_templates_form" class="form-edit">
    <p class="muted">Шаблон задаёт категорию Авито и её поля для категории сайта и всех вложенных (действует ближайший). Поля &#8211; по строке «Поле = значение». Отмеченные поля берутся из характеристик товара (бренд, модель, цвет). Поля, перенесённые из UnitXML в само объявление, важнее шаблона.</p>
    <div class="table-responsive-wrapper">
    <table class="table table-middle table--relative">
        <thead><tr><th width="28%">Категория сайта</th><th width="47%">Поля Авито</th><th width="15%">Из характеристик</th><th width="10%">Товаров</th></tr></thead>
        <tbody>
        {foreach from=$gm_avito_templates item="t"}
            <tr>
                <td><strong>{$t.path}</strong><br /><span class="muted">id {$t.category_id}{if $t.note} &#183; {$t.note}{/if}</span><br />
                    <label class="checkbox"><input type="checkbox" name="templates[{$t.category_id}][delete]" value="Y" /> удалить</label></td>
                <td><textarea name="templates[{$t.category_id}][fields]" rows="4" class="input-full">{$t.fields}</textarea></td>
                <td>{foreach from=$gm_avito_auto_fields item="f"}<label class="checkbox"><input type="checkbox" name="templates[{$t.category_id}][auto_fields][]" value="{$f}" {if $f|in_array:$t.auto_fields}checked="checked"{/if} /> {$f}</label>{/foreach}</td>
                <td>{$t.enabled} из {$t.total}<br /><span class="muted">включено</span></td>
            </tr>
        {/foreach}
        </tbody>
    </table>
    </div>

    {include file="common/subheader.tpl" title="Добавить шаблон" target="#gm_avito_new_template"}
    <div id="gm_avito_new_template" class="in collapse form-horizontal">
        <div class="control-group">
            <label class="control-label" for="gm_avito_new_cat">Id категории сайта</label>
            <div class="controls"><input type="text" id="gm_avito_new_cat" name="new_template_category_id" class="input-small" /></div>
        </div>
        <div class="control-group">
            <label class="control-label" for="gm_avito_new_fields">Поля Авито</label>
            <div class="controls"><textarea id="gm_avito_new_fields" name="new_template_fields" rows="4" class="span9" placeholder="Category = Бытовая техника"></textarea></div>
        </div>
    </div>

    {if $gm_avito_missing}
    {include file="common/subheader.tpl" title="Категории без шаблона (активные товары)" target="#gm_avito_missing"}
    <div id="gm_avito_missing" class="in collapse">
        <table class="table table-middle"><thead><tr><th>Категория сайта</th><th>Id</th><th>Товаров</th></tr></thead><tbody>
        {foreach from=$gm_avito_missing item="m"}<tr><td>{$m.path}</td><td>{$m.category_id}</td><td>{$m.count}</td></tr>{/foreach}
        </tbody></table>
    </div>
    {/if}

    <div class="buttons-container">
        <input type="submit" class="btn btn-primary" name="dispatch[gm_avito.update_templates]" value="Сохранить шаблоны" />
    </div>
</form>
{/if}
{/capture}
{include file="common/mainbox.tpl" title="Авито: шаблоны категорий" content=$smarty.capture.mainbox}
