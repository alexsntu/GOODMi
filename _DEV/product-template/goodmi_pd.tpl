{script src="js/tygh/exceptions.js"}

{if $settings.ab__device !== "mobile"}

    {assign var="pd_image_gallery_width"  value=$settings.abt__ut2.products.view.image_width[$settings.ab__device]|default:$settings.Thumbnails.product_details_thumbnail_width|default:450}
    {assign var="pd_image_gallery_height" value=$settings.abt__ut2.products.view.image_height[$settings.ab__device]|default:$settings.Thumbnails.product_details_thumbnail_height|default:450}

    {$is_add_to_cart_mv=true}
    {$abt__shareb_mute=true}
    {if "MULTIVENDOR"|fn_allowed_for && ($product.master_product_id || !$product.company_id)}{$is_add_to_cart_mv=false}{/if}

    {hook name="products:ab__product_images_count"}
    {$product_images_count = $product.image_pairs|@count}
    {/hook}

{literal}
<style>
/* ═══════════════════════════════════════
   GOODMi — DNS-style product card
   ═══════════════════════════════════════ */

/* CSS variables (scoped to template root) */
.gm-pd {
    --gm-orange:  #FF6900;
    --gm-orange-h:#E55D00;
    --gm-text:    #191919;
    --gm-muted:   #6e6e6e;
    --gm-border:  #e5e5e5;
    --gm-bg:      #f7f7f7;
    --gm-r:       8px;
}

/* ── Full-width header: H1 + SKU ─────────────── */
.gm-pd__header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 20px;
}
.gm-pd h1 {
    font-size: 28px !important;
    font-weight: 700 !important;
    line-height: 1.3;
    color: var(--gm-text);
    margin: 0 !important;
    flex: 1;
}
.gm-pd .gm-pd__sku-wrap {
    flex-shrink: 0;
    padding-top: 8px;
    font-size: 12px;
    color: var(--gm-muted);
    white-space: nowrap;
}

/* ── 2-column layout ─────────────────────────── */
/* Use flex instead of grid so OWL carousel can
   read offsetWidth of ut2-pb__img-wrapper correctly */
.gm-pd .ut2-pb__section-combined {
    display: flex !important;
    align-items: flex-start !important;
    gap: 36px !important;
}
.gm-pd .ut2-pb__img-wrapper {
    flex: 0 0 50% !important;
    width: 50% !important;
    max-width: 50% !important;
    position: relative !important;   /* gallery scrolls normally */
    top: auto !important;
    border: none !important;
    box-shadow: none !important;
}
.gm-pd .ut2-pb__content-wrapper {
    flex: 1 !important;
    min-width: 0 !important;
    margin-left: 0 !important;
    margin-right: 0 !important;
    padding-left: 0 !important;
    padding-right: 0 !important;
    float: none !important;
    width: auto !important;
    /* Sticky: info column stays visible while scrolling through gallery */
    position: sticky !important;
    top: 16px !important;
}

/* ── Remove borders/shadows from gallery images ─ */
.gm-pd .ut2-pb__img-wrapper img.ty-pict,
.gm-pd .ut2-pb__img-wrapper .ty-product-img,
.gm-pd .ut2-pb__img-wrapper .cm-gallery-item,
.gm-pd .ut2-pb__img-wrapper .cm-thumbnails-mini,
.gm-pd .ut2-pb__img-wrapper .ty-product-thumbnails__item {
    border: none !important;
    box-shadow: none !important;
    outline: none !important;
}

/* ── remove outline box from content area ─────── */
.gm-pd .ut2-pb__main-content-box { border: none !important; padding: 0 !important; }

/* ── title area (H1 moved to header, so hide it here) ── */
.gm-pd .ut2-pb__title { display: none !important; }

/* ── features box ────────────────────────────── */
.gm-pd .ut2-pb__short-features {
    background: var(--gm-bg);
    border-radius: var(--gm-r);
    padding: 10px 14px;
    margin-bottom: 14px;
}
.gm-pd .ut2-pb__short-features .ut2-features-list {
    display: flex;
    flex-direction: column;
    margin: 0; padding: 0;
}
.gm-pd .ut2-pb__short-features .ut2-features-list .ty-control-group {
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    gap: 8px;
    padding: 4px 0;
    border-bottom: 1px solid rgba(0,0,0,.06);
    font-size: 12.5px;
    line-height: 1.4;
}
.gm-pd .ut2-pb__short-features .ut2-features-list .ty-control-group:last-child { border-bottom: none; }
.gm-pd .ut2-pb__short-features .ty-product-feature__label em { color: var(--gm-muted); font-style: normal; white-space: nowrap; }
.gm-pd .ut2-pb__short-features .ty-control-group > span:last-child em { color: var(--gm-text); font-weight: 500; font-style: normal; text-align: right; display: block; }
.gm-pd__features-link { display: inline-block; margin-top: 6px; font-size: 12px; color: var(--gm-orange); text-decoration: none; font-weight: 500; }
.gm-pd__features-link:hover { text-decoration: underline; }

