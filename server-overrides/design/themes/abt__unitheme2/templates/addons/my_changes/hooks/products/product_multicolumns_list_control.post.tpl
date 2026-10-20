{* Maxmobiles (company_id = 1): «Быстрый заказ» в плитке каталога рядом с «В корзину» – то же штатное окно модуля
   call_requests, что в карточке (тема выводит его только на странице товара). Не для товаров без цены, условия наличия –
   как в addons/call_requests/hooks/products/add_to_cart.post.tpl темы. 2026-10-20 *}
{if $runtime.company_id == 1
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
{ab__hide_content bot_type="ALL"}
    {include file="common/popupbox.tpl"
        href="call_requests.request?product_id={$product.product_id}&obj_prefix={$obj_prefix}"
        link_text=__("call_requests.buy_now_with_one_click")
        text=__("call_requests.buy_now_with_one_click")
        link_icon=""
        id="buy_now_with_one_click_{$obj_prefix}{$product.product_id}"
        link_meta="ty-btn ty-btn__outline mm-gl-quick cm-dialog-destroy-on-close"
        content=""
        dialog_additional_attrs=["data-ca-product-id" => $product.product_id, "data-ca-dialog-purpose" => "call_request"]
    }
{/ab__hide_content}
{/if}
