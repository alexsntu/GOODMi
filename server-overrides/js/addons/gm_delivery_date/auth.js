/**
 * GOODMi: вход по номеру телефона, регистрация без e-mail, восстановление пароля по СМС – правки штатных форм.
 * Шаблоны темы не трогаем, поэтому три вещи делает скрипт (и для форм, подгруженных позже, – всплывающее окно входа):
 * 1) поле входа подписано «Телефон или e-mail» и не проверяется как e-mail (сервер ищет по подтверждённому номеру,
 *    см. fn_gm_delivery_date_auth_routines);
 * 2) e-mail в форме регистрации и в профиле необязателен (см. profiles.pre.php модуля gm_delivery_date); покупателю без
 *    почты записана заглушка из номера, 79780000000@goodmi.ru (gm_cab_is_stub_email в app/gm_cabinet/store.php) –
 *    в форме профиля она не показывается;
 * 3) «Забыли пароль?» ведёт на восстановление по СМС (gm_cabinet.recover), оттуда есть ссылка на восстановление по e-mail.
 */
(function (_, $) {
  var RECOVER_URL = 'index.php?dispatch=gm_cabinet.recover';
  var STUB_EMAIL = /^(mail|7\d{10}(-\d+)?)@goodmi\.ru$/i;

  function tune(context) {
    var $ctx = $(context || _.doc);

    $ctx.find('label.ty-login__filed-label.cm-email').each(function () {
      var $label = $(this);
      $label.removeClass('cm-email').text('Телефон или e-mail');
      $('#' + $label.attr('for')).attr({placeholder: '+7 978 000-00-00 или e-mail', autocomplete: 'username'});
    });

    $ctx.find('form[name="profiles_register_form"] label[for="email"], form[name="profile_form"] label[for="email"]').each(function () {
      var $label = $(this);
      if ($label.hasClass('cm-required')) {
        $label.removeClass('cm-required').append(' <span class="gm-opt">(необязательно)</span>');
      }
      var $input = $('#' + $label.attr('for'));
      if (STUB_EMAIL.test($.trim(String($input.val() || '')))) {
        $input.val('');
      }
    });

    $ctx.find('a.ty-password-forgot__a').attr('href', RECOVER_URL);
  }

  $(function () {
    tune(_.doc);
  });
  $.ceEvent('on', 'ce.commoninit', function (context) {
    tune(context);
  });
})(Tygh, Tygh.$);
