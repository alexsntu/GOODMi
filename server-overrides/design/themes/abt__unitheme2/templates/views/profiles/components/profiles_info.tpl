{* GOODMi: сведения о покупателе в подробностях заказа (views/orders/details.tpl подключает этот файл).
   На витрине GOODMi вместо трёх штатных колонок (плательщик, адрес доставки, контакты) – две плитки:
   «как получить заказ» по способу доставки и «получатель». Адрес плательщика не показываем.
   Способ определяется по названию: «Пункт выдачи - <адрес>» – свой пункт выдачи, «СДЭК» – пункт СДЭК
   (на сайте не выбирается, его подбирает менеджер), «курьер» – доставка по адресу.
   На остальных витринах и страницах – штатное содержимое темы responsive без изменений (ветка else).
   Оформление – секция 54 «Своего CSS» (.gm-ord-info). *}
{if $runtime.company_id == 2 && $runtime.controller == "orders" && $runtime.mode == "details" && $order_info}
    {$gm_ship = []}
    {foreach from=$order_info.shipping item="gm_s"}{if !$gm_ship}{$gm_ship = $gm_s}{/if}{/foreach}
    {$gm_ship_name = $gm_ship.shipping|default:""}
    {$gm_city = $order_info.s_city|default:""|trim}
    {$gm_addr = $order_info.s_address|default:""|trim}
    <div class="ty-profiles-info gm-ord-info">
        <div class="ty-profiles-info__item gm-ord-info__item">
            {if $gm_ship_name|strpos:"СДЭК" !== false}
                <h5 class="ty-profiles-info__title">Пункт выдачи СДЭК</h5>
                <div class="ty-profiles-info__field">
                    {if $gm_city}<span class="gm-ord-info__main">{$gm_city}</span>{/if}
                    <span class="gm-ord-info__note">Пункт выдачи и стоимость доставки подтвердит менеджер по телефону</span>
                </div>
            {elseif $gm_ship_name|strpos:"Пункт выдачи - " === 0}
                <h5 class="ty-profiles-info__title">Пункт выдачи GOODMi</h5>
                <div class="ty-profiles-info__field">
                    <span class="gm-ord-info__main">{$gm_ship_name|replace:"Пункт выдачи - ":""|replace:" - ":" – "}</span>
                    <span class="gm-ord-info__note">Ежедневно с 10:00 до 21:00</span>
                </div>
            {elseif $gm_ship_name|mb_strtolower|strpos:"курьер" !== false}
                <h5 class="ty-profiles-info__title">Доставка курьером</h5>
                <div class="ty-profiles-info__field">
                    {if $gm_city || $gm_addr}<span class="gm-ord-info__main">{$gm_city}{if $gm_city && $gm_addr}, {/if}{$gm_addr}</span>{/if}
                    {if !$gm_addr}<span class="gm-ord-info__note">Адрес и время доставки уточнит менеджер по телефону</span>{/if}
                </div>
            {else}
                <h5 class="ty-profiles-info__title">Получение</h5>
                <div class="ty-profiles-info__field">
                    <span class="gm-ord-info__main">{$gm_ship_name|default:"Способ получения уточнит менеджер"}</span>
                    {if $gm_city}<span class="gm-ord-info__note">{$gm_city}{if $gm_addr}, {$gm_addr}{/if}</span>{/if}
                </div>
            {/if}
        </div>
        <div class="ty-profiles-info__item gm-ord-info__item">
            <h5 class="ty-profiles-info__title">Получатель</h5>
            <div class="ty-profiles-info__field">
                <span class="gm-ord-info__main">{$order_info.firstname} {$order_info.lastname}</span>
                {if $order_info.phone}<span class="gm-ord-info__note">{$order_info.phone}</span>{/if}
                {if $order_info.email && !$order_info.email|fn_checkout_is_email_address_fake}<span class="gm-ord-info__note">{$order_info.email}</span>{/if}
            </div>
        </div>
    </div>
{else}
{include file="common/subheader.tpl" title=__("customer_information")}

{assign var="profile_fields" value=$location|fn_get_profile_fields}
{$contact_fields = $profile_fields.C}

<div class="ty-profiles-info">
    {if $profile_fields.B}
        <div id="tygh_order_billing_adress" class="ty-profiles-info__item ty-profiles-info__billing">
            <h5 class="ty-profiles-info__title">{__("billing_address")}</h5>
            <div class="ty-profiles-info__field">{include file="views/profiles/components/profile_fields_info.tpl" fields=$profile_fields.B title=__("billing_address")}</div>
        </div>
    {/if}
    {if $profile_fields.S}
        <div id="tygh_order_shipping_adress" class="ty-profiles-info__item ty-profiles-info__shipping">
            <h5 class="ty-profiles-info__title">{__("shipping_address")}</h5>
            <div class="ty-profiles-info__field">{include file="views/profiles/components/profile_fields_info.tpl" fields=$profile_fields.S title=__("shipping_address")}</div>
        </div>
    {/if}
    {if $contact_fields}
        <div class="ty-profiles-info__item">
            {capture name="contact_information"}
                {include file="views/profiles/components/profile_fields_info.tpl" fields=$contact_fields title=__("contact_information")}
            {/capture}
            {if $smarty.capture.contact_information|trim != ""}
                <h5 class="ty-profiles-info__title">{__("contact_information")}</h5>
                <div class="ty-profiles-info__field">{$smarty.capture.contact_information nofilter}</div>
            {/if}
        </div>
    {/if}
</div>
{/if}