/* ── rating ──────────────────────────────────── */
.gm-pd .gm-pd__rating {
    padding-bottom: 12px;
    border-bottom: 1px solid var(--gm-border);
    margin-bottom: 0;
}

/* ── price ───────────────────────────────────── */
.gm-pd .ty-product-prices { display: flex; align-items: baseline; flex-wrap: wrap; gap: 8px; }
.gm-pd .ty-price .ty-price-num { font-size: 28px; font-weight: 700; line-height: 1; color: var(--gm-text); }
.gm-pd .ty-list-price .ty-strike,
.gm-pd .ty-list-price .ut2-cost-base { font-size: 14px; font-weight: 400; color: var(--gm-muted); text-decoration: line-through; }
.gm-pd .ty-list-price .ty-price-num { font-size: 14px; font-weight: 400; color: var(--gm-muted); text-decoration: line-through; }
.gm-pd .ty-save-price { display: none; }

/* ── price + buy in one row (float approach) ─── */
/* Button floated right BEFORE price in HTML → price wraps alongside */
.gm-pd .ut2-pb__button.ty-product-block__button {
    float: right;
    width: 200px !important;
    max-width: 200px !important;
    margin-left: 16px;
    padding-top: 14px;
}
.gm-pd .gm-pd__price-area {
    overflow: hidden; /* BFC — sits next to the floated button */
    padding: 14px 0 10px;
}
/* Clear float before USP and options */
.gm-pd .gm-pd__usp { clear: both; }

/* Hide "В наличии" from price area — shown in USP strip instead */
.gm-pd .ut2-pb__price-wrap .stock-wrap,
.gm-pd .ut2-pb__price-wrap .ty-qty-in-stock { display: none !important; }

/* credit line */
.gm-pd__credit { font-size: 12px; color: var(--gm-muted); margin-top: 4px; }
.gm-pd__credit-val { color: var(--gm-orange); font-weight: 600; }

/* ── buy button ──────────────────────────────── */
.gm-pd .ty-btn__add-to-cart {
    background-color: var(--gm-orange) !important;
    border-color:     var(--gm-orange) !important;
    color: #fff !important;
    border-radius: var(--gm-r) !important;
    font-weight: 600 !important;
    white-space: nowrap;
}
.gm-pd .ty-btn__add-to-cart:hover {
    background-color: var(--gm-orange-h) !important;
    border-color:     var(--gm-orange-h) !important;
}

/* ── wishlist / compare ──────────────────────── */
.gm-pd .ut2-add-to-wish,
.gm-pd .ut2-add-to-compare {
    background: none !important;
    border: 1px solid var(--gm-border) !important;
    color: var(--gm-muted) !important;
    border-radius: var(--gm-r) !important;
}
.gm-pd .ut2-add-to-wish:hover,
.gm-pd .ut2-add-to-compare:hover {
    border-color: var(--gm-orange) !important;
    color: var(--gm-orange) !important;
}

