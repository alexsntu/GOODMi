/**
 * GOODMi: заголовок «О товаре» над краткими характеристиками в карточке товара (ПК).
 * Блок характеристик (.ut2-pb__short-features) стилями переставлен под выбор вариантов (секция 32
 * «Своего CSS»); этот скрипт добавляет над ним строку «О товаре» и кнопку «Перейти к описанию»,
 * которая прокручивает страницу к разделу «Описание» (#description). Если описания у товара нет,
 * кнопка не выводится. Карточка перерисовывается при смене цвета или памяти – заголовок ставится
 * заново на каждое ce.commoninit. Шаблон темы не меняется. На телефоне строка скрыта стилями.
 */
(function (_, $) {
  var TARGET = 'description';

  function init(context) {
    var $ctx = $(context || document);
    $ctx.find('.ut2-pb__short-features').add($ctx.filter('.ut2-pb__short-features')).each(function () {
      var $box = $(this);
      if (!$box.parent().hasClass('ut2-pb__aside') || $box.children('.gm-about__head').length) {
        return;
      }
      var $head = $('<div class="gm-about__head"></div>').append($('<span class="gm-about__title"></span>').text('О товаре'));
      if (document.getElementById(TARGET)) {
        $head.append($('<a class="gm-about__link"></a>').attr('href', '#' + TARGET).text('Перейти к описанию'));
      }
      $box.prepend($head);
    });
  }

  $(_.doc).on('click', '.gm-about__link', function (e) {
    var target = document.getElementById(TARGET);
    // На странице задан <base href>, поэтому обычный переход по «#description» увёл бы на главную
    e.preventDefault();
    if (target) {
      target.scrollIntoView({behavior: 'smooth', block: 'start'});
    }
  });

  $.ceEvent('on', 'ce.commoninit', function (context) {
    init(context);
  });
})(Tygh, Tygh.$);
