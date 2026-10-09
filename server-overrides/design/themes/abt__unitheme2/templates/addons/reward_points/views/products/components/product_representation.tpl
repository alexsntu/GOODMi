{* GOODMi: переопределение шаблона модуля reward_points (исходник – design/themes/responsive/templates/addons/
   reward_points/views/products/components/product_representation.tpl). Отличие одно, только для витрины GOODMi
   (company_id = 2): «начислим N бонусов» – ссылка на страницу бонусной программы. Для остальных витрин разметка
   дословно штатная. Оформление строки в карточке товара – секция 38 «Своего CSS». *}
{$show_price_in_points = $show_price_in_points|default:true}

{if $show_price_in_points && $product.points_info.price}
    <div class="ty-reward-group">
        <span class="ty-control-group__label ty-reward-points-product-representation__label ty-reward-points-product-representation__label--price-in-points product-list-field">{__("price_in_points")}:</span>
        <span class="ty-control-group__item ty-reward-points-product-representation__item ty-reward-points-product-representation__item--price-in-points" id="price_in_points_{$obj_prefix}{$obj_id}"><bdi>{__("points_lowercase", [$product.points_info.price])}</bdi></span>
    </div>
{/if}
<div class="ty-reward-group product-list-field{if !$product.points_info.reward.amount} hidden{/if}">
    <span class="ty-control-group__label ty-reward-points-product-representation__label ty-reward-points-product-representation__label--reward-points">{__("reward_points")}:</span>
    <span class="ty-control-group__item ty-reward-points-product-representation__item ty-reward-points-product-representation__item--reward-points" id="reward_points_{$obj_prefix}{$obj_id}">{if $runtime.company_id == 2}<bdi><a class="gm-bonus-link" href="/bonusnaya-programma/">{__("points_lowercase", [$product.points_info.reward.amount])}</a></bdi>{else}<bdi>{__("points_lowercase", [$product.points_info.reward.amount])}</bdi>{/if}</span>
</div>
