{* GOODMi: сроки получения и условия покупки в карточке товара – сразу в разметке страницы.
   Хук вызывается из шаблонов blocks/product_templates/goodmi_card.tpl и components/goodmi_card_mobile.tpl.
   Вне витрины GOODMi функция возвращает пустую строку. *}
{$product.product_id|fn_gm_delivery_date_card_html nofilter}
