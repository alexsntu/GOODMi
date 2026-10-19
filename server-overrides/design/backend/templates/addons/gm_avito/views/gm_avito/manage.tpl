{* GOODMi: «Товары → Авито: настройки» (2026-10-19), контроллер – app/addons/gm_avito/controllers/backend/gm_avito.php *}
{capture name="mainbox"}
{include file="addons/gm_avito/views/gm_avito/components/nav.tpl"}
{if !$gm_avito_allowed}
    <p>Выберите витрину GOODMi или Maxmobiles в переключателе витрин вверху админки &#8211; у каждой свои настройки и свой аккаунт Авито.</p>
{else}
<form action="{""|fn_url}" method="post" name="gm_avito_form" class="form-horizontal form-edit">
    {include file="common/subheader.tpl" title="Какие товары выгружать" target="#gm_avito_stock"}
    <div id="gm_avito_stock" class="in collapse">
        <div class="control-group">
            <label class="control-label">Выгружать на Авито</label>
            <div class="controls">
                <label class="radio"><input type="radio" name="gm_avito[stock_mode]" value="in_stock" {if $gm_avito.stock_mode == "in_stock"}checked="checked"{/if} /> только включённые товары с остатком (закончился &#8211; объявление снимается, поступил &#8211; возвращается)</label>
                <label class="radio"><input type="radio" name="gm_avito[stock_mode]" value="out_of_stock" {if $gm_avito.stock_mode == "out_of_stock"}checked="checked"{/if} /> только включённые товары без остатка</label>
                <label class="radio"><input type="radio" name="gm_avito[stock_mode]" value="all" {if $gm_avito.stock_mode == "all"}checked="checked"{/if} /> все включённые товары</label>
                <p class="muted description">Включается у товара на вкладке «Авито». Выключенные и скрытые на сайте товары не выгружаются никогда. Сейчас включено {$gm_avito.items_enabled} из {$gm_avito.items_total} товаров.</p>
            </div>
        </div>
    </div>

    {include file="common/subheader.tpl" title="Текст объявлений" target="#gm_avito_text"}
    <div id="gm_avito_text" class="in collapse">
        <div class="control-group">
            <label class="control-label">Описание</label>
            <div class="controls">
                <label class="radio"><input type="radio" name="gm_avito[text_mode]" value="old" {if $gm_avito.text_mode == "old"}checked="checked"{/if} /> прежнее описание из UnitXML (у новых объявлений &#8211; новое)</label>
                <label class="radio"><input type="radio" name="gm_avito[text_mode]" value="categories" {if $gm_avito.text_mode == "categories"}checked="checked"{/if} /> новое в выбранных категориях, в остальных прежнее</label>
                <label class="radio"><input type="radio" name="gm_avito[text_mode]" value="new" {if $gm_avito.text_mode == "new"}checked="checked"{/if} /> новое у всех</label>
                <p class="muted description">Новое описание = «о товаре» (вкладка «Авито» в карточке) + характеристики + общий блок ниже.</p>
            </div>
        </div>
        <div class="control-group">
            <label class="control-label">Категории с новым описанием</label>
            <div class="controls">
                <details class="gm-avito-picker">
                    <summary>Выбрано: {$gm_avito.new_text_count}{if $gm_avito.new_text_names} &#8211; {$gm_avito.new_text_names}{/if} <span class="muted">(нажмите, чтобы изменить)</span></summary>
                    <input type="text" class="input-xlarge gm-avito-filter" placeholder="Найти категорию" data-target="gm_avito_new_text_list" />
                    <div id="gm_avito_new_text_list" style="max-height: 320px; overflow-y: auto; border: 1px solid #ddd; padding: 6px 10px; margin-top: 6px;">
                        {foreach from=$gm_avito.templates key="category_id" item="path"}
                            <label class="checkbox gm-avito-filter-item"><input type="checkbox" name="gm_avito[new_text_categories][]" value="{$category_id}" {if $category_id|in_array:$gm_avito.new_text_categories}checked="checked"{/if} /> {$path}</label>
                        {/foreach}
                    </div>
                </details>
            </div>
        </div>
        <div class="control-group">
            <label class="control-label" for="gm_avito_common">Общий блок магазина</label>
            <div class="controls">
                <textarea id="gm_avito_common" name="gm_avito[common_block]" rows="14" class="span9">{$gm_avito.common_block}</textarea>
                <p class="muted description">Добавляется в конец каждого нового описания. Авито понимает теги p, br, strong, em, ul, ol, li; ссылки и телефоны в тексте не указывать. {literal}{warranty}{/literal} &#8211; срок гарантии категории товара; если срок неизвестен, строка списка с ним убирается.</p>
            </div>
        </div>
        <div class="control-group">
            <label class="control-label" for="gm_avito_specs">Характеристик в описании</label>
            <div class="controls"><input type="text" id="gm_avito_specs" name="gm_avito[specs_limit]" value="{$gm_avito.specs_limit}" class="input-mini" /> <span class="muted">сначала главные (те, что под названием товара)</span></div>
        </div>
    </div>

    {include file="common/subheader.tpl" title="Объявление" target="#gm_avito_ad_defaults"}
    <div id="gm_avito_ad_defaults" class="in collapse">
        <div class="control-group">
            <label class="control-label" for="gm_avito_defaults">Поля по умолчанию</label>
            <div class="controls">
                <textarea id="gm_avito_defaults" name="gm_avito[defaults]" rows="6" class="span9">{$gm_avito.defaults}</textarea>
                <p class="muted description">По строке «Поле = значение» (названия полей &#8211; как в справочнике Авито). Шаблон категории и поля самого объявления важнее.</p>
            </div>
        </div>
        <div class="control-group">
            <label class="control-label" for="gm_avito_images">Фото в объявлении</label>
            <div class="controls"><input type="text" id="gm_avito_images" name="gm_avito[images_limit]" value="{$gm_avito.images_limit}" class="input-mini" /> <span class="muted">до 10, из карточки товара на сайте</span></div>
        </div>
    </div>

    {include file="common/subheader.tpl" title="Расписание" target="#gm_avito_schedule"}
    <div id="gm_avito_schedule" class="in collapse">
        <div class="control-group">
            <label class="control-label">Модуль</label>
            <div class="controls"><p>Файл собирается каждый час в :{$gm_avito.cron.build|string_format:"%02d"}, отчёт Авито забирается каждый час в :{$gm_avito.cron.sync|string_format:"%02d"}.</p></div>
        </div>
        <div class="control-group">
            <label class="control-label">Кабинет Авито</label>
            <div class="controls">
                {if $gm_avito.profile.time}
                    <p>Автозагрузка {if $gm_avito.profile.autoload_enabled}включена{else}<strong>выключена</strong>{/if}; Авито забирает файл в часы: {$gm_avito.profile_hours|default:"&#8211;"}{if $gm_avito.profile.schedule.0.rate}, лимит {$gm_avito.profile.schedule.0.rate} объявлений{/if}.</p>
                    <p>В кабинете указан файл: <strong>{$gm_avito.profile_feed|default:"&#8211;"}</strong>.</p>
                    <p class="muted">Расписание меняется в кабинете Авито; данные обновляются вместе с отчётом.</p>
                {else}
                    <p class="muted">Появится после первого получения отчёта Авито.</p>
                {/if}
            </div>
        </div>
    </div>

    {include file="common/subheader.tpl" title="Файл для Авито" target="#gm_avito_file"}
    <div id="gm_avito_file" class="in collapse">
        <div class="control-group">
            <label class="control-label">Ссылка</label>
            <div class="controls"><input type="text" readonly="readonly" value="{$gm_avito.feed_url}" class="span9" onclick="this.select()" />
                <p class="muted description">Её указывают в кабинете Авито вместо ссылки UnitXML. Не публикуйте &#8211; в ней ключ доступа.</p></div>
        </div>
        <div class="control-group">
            <label class="control-label">Последняя сборка</label>
            <div class="controls">
                {if $gm_avito.last_build.time}
                    <p>{$gm_avito.last_build.time|date_format:"`$settings.Appearance.date_format`, `$settings.Appearance.time_format`"}: объявлений {$gm_avito.last_build.ads}, не попали {$gm_avito.last_build.skipped}</p>
                    {if $gm_avito.last_build.reasons}<ul>{foreach from=$gm_avito.last_build.reasons key="reason" item="count"}<li>{$reason}: {$count}</li>{/foreach}</ul>{/if}
                {else}<p>ещё не собирался</p>{/if}
                {if $gm_avito.no_template_products}<p class="muted">Товаров в категориях без шаблона Авито: {$gm_avito.no_template_products} (Товары &#8594; Авито: шаблоны категорий).</p>{/if}
            </div>
        </div>
    </div>

    <div class="buttons-container">
        <input type="submit" class="btn btn-primary" name="dispatch[gm_avito.update]" value="Сохранить" />
        <input type="submit" class="btn" name="dispatch[gm_avito.build]" value="Собрать файл сейчас" />
        <input type="submit" class="btn" name="dispatch[gm_avito.fill]" value="Заполнить описания автоматически" title="Новым товарам и тем, у кого описание не правили вручную" />
    </div>
</form>
{/if}
{literal}
<script>
document.addEventListener('input', function (e) {
    if (!e.target.classList || !e.target.classList.contains('gm-avito-filter')) return;
    var q = e.target.value.toLowerCase().trim();
    var box = document.getElementById(e.target.getAttribute('data-target'));
    if (!box) return;
    box.querySelectorAll('.gm-avito-filter-item').forEach(function (el) {
        el.style.display = !q || el.textContent.toLowerCase().indexOf(q) !== -1 ? '' : 'none';
    });
});
</script>
{/literal}
{/capture}
{if $gm_avito.company}{$gm_avito_title = "Авито: настройки &#8211; `$gm_avito.company`"}{else}{$gm_avito_title = "Авито: настройки"}{/if}
{include file="common/mainbox.tpl" title=$gm_avito_title content=$smarty.capture.mainbox}
