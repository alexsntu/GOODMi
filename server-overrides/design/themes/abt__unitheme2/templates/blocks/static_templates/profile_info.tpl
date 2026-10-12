{* GOODMi: блок справа от формы регистрации и профиля. Перекрывает штатный файл темы responsive:
   на витрине GOODMi на странице регистрации – свой текст преимуществ (с бонусами и личным кабинетом),
   во всех остальных случаях – штатное содержимое без изменений (сверять с оригиналом после обновления CS-Cart).
   Языковая переменная text_profile_benefits общая для всех витрин, поэтому текст задан здесь. *}
{if $runtime.controller === 'profiles'}
    {if $runtime.mode === 'add'}
    <div class="ty-account-benefits">
        {if $runtime.company_id == 2}
            <h4>Что даёт регистрация</h4>
            <ul>
                <li>Бонусы за покупки: копите их и оплачивайте до 25% следующего заказа</li>
                <li>Заказы с сайта и покупки в магазинах GOODMi &#8211; в одном личном кабинете</li>
                <li>Купленные товары под рукой: повторить заказ или оставить отзыв в пару нажатий</li>
                <li>Быстрое оформление: контакты подставятся сами</li>
            </ul>
        {else}
            {__("text_profile_benefits") nofilter}
        {/if}
    </div>

    {elseif $runtime.mode == 'update'}
        <div class="ty-account-detail">
            <div>
                {__("text_profile_details") nofilter}
            </div>
            <div class="ty-account-detail__image">
                {include file="common/image.tpl" images = ["image_path" => "`$images_dir`/profile_details.png", "image_x" => 183, "image_y" => 206]}
            </div>
        </div>
    {/if}
{/if}
