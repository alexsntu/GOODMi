{* Maxmobiles (company_id = 1): «Быстрый заказ» в плитке каталога рядом с «В корзину» – то же штатное окно модуля
   call_requests, что в карточке (тема выводит его только на странице товара). Не для товаров без цены, условия наличия –
   как в addons/call_requests/hooks/products/add_to_cart.post.tpl темы. 2026-10-20
   GOODMi (company_id = 2) – то же с 2026-10-20: класс gm-gl-quick (стили – секция 71 «Своего CSS» GOODMi), подпись «Быстрый заказ»
   (штатная «Купить в один клик» длинная для плитки; заголовок окна – штатный). *}
{if ($runtime.company_id == 1 || $runtime.company_id == 2)
    && $addons.call_requests.status == "ObjectStatuses::ACTIVE"|enum
    && $addons.call_requests.buy_now_with_one_click == "YesNo::YES"|enum
    && ($auth.user_id || $settings.Checkout.allow_anonymous_shopping == "allow_shopping")
    && $product.price > 0
    && (
        $settings.General.inventory_tracking == "YesNo::NO"|enum
        || $settings.General.allow_negative_amount == "YesNo::YES"|enum
        || $product.amount > 0
        || $product.tracking == "ProductTracking::DO_NOT_TRACK"|enum
        || $product.out_of_stock_actions == "OutOfStockActions::BUY_IN_ADVANCE"|enum
    )
}
{if $runtime.company_id == 2}
    {$gl_quick_text = "Быстрый заказ"}
    {$gl_quick_meta = "ty-btn gm-gl-quick cm-dialog-destroy-on-close"}
{else}
    {$gl_quick_text = __("call_requests.buy_now_with_one_click")}
    {$gl_quick_meta = "ty-btn ty-btn__outline mm-gl-quick cm-dialog-destroy-on-close"}
{/if}
{ab__hide_content bot_type="ALL"}
    {include file="common/popupbox.tpl"
        href="call_requests.request?product_id={$product.product_id}&obj_prefix={$obj_prefix}"
        link_text=$gl_quick_text
        text=__("call_requests.buy_now_with_one_click")
        link_icon=""
        id="buy_now_with_one_click_{$obj_prefix}{$product.product_id}"
        link_meta=$gl_quick_meta
        content=""
        dialog_additional_attrs=["data-ca-product-id" => $product.product_id, "data-ca-dialog-purpose" => "call_request"]
    }
{/ab__hide_content}
{/if}
