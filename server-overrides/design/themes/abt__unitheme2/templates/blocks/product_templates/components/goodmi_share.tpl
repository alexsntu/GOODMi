{* GOODMi: кнопка «Поделиться» в карточке товара. Подключается из goodmi_card.tpl и goodmi_card_mobile.tpl
   в блоке кнопок, рядом с «В избранное» и «Сравнить». Стили – секция 36 «Своего CSS», открытие меню и
   копирование ссылки – js/addons/gm_delivery_date/share.js. Ссылки на соцсети работают и без скрипта. *}
{$gm_share_url = "products.view?product_id=`$product.product_id`"|fn_url}
{$gm_share_title = $product.product|strip_tags|trim}
{$gm_share_u = $gm_share_url|escape:"url"}
{$gm_share_t = $gm_share_title|escape:"url"}
<div class="gm-share" data-gm-share-url="{$gm_share_url}" data-gm-share-title="{$gm_share_title}">
    <button type="button" class="gm-share__btn" aria-haspopup="true" aria-expanded="false">
        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M14 9V5l7 7-7 7v-4.1c-5 0-8.5 1.6-11 5.1 1-5 4-10 11-11z"/></svg>Поделиться
    </button>
    <div class="gm-share__menu">
        <a class="gm-share__item" data-gm-share="copy" href="{$gm_share_url}" rel="nofollow">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="9" width="11" height="11" rx="2"/><path d="M5 15V6a2 2 0 0 1 2-2h9"/></svg><span>Скопировать ссылку</span>
        </a>
        <a class="gm-share__item" href="https://vk.com/share.php?url={$gm_share_u}&amp;title={$gm_share_t}" target="_blank" rel="nofollow noopener">
            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M7 3h10a4 4 0 0 1 4 4v10a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4V7a4 4 0 0 1 4-4zm5.6 13c-4.1 0-6.5-2.8-6.600-7.500h2.100c.070 3.400 1.600 4.900 2.800 5.200V8.500h2v3c1.200-.130 2.400-1.500 2.800-3h2c-.300 1.800-1.700 3.200-2.600 3.800 1 .500 2.500 1.700 3.100 3.700h-2.200c-.500-1.400-1.600-2.500-3.100-2.700V16h-.300z"/></svg><span>ВКонтакте</span>
        </a>
        <a class="gm-share__item" href="https://connect.ok.ru/offer?url={$gm_share_u}&amp;title={$gm_share_t}" target="_blank" rel="nofollow noopener">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="7" r="3.2"/><path d="M7.500 13.200c2.800 1.700 6.200 1.700 9 0"/><path d="M12 14.500v2"/><path d="M8.500 20.500l3.500-4 3.500 4"/></svg><span>Одноклассники</span>
        </a>
        <a class="gm-share__item" href="https://t.me/share/url?url={$gm_share_u}&amp;text={$gm_share_t}" target="_blank" rel="nofollow noopener">
            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M21.900 4.300 18.700 19.400c-.200 1-.900 1.300-1.700.800l-4.800-3.600-2.300 2.200c-.300.300-.500.500-1 .500l.300-4.900 8.900-8c.400-.300-.100-.500-.600-.200l-11 6.900-4.700-1.500c-1-.300-1-1 .200-1.500L20.600 3c.800-.300 1.600.200 1.300 1.300z"/></svg><span>Telegram</span>
        </a>
        <a class="gm-share__item" href="https://wa.me/?text={$gm_share_t}%20{$gm_share_u}" target="_blank" rel="nofollow noopener">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3.500 20.500l1.300-4.200A8.500 8.500 0 1 1 8 19.400l-4.500 1.100z"/><path d="M9 8.500c0 3.500 3 6.500 6.500 6.500l1-1.600-2-1-.800.800c-1-.400-2-1.400-2.400-2.400l.800-.800-1-2L9 8.500z" fill="currentColor" stroke="none"/></svg><span>WhatsApp</span>
        </a>
    </div>
</div>
