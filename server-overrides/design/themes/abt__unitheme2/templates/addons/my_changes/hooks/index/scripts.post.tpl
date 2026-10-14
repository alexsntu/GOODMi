{* Maxmobiles: кнопка «Список сравнения» в уведомлении о добавлении в сравнение ведёт сразу на список товара (compare_notice.js) – только витрина Maxmobiles (company_id = 1) *}
{if $runtime.company_id == 1}
    {script src="js/addons/my_changes/compare_notice.js"}
{/if}
