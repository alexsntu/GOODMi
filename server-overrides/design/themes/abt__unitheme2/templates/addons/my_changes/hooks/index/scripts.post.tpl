{* Maxmobiles (company_id = 1): ссылки на сравнение открывают страницу, а не окно списков (compare_notice.js); на оформлении заказа нажатие на город открывает окно выбора города (checkout_city.js) *}
{if $runtime.company_id == 1}
    {script src="js/addons/my_changes/compare_notice.js"}
    {script src="js/addons/my_changes/checkout_city.js"}
{/if}
