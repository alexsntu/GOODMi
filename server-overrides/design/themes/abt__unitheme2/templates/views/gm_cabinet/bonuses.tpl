{* GOODMi: страница «Мои бонусы» (dispatch=gm_cabinet.bonuses). Данные готовит app/controllers/frontend/gm_cabinet.php,
   оформление – секция 44 «Своего CSS» (классы .gm-bp*). *}
{capture name="mainbox_title"}Мои бонусы{/capture}

{$b = $gm_cab.bonus}

<div class="gm-lk">
    {include file="views/gm_cabinet/components/nav.tpl"}

    <div class="gm-lk__main">
<div class="gm-bp">
    <div class="gm-bp__col">
    {if !$gm_cab.has_phone}
        <div class="gm-bp__card gm-bp-empty">
            <h2 class="gm-bp__h">Укажите номер телефона</h2>
            <p>Бонусы привязаны к номеру телефона. Добавьте его в профиль, и здесь появятся баланс, уровень и история начислений.</p>
            <a class="gm-bp-btn" href="{$gm_cab.profile_url}">Перейти в профиль</a>
        </div>
    {elseif $gm_cab.verify}
        {include file="views/gm_cabinet/components/verify.tpl"}
    {elseif !$b}
        <div class="gm-bp__card gm-bp-empty">
            <h2 class="gm-bp__h">Не удалось получить данные о бонусах</h2>
            <p>Сервис бонусной программы сейчас не отвечает. Обновите страницу через несколько минут &#8211; бонусы никуда не пропали.</p>
        </div>
    {elseif !$b.found}
        <div class="gm-bp__card gm-bp-empty">
            <h2 class="gm-bp__h">Вы ещё не участвуете в бонусной программе</h2>
            <p>По номеру телефона из вашего профиля карта не найдена. Зарегистрируйтесь в программе, чтобы получать кешбэк бонусами с каждой покупки.</p>
            <a class="gm-bp-btn" href="{$gm_cab.program_url}">Как вступить в программу</a>
        </div>
    {else}
        <div class="gm-bp-hero">
            <div class="gm-bp-hero__main">
                <div class="gm-bp-hero__label">Доступно для оплаты</div>
                <div class="gm-bp-hero__sum">{$b.balance} <small>{$b.balance_word}</small></div>
                <div class="gm-bp-hero__note">1 бонус = 1 рубль &#183; до {$b.debit_pct}% стоимости покупки</div>
            </div>
            <div class="gm-bp-hero__side">
                <img class="gm-bp-hero__card" src="{$b.level.img}" alt="Клубная карта {$b.level.name}" width="300" height="115">
                <div class="gm-bp-hero__info">
                    <div class="gm-bp-hero__status">Ваш уровень</div>
                    <div class="gm-bp-hero__level">{$b.level.name}</div>
                    <div class="gm-bp-hero__rate">Кешбэк {$b.level.pct} бонусами с покупок</div>
                </div>
            </div>
        </div>

        <div class="gm-bp__card">
            <div class="gm-bp-stats">
                <div class="gm-bp-stat">
                    <div class="gm-bp-stat__v">{$b.pending}</div>
                    <div class="gm-bp-stat__l">ожидают начисления</div>
                </div>
                <div class="gm-bp-stat">
                    <div class="gm-bp-stat__v">{if $b.expire_amount}{$b.expire_amount}{else}0{/if}</div>
                    <div class="gm-bp-stat__l">{if $b.expire_date}сгорит {$b.expire_date}{else}сгорает в ближайшее время{/if}</div>
                </div>
                <div class="gm-bp-stat">
                    <div class="gm-bp-stat__v">{$b.credited}</div>
                    <div class="gm-bp-stat__l">начислено за всё время</div>
                </div>
            </div>
        </div>

        <div class="gm-bp__card">
            <h2 class="gm-bp__h">{if $b.next}Прогресс до следующего уровня{else}Ваш уровень{/if}</h2>
            <div class="gm-bp-prog__text">
                {if $b.next}
                    До уровня <b>{$b.next.name}</b> осталось купить на <b>{$b.to_next}&nbsp;&#8381;</b> &#183; уже куплено на {$b.spent}&nbsp;&#8381;
                {else}
                    У вас максимальный уровень программы &#8211; <b>{$b.level.name}</b>. Куплено на {$b.spent}&nbsp;&#8381;
                {/if}
            </div>
            <div class="gm-bp-prog__bar"><div class="gm-bp-prog__fill" style="width:{$b.percent}%"></div></div>
            <div class="gm-bp-levels">
                {foreach from=$b.levels item="level" key="i"}
                    <a class="gm-bp-level{if $i == $b.level_index} gm-bp-level--on{/if}" href="{$level.url}">
                        <img class="gm-bp-level__img" src="{$level.img}" alt="Уровень {$level.name}" loading="lazy" width="300" height="96">
                        <span class="gm-bp-level__b">
                            <span class="gm-bp-level__n">{$level.name}</span>
                            <span class="gm-bp-level__pct">{$level.pct} <span>кешбэк</span></span>
                            <span class="gm-bp-level__p">{$level.range}</span>
                        </span>
                    </a>
                {/foreach}
            </div>
        </div>

        <div class="gm-bp__card">
            <h2 class="gm-bp__h">Начисления и списания</h2>
            {if $b.ops}
                <input type="checkbox" id="gm_bp_more" class="gm-bp-toggle">
                {foreach from=$b.ops item="op" name="ops"}
                    <div class="gm-bp-op{if !$op.plus} gm-bp-op--minus{/if}{if $smarty.foreach.ops.index >= 8} gm-bp-op--extra{/if}">
                        <div class="gm-bp-op__ic">{if $op.plus}+{else}&#8722;{/if}</div>
                        <div class="gm-bp-op__t">
                            <div class="gm-bp-op__n">{$op.name}</div>
                            <div class="gm-bp-op__d">{$op.date}{if $op.purchase} &#183; покупка на {$op.purchase}&nbsp;&#8381;{/if}{if $op.until} &#183; {$op.until}{/if}</div>
                        </div>
                        <div class="gm-bp-op__s">{if $op.plus}+{else}&#8722;{/if}{$op.amount}</div>
                    </div>
                {/foreach}
                {if $b.ops|count > 8}
                    <label for="gm_bp_more" class="gm-bp-more">Показать ещё</label>
                {/if}
            {else}
                <p class="gm-bp-note">Операций пока нет. Бонусы поступают в течение 7 дней после покупки.</p>
            {/if}
        </div>
    {/if}
    </div>

    <div class="gm-bp__col">
        <div class="gm-bp__card">
            <h2 class="gm-bp__h">Как это работает</h2>
            <ul class="gm-bp-how">
                <li>Бонусы начисляются за покупки на сайте и в магазинах GOODMi</li>
                <li>Поступают на счёт в течение 7 дней после покупки</li>
                <li>Бонусами можно оплатить до 25% стоимости следующей покупки</li>
            </ul>
            <a class="gm-bp-link" href="{$gm_cab.program_url}">Условия бонусной программы</a>
        </div>

        <div class="gm-bp__card">
            <h2 class="gm-bp__h">Мои заказы</h2>
            {if $gm_cab.orders}
                {foreach from=$gm_cab.orders item="order"}
                    <div class="gm-bp-ord">
                        <div class="gm-bp-ord__main">
                            <div class="gm-bp-ord__n">{if $order.url}<a href="{$order.url}">&#8470;&nbsp;{$order.number}</a>{else}&#8470;&nbsp;{$order.number}{/if} от {$order.date}</div>
                            <div class="gm-bp-ord__st"><span class="gm-bp-chip{if $order.source == "store"} gm-bp-chip--store{/if}">{$order.label}</span>{if $order.status} {$order.status}{/if}</div>
                        </div>
                        <div class="gm-bp-ord__sum">{$order.sum}&nbsp;&#8381;</div>
                    </div>
                {/foreach}
                <a class="gm-bp-more" href="{$gm_cab.orders_url}">Все заказы</a>
            {else}
                <p class="gm-bp-note">Заказов пока нет.</p>
            {/if}
        </div>
    </div>
</div>
    </div>
</div>
