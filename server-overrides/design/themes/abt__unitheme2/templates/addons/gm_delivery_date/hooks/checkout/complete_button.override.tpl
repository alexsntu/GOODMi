{* GOODMi (company_id = 2): кнопки страницы «Заказ оформлен» (checkout.complete, общий шаблон responsive views/checkout/complete.tpl) –
   «Все заказы» ведёт в «Мои заказы» личного кабинета (gm_cabinet.orders, /cabinet/), а не в старый список orders.search.
   Остальное – как в штатном шаблоне. На других витринах вывод пустой, и CS-Cart показывает штатные кнопки (или подмену своего модуля). *}
{if $runtime.company_id == 2}
            <div class="ty-checkout-complete__buttons-left">
                {if $order_info}
                    {if $order_info.child_ids}
                        {include file="buttons/button.tpl" but_meta="ty-btn__secondary" but_text=__("order_details") but_href="orders.search?period=A&order_id=`$order_info.child_ids`"}
                    {else}
                        {include file="buttons/button.tpl" but_text=__("order_details") but_meta="ty-btn__secondary" but_href="orders.details?order_id=`$order_info.order_id`"}
                    {/if}
                {/if}
                &nbsp;{include file="buttons/button.tpl" but_meta="ty-btn__secondary" but_text=__("view_orders") but_href="gm_cabinet.orders"}
            </div>
            <div class="ty-checkout-complete__buttons-right">
                {include file="buttons/continue_shopping.tpl" but_role="text" but_meta="ty-checkout-complete__button-vmid" but_href=$continue_url|fn_url}
            </div>
{/if}
