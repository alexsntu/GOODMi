{* GOODMi: страница «Номер телефона» (dispatch=gm_cabinet.phone) – состояние подтверждения и смена номера.
   Подтверждённый номер в профиле напрямую не правится (profiles.pre.php модуля gm_delivery_date): новый номер
   попадает в профиль только после ввода кода из СМС, отправленного на него. Данные: $gm_cab.state
   (fn_gm_cab_verify_state), $gm_cab.url – обработчик gm_cabinet.verify. Оформление – секция 56 (.gm-vf). *}
{capture name="mainbox_title"}Номер телефона{/capture}
{$st = $gm_cab.state}

<div class="gm-lk">
    {include file="views/gm_cabinet/components/nav.tpl"}

    <div class="gm-lk__main">
        <div class="gm-lk__card gm-vf gm-vf--plain">
            <h2 class="gm-vf__h">{if $gm_cab.has_phone}Ваш номер: {$st.mask}{else}Номер не указан{/if}</h2>
            <p class="gm-vf__t">
                {if $st.verified}Номер подтверждён. К нему привязаны покупки в магазинах и бонусы.
                {elseif $gm_cab.has_phone}Номер не подтверждён &#8211; покупки в магазинах и бонусы скрыты.{if $st.sms} Подтвердить его можно на странице <a href="{"gm_cabinet.orders"|fn_url}">«Мои заказы»</a>.{/if}
                {else}Укажите номер, чтобы видеть покупки в магазинах и бонусы.{/if}
            </p>
        </div>

        {if !$st.sms}
        <div class="gm-lk__card gm-vf gm-vf--plain">
            <h2 class="gm-vf__h">{if $st.verified}Изменить номер{else}Подтвердить номер{/if}</h2>
            <p class="gm-vf__t">{if $st.verified}Изменить номер может оператор интернет-магазина: позвоните по номеру <a href="tel:+78002501700">8 (800) 250-17-00</a> с нового телефона, который нужно подтвердить.{else}Подтвердить номер может оператор интернет-магазина: позвоните по номеру <a href="tel:+78002501700">8 (800) 250-17-00</a> с того телефона, который нужно подтвердить.{/if}</p>
            <div class="gm-vf__form">
                <a class="gm-vf__btn" href="tel:+78002501700">Позвонить 8 (800) 250-17-00</a>
            </div>
        </div>
        {else}
        <div class="gm-lk__card gm-vf gm-vf--plain">
            {if $st.change_phone}
                <h2 class="gm-vf__h">Введите код</h2>
                <p class="gm-vf__t">Мы отправили СМС с кодом на новый номер <b>{$st.change_mask}</b>. Введите 4 цифры из сообщения &#8211; после этого номер изменится.</p>
                <form class="gm-vf__form" action="{$gm_cab.url}" method="post" name="gm_vf_check">
                    <input type="hidden" name="action" value="check">
                    <input type="hidden" name="back" value="phone">
                    <input class="gm-vf__code" type="text" name="code" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]*" maxlength="4" placeholder="0000" required>
                    <button class="gm-vf__btn" type="submit">Подтвердить</button>
                </form>
                <p class="gm-vf__hint">Код действует 5 минут. Ошиблись номером? Введите его заново ниже.</p>
            {else}
                <h2 class="gm-vf__h">{if $gm_cab.has_phone}Изменить номер{else}Указать номер{/if}</h2>
                <p class="gm-vf__t">Введите новый номер &#8211; мы отправим на него СМС с кодом. Номер в профиле изменится после ввода кода.</p>
            {/if}
            <form class="gm-vf__form" action="{$gm_cab.url}" method="post" name="gm_vf_change">
                <input type="hidden" name="action" value="change">
                <input type="hidden" name="back" value="phone">
                <input class="gm-vf__phone" type="tel" name="new_phone" inputmode="tel" autocomplete="tel" placeholder="+7 978 000-00-00" maxlength="20" required>
                <button class="gm-vf__btn{if $st.change_phone} gm-vf__btn--ghost{/if}" type="submit">Получить код</button>
            </form>
            <p class="gm-vf__hint">Не приходит СМС? Номер подтвердит оператор интернет-магазина: позвоните по номеру <a href="tel:+78002501700">8 (800) 250-17-00</a> с того телефона, который нужно подтвердить.</p>
        </div>
        {/if}
    </div>
</div>