/* ── USP strip ───────────────────────────────── */
.gm-pd__usp {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 4px;
    margin: 14px 0;
    padding: 10px 0;
    border-top: 1px solid var(--gm-border);
    border-bottom: 1px solid var(--gm-border);
}
.gm-pd__usp-item { display: flex; flex-direction: column; align-items: center; text-align: center; gap: 3px; padding: 5px 2px; }
.gm-pd__usp-icon { width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; }
.gm-pd__usp-icon svg { width: 22px; height: 22px; fill: var(--gm-orange); }
.gm-pd__usp-item--off .gm-pd__usp-icon svg { fill: #bbb; }
.gm-pd__usp-label { font-size: 11px; font-weight: 700; color: var(--gm-text); line-height: 1.2; }
.gm-pd__usp-item--off .gm-pd__usp-label { color: #bbb; }
.gm-pd__usp-sub { font-size: 10px; color: var(--gm-muted); line-height: 1.2; }

/* ── tablet ──────────────────────────────────── */
@media (max-width: 1024px) {
    .gm-pd .ut2-pb__section-combined { gap: 20px !important; }
    .gm-pd h1 { font-size: 22px !important; }
    .gm-pd .ut2-pb__img-wrapper { position: relative !important; top: auto !important; }
}
@media (max-width: 768px) {
    .gm-pd .ut2-pb__section-combined { flex-direction: column !important; }
    .gm-pd .ut2-pb__img-wrapper { flex: none !important; width: 100% !important; max-width: 100% !important; }
}
</style>
{/literal}

    <div class="gm-pd ut2-pb ty-product-block ut2-big-image --flat{if $product_images_count < 1} --single{/if}"
         style="--pd-image-gallery-width: {$pd_image_gallery_width};--pd-image-gallery-height: {$pd_image_gallery_height}">

        <div class="ut2-breadcrumbs__wrapper">
            {hook name="products:ut2_main_info_breadcrumbs"}
                {include file="common/breadcrumbs.tpl"}
            {/hook}
        </div>

        {hook name="products:view_main_info"}
        {if $product}
            {assign var="obj_id" value=$product.product_id}
            {include file="common/product_data.tpl" product=$product but_role="big" but_text=__("add_to_cart") product_labels_position="right-top" hide_qty_label=true}

            {if $settings.abt__ut2.general.brand_feature_id}
                {include file="blocks/product_templates/components/product_brand_logo_prepare.tpl"}
            {/if}

            {* ── FULL-WIDTH HEADER: H1 + SKU ── *}
            <div class="gm-pd__header">
                {if !$hide_title}
                    <h1 {live_edit name="product:product:{$product.product_id}"}>{$product.product nofilter}</h1>
                {/if}
                {if $show_sku == "true" && $product.product_code|trim}
                    <div class="gm-pd__sku-wrap">
                        {assign var="sku" value="sku_`$obj_id`"}
                        {$smarty.capture.$sku nofilter}
                    </div>
                {/if}
            </div>

            <div class="ut2-pb__wrapper clearfix">

                <div class="ut2-pb__section-combined">

                    {* ══ LEFT COLUMN: GALLERY ══ *}
                    <div class="ut2-pb__img-wrapper">
                        {hook name="products:image_wrap"}
                        {if !$no_images}
                            <div class="ut2-pb__img cm-reload-{$product.product_id} images-{$settings.abt__ut2.products[$settings.abt__details_layout].multiple_product_images[$settings.ab__device]|default:1}" data-ca-previewer="true" id="product_images_{$product.product_id}_update">
                                {include file="views/products/components/product_images.tpl" product=$product show_detailed_link="Y" image_width=$pd_image_gallery_width image_height=$pd_image_gallery_height lazy_load=false}
                                {hook name="products:ab__s_pictograms_pos_1"}{/hook}
                                <!--product_images_{$product.product_id}_update-->
                            </div>
                        {else}
                            {hook name="products:ab__s_pictograms_pos_1"}{/hook}
                        {/if}
                        {/hook}
                    </div>

                    {* ══ RIGHT COLUMN: INFO ══ *}
                    <div class="ut2-pb__content-wrapper">

                        {* H1 title area — hidden via CSS since H1 is in full-width header above *}
                        <div class="ut2-pb__title ut2-pb__title-wrap">
                            {if !$hide_title}
                                <h1 {live_edit name="product:product:{$product.product_id}"}>{$product.product nofilter}</h1>
                            {/if}
                            <div class="ut2-pb__inner-elements-wrap space-between">
                                <div class="ut2-pb__rating">{include file="blocks/product_templates/components/product_rating.tpl"}</div>
                                {if $show_sku == "true" && $product.product_code|trim}
                                    <div class="ut2-pb__sku">
                                        {assign var="sku2" value="sku_`$obj_id`"}
                                        {$smarty.capture.$sku2 nofilter}
                                    </div>
                                {/if}
                            </div>
                        </div>

                        {hook name="products:ab__deal_of_the_day_product_view"}{/hook}

                        {* ─ Short features (above form) ─ *}
                        {if $settings.abt__ut2.products.view.show_features[$settings.ab__device] === "YesNo::YES"|enum && $product.header_features}
                            <div class="ut2-pb__short-features">
                                {include file="views/products/components/product_features_short_list.tpl" features=$product.header_features}
                                <a href="#product_tabs_anchor" class="gm-pd__features-link">Все характеристики &#8250;</a>
                            </div>
                        {/if}

                        {* ─ Rating (outside form) ─ *}
                        <div class="gm-pd__rating">
                            {include file="blocks/product_templates/components/product_rating.tpl"}
                        </div>

                        <div class="ut2-pb__main-content-box">

                            {assign var="form_open" value="form_open_`$obj_id`"}
                            {$smarty.capture.$form_open nofilter}

                            {assign var="old_price"      value="old_price_`$obj_id`"}
                            {assign var="price"          value="price_`$obj_id`"}
                            {assign var="clean_price"    value="clean_price_`$obj_id`"}
                            {assign var="list_discount"  value="list_discount_`$obj_id`"}
                            {assign var="discount_label" value="discount_label_`$obj_id`"}

                            {* ─ Buy button floated right (must come BEFORE price in HTML for float:right to work) ─ *}
                            {if $capture_buttons}{capture name="buttons"}{/if}
                            <div class="ut2-pb__button ty-product-block__button">
                                {if $show_qty}
                                    <div class="ut2-qty__wrap {if $min_qty && $product.min_qty}min-qty{/if}">
                                        {if $capture_options_vs_qty}{capture name="product_options"}{$smarty.capture.product_options nofilter}{/if}
                                        {assign var="qty"     value="qty_`$obj_id`"}
                                        {$smarty.capture.$qty nofilter}
                                        {assign var="min_qty" value="min_qty_`$obj_id`"}
                                        {$smarty.capture.$min_qty nofilter}
                                        {if $capture_options_vs_qty}{/capture}{/if}
                                    </div>
                                {/if}
                                {if $show_details_button}
                                    {include file="buttons/button.tpl" but_href="products.view?product_id=`$product.product_id`" but_text=__("view_details") but_role="submit"}
                                {/if}
                                {assign var="add_to_cart"  value="add_to_cart_`$obj_id`"}
                                {$smarty.capture.$add_to_cart nofilter}
                                {assign var="list_buttons" value="list_buttons_`$obj_id`"}
                                {$smarty.capture.$list_buttons nofilter}
                            </div>
                            {if $capture_buttons}{/capture}{/if}

                            {* ─ Price + Credit (sits next to floated button) ─ *}
                            <div class="gm-pd__price-area">
                                {include file="blocks/product_templates/components/product_price.tpl"}
                                {if $product.price >= 2000}
                                    <div class="gm-pd__credit" data-price="{$product.price|string_format:"%.2f"}">
                                        от <span class="gm-pd__credit-val">&#8212;</span> &#8381;/мес. в кредит
                                    </div>
                                {/if}
                            </div>

                            {hook name="products:promo_text"}
                            {if $product.promo_text}
                                <div class="ut2-pb__note">{$product.promo_text nofilter}</div>
                            {/if}
                            {/hook}

                            {* ─ USP strip ─ *}
                            <div class="gm-pd__usp">
                                <div class="gm-pd__usp-item{if !$product.in_stock} gm-pd__usp-item--off{/if}">
                                    <div class="gm-pd__usp-icon">
                                        {if $product.in_stock}
                                            <svg viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
                                        {else}
                                            <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
                                        {/if}
                                    </div>
                                    <div class="gm-pd__usp-label">{if $product.in_stock}В наличии{else}Нет в наличии{/if}</div>
                                    <div class="gm-pd__usp-sub">{if $product.in_stock}в магазинах Крыма{else}уточните у менеджера{/if}</div>
                                </div>
                                <div class="gm-pd__usp-item">
                                    <div class="gm-pd__usp-icon">
                                        <svg viewBox="0 0 24 24"><path d="M20 8h-3V4H3c-1.1 0-2 .9-2 2v11h2c0 1.66 1.34 3 3 3s3-1.34 3-3h6c0 1.66 1.34 3 3 3s3-1.34 3-3h2v-5l-3-4zM6 18.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zm13.5-9l1.96 2.5H17V9.5h2.5zm-1.5 9c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5z"/></svg>
                                    </div>
                                    <div class="gm-pd__usp-label">Доставка СДЭК</div>
                                    <div class="gm-pd__usp-sub">по всей России</div>
                                </div>
                                <div class="gm-pd__usp-item">
                                    <div class="gm-pd__usp-icon">
                                        <svg viewBox="0 0 24 24"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg>
                                    </div>
                                    <div class="gm-pd__usp-label">Самовывоз</div>
                                    <div class="gm-pd__usp-sub">6 точек в Крыму</div>
                                </div>
                            </div>{* /gm-pd__usp *}

                            {* ─ Options ─ *}
                            {if $capture_options_vs_qty}{capture name="product_options"}{$smarty.capture.product_options nofilter}{/if}
                            <div class="ut2-pb__options">
                                {assign var="product_options" value="product_options_`$obj_id`"}
                                {$smarty.capture.$product_options nofilter}
                            </div>
                            {if $capture_options_vs_qty}{/capture}{/if}

                            <div class="ut2-pb__advanced-options">
                                {if $capture_options_vs_qty}{capture name="product_options"}{$smarty.capture.product_options nofilter}{/if}
                                {assign var="advanced_options" value="advanced_options_`$obj_id`"}
                                {$smarty.capture.$advanced_options nofilter}
                                {if $capture_options_vs_qty}{/capture}{/if}
                            </div>

                            {assign var="product_edp" value="product_edp_`$obj_id`"}
                            {$smarty.capture.$product_edp nofilter}

                            {hook name="products:ab__s_pictograms_pos_2"}{/hook}
                            {hook name="products:geo_maps"}{/hook}

                            {if $show_short_descr && strlen(trim($product.short_description))}
                                <div class="ut2-pb__short-descr" {live_edit name="product:short_description:{$product.product_id}"}>{$product.short_description nofilter}</div>
                            {/if}

                            {hook name="products:product_form_close_tag"}
                            {$form_close="form_close_`$obj_id`"}
                            {$smarty.capture.$form_close nofilter}
                            {/hook}

                            {if $show_product_tabs}
                                {include file="views/tabs/components/product_popup_tabs.tpl"}
                                {$smarty.capture.popupsbox_content nofilter}
                            {/if}

                        </div>{* /ut2-pb__main-content-box *}

                        {* Brand name *}
                        {if $settings.abt__ut2.general.brand_feature_id && $settings.abt__ut2.products.view.show_brand_format[$settings.ab__device] === "name"}
                            {if $brand_feature}
                                {hook name="products:brand"}
                                    <div class="ut2-pb__product-brand-name">
                                        {include file="views/products/components/product_features_short_list.tpl" features=array($brand_feature) no_container=true feature_image=false hide_name=true feature_link=true}
                                    </div>
                                {/hook}
                            {/if}
                        {/if}

                        {hook name="products:ab__vendor_block"}{/hook}
                        {hook name="products:ab__motivation_block"}{/hook}

                    </div>{* /ut2-pb__content-wrapper *}

                </div>{* /ut2-pb__section-combined *}

                <div class="ut2-pb__tabs-wrapper" id="product_tabs_anchor">
                    {if $settings.abt__ut2.products.custom_block_id|intval}
                        <div class="ut2-pb__custom-block">
                            {render_block block_id=$settings.abt__ut2.products.custom_block_id|intval dispatch="products.view" use_cache=false parse_js=false}
                        </div>
                    {/if}
                    {hook name="products:buy_together"}{/hook}
                    {hook name="products:product_tabs_pre"}
                        <div class="ut2-pb__tabs{if $settings.Appearance.product_details_in_tab === "YesNo::NO"|enum} tabs-list{/if}">
                            {if $show_product_tabs}
                                {hook name="products:product_tabs"}
                                    {include file="views/tabs/components/product_tabs.tpl"}
                                    {if $blocks.$tabs_block_id.properties.wrapper}
                                        {include file=$blocks.$tabs_block_id.properties.wrapper content=$smarty.capture.tabsbox_content title=$blocks.$tabs_block_id.description}
                                    {else}
                                        {$smarty.capture.tabsbox_content nofilter}
                                    {/if}
                                {/hook}
                            {/if}
                        </div>
                    {/hook}
                </div>

                {hook name="products:product_detail_bottom"}{/hook}

            </div>{* /ut2-pb__wrapper *}

        {/if}
        {/hook}

        {if $smarty.capture.hide_form_changed == "Y"}
            {assign var="hide_form" value=$smarty.capture.orig_val_hide_form}
        {/if}

        {hook name="products:bottom_product_layer"}{/hook}
    </div>

    <div class="product-details"></div>
    {capture name="mainbox_title"}{assign var="details_page" value=true}{/capture}

{literal}
<script>
(function () {
    var ANNUAL_RATE = 0.75, TERM = 12;
    function calcMonthly(price) {
        var r = ANNUAL_RATE / 12;
        var p = Math.pow(1 + r, TERM);
        return Math.ceil(price * (r * p) / (p - 1));
    }
    function fmt(v) { return v.toLocaleString('ru-RU', { maximumFractionDigits: 0 }); }
    function renderCredit() {
        var els = document.querySelectorAll('.gm-pd__credit[data-price]');
        for (var i = 0; i < els.length; i++) {
            var price = parseFloat(els[i].getAttribute('data-price'));
            if (!price || price < 2000) { els[i].style.display = 'none'; continue; }
            var span = els[i].querySelector('.gm-pd__credit-val');
            if (span) span.textContent = fmt(calcMonthly(price));
        }
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', renderCredit);
    else renderCredit();
}());
</script>
{/literal}

{else}
    {include file="blocks/product_templates/components/abt__ut2_mobile_template.tpl" features=$product.header_features}
{/if}
