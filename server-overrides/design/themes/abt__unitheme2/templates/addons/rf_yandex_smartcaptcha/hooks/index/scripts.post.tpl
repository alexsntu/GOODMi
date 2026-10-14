{* GOODMi 2026-10-14: копия шаблона модуля rf_yandex_smartcaptcha из темы responsive. На витринах GOODMi (company_id = 2) и Maxmobiles (1)
   скрипт капчи Яндекса больше не подключается синхронно на каждой странице: капча включена только в формах регистрации
   и восстановления пароля (на Maxmobiles – только регистрации), поэтому captcha.js загружается, когда на странице появляется поле капчи (.smart-captcha),
   в том числе во всплывающем окне. На Xione – как было.
   Откат: удалить этот файл (останется шаблон темы responsive). *}
{if $app.antibot->getDriver()|get_class == "Tygh\Addons\SmartCaptcha\SmartCaptchaDriver"}

{if $runtime.company_id == 1 || $runtime.company_id == 2}
<script type="text/javascript">
(function () {ldelim}
    var boxes = document.getElementsByClassName('smart-captcha');
    var loaded = false;
    var load = function () {ldelim}
        if (loaded) {ldelim}
            return;
        {rdelim}
        loaded = true;
        var s = document.createElement('script');
        s.src = 'https://smartcaptcha.yandexcloud.net/captcha.js';
        s.defer = true;
        document.head.appendChild(s);
    {rdelim};
    if (boxes.length) {ldelim}
        load();
        return;
    {rdelim}
    if (!window.MutationObserver) {ldelim}
        load();
        return;
    {rdelim}
    var mo = new MutationObserver(function () {ldelim}
        if (boxes.length) {ldelim}
            mo.disconnect();
            load();
        {rdelim}
    {rdelim});
    mo.observe(document.documentElement, {ldelim} childList: true, subtree: true {rdelim});
{rdelim})();
</script>
{else}
<script src="https://smartcaptcha.yandexcloud.net/captcha.js" defer></script>
{/if}
<script type="text/javascript">
(function (_, $) {
	_.tr({
		rf_yandex_smartcaptcha_invalid: '{__("error_confirmation_code_invalid")|escape:"javascript"}'
	});
}(Tygh, Tygh.$));
</script>
{script src="js/addons/rf_yandex_smartcaptcha/func.js"}

{/if}
