{* GOODMi: левое меню личного кабинета на штатных страницах «Данные профиля», «Подробности заказа» и «Избранное»
   (в избранном – только на ПК: на телефоне меню скрыто стилями, секция 51).
   Точка wrapper:mainbox_general_title_wrapper срабатывает у каждого блока с заголовком на всех витринах, поэтому
   условие строгое: витрина GOODMi, вошедший покупатель, нужная страница и только основной блок страницы
   (тип блока main) – иначе меню подставлялось и к другим блокам с заголовком ниже по странице.
   Оформление – секция 49 «Своего CSS» (.gm-lk__side--std).
   Гостю на странице его заказа (после оформления без входа) вместо меню – карточка с приглашением зарегистрироваться
   (.gm-lk__side--guest, секция 54): с ней страница получает то же оформление, что и у вошедшего покупателя.
   При регистрации CS-Cart сам привязывает заказы гостя к новой учётной записи. *}
{if $runtime.company_id == 2 && $auth.user_id && !isset($gm_lk_std_done) && isset($block.type) && $block.type == "main"
    && (($runtime.controller == "profiles" && $runtime.mode == "update") || ($runtime.controller == "orders" && $runtime.mode == "details")
        || ($runtime.controller == "wishlist" && $runtime.mode == "view"))}
{assign var="gm_lk_std_done" value=true scope="global"}
{$gm_lk_name = $auth.user_id|fn_get_user_name}
{if $runtime.controller == "orders"}{$gm_lk_active = "orders"}{elseif $runtime.controller == "wishlist"}{$gm_lk_active = "wishlist"}{else}{$gm_lk_active = "profile"}{/if}
<aside class="gm-lk__side gm-lk__side--std{if $gm_lk_active == "wishlist"} gm-lk__side--wishlist{/if}">
    <div class="gm-lk-user">
        <div class="gm-lk-user__ava">{$gm_lk_name|truncate:1:"":true|upper}</div>
        <div class="gm-lk-user__info">
            <div class="gm-lk-user__name">{$gm_lk_name}</div>
            <a class="gm-lk-user__edit" href="{"profiles.update"|fn_url}">Изменить профиль</a>
        </div>
    </div>
    <nav class="gm-lk-nav">
        <a class="gm-lk-nav__a gm-lk-nav__a--orders{if $gm_lk_active == "orders"} gm-lk-nav__a--on{/if}" href="{"gm_cabinet.orders"|fn_url}">Мои заказы</a>
        <a class="gm-lk-nav__a gm-lk-nav__a--purchased" href="{"gm_cabinet.products"|fn_url}">Купленные товары</a>
        <a class="gm-lk-nav__a gm-lk-nav__a--bonuses" href="{"gm_cabinet.bonuses"|fn_url}">Мои бонусы</a>
        <a class="gm-lk-nav__a gm-lk-nav__a--wishlist{if $gm_lk_active == "wishlist"} gm-lk-nav__a--on{/if}" href="{"wishlist.view"|fn_url}">Избранное</a>
        <a class="gm-lk-nav__a gm-lk-nav__a--compare" href="{"product_features.compare"|fn_url}">Сравнение</a>
        <a class="gm-lk-nav__a gm-lk-nav__a--profile{if $gm_lk_active == "profile"} gm-lk-nav__a--on{/if}" href="{"profiles.update"|fn_url}">Данные профиля</a>
        <a class="gm-lk-nav__a gm-lk-nav__a--out" href="{"auth.logout"|fn_url}" rel="nofollow">Выйти</a>
    </nav>
</aside>
{* Подтверждённый номер в профиле не правится (profiles.pre.php модуля gm_delivery_date выставляет $gm_phone_locked):
   поле телефона гасится стилями секции 56, смена номера – на странице gm_cabinet.phone *}
{if $gm_lk_active == "profile" && $gm_phone_locked}
<div class="gm-phone-lock">Номер телефона подтверждён и защищён от изменений. <a href="{"gm_cabinet.phone"|fn_url}">Как сменить номер</a></div>
{/if}
{/if}
{if $runtime.company_id == 2 && !$auth.user_id && !isset($gm_lk_std_done) && isset($block.type) && $block.type == "main"
    && $runtime.controller == "orders" && $runtime.mode == "details"}
{assign var="gm_lk_std_done" value=true scope="global"}
{$gm_lk_ret = $config.current_url|urlencode}
<aside class="gm-lk__side gm-lk__side--std gm-lk__side--guest">
    <div class="gm-lk-guest">
        <div class="gm-lk-guest__title">Заказ оформлен без входа</div>
        <p class="gm-lk-guest__text">Зарегистрируйтесь &#8211; этот заказ появится в личном кабинете, а история покупок и бонусы будут под рукой.</p>
        <a class="gm-lk-guest__btn" href="{"profiles.add"|fn_url}" rel="nofollow">Зарегистрироваться</a>
        <a class="gm-lk-guest__link" href="{"auth.login_form?return_url=`$gm_lk_ret`"|fn_url}" rel="nofollow">Уже есть аккаунт &#8211; войти</a>
    </div>
</aside>
{/if}
