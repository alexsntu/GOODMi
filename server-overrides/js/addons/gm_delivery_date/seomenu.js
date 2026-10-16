/**
 * GOODMi: кнопка «Ещё» в строке быстрых ссылок категории (блок-меню с классом .seomenu, блок 1284).
 * На ПК ссылки переносятся по строкам; когда их больше двух строк, лишние прячутся, а в конце второй строки
 * ставится кнопка «Ещё N». По нажатию показываются все ссылки, кнопка превращается в «Свернуть».
 * Выбранная подборка (открыта её страница, пункт .active) встаёт первой и получает крестик «Сбросить» – он ведёт в сам раздел
 * (последняя ссылка хлебных крошек), как у DNS (2026-10-16). Это – и на ПК, и на телефоне.
 * На телефоне (до 767px) кнопки «Ещё» нет: там одна строка с прокруткой пальцем.
 * Стили – секции 65 и 70 (выбранная подборка) «Своего CSS». До запуска скрипта стили сами обрезают список до двух строк, чтобы страница
 * не прыгала; класс gm-seomenu--ready снимает обрезку, когда кнопка расставлена.
 */
(function (_, $) {
  var ROWS = 2;
  var DESKTOP = '(min-width: 768px)';
  var HIDDEN = 'gm-seomenu-hidden';
  var READY = 'gm-seomenu--ready';
  var CROSS = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"'
    + ' aria-hidden="true"><path d="M7 7l10 10M17 7L7 17"/></svg>';
  var CHEVRON = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"'
    + ' stroke-linejoin="round" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>';

  function button(text, open) {
    return $('<li class="gm-seomenu-more' + (open ? ' gm-seomenu-more--open' : '') + '"><button type="button"'
      + ' class="gm-seomenu-more__btn" aria-expanded="' + (open ? 'true' : 'false') + '">' + text + CHEVRON + '</button></li>');
  }

  // Адрес раздела для крестика: последняя ссылка хлебных крошек (страница подборки в них не ссылка), иначе адрес без последней части
  function parentUrl() {
    var a = $('.ty-breadcrumbs a.ty-breadcrumbs__a').last().attr('href') || '';
    var here = window.location.href.split(/[?#]/)[0];
    if (a && a !== here) {
      return a;
    }
    return here.replace(/[^\/]+\/?$/, '');
  }

  // Выбранная подборка – первой и с крестиком
  function selected($ul) {
    var $active = $ul.children('.ty-text-links__item').filter('.active, .ty-text-links__active').first();
    if (!$active.length) {
      return;
    }
    $active.addClass('gm-seomenu-sel');
    if ($active.index() !== 0) {
      $ul.prepend($active);
    }
    if (!$active.children('.gm-seomenu-x').length) {
      $active.append('<a class="gm-seomenu-x" href="' + $('<i>').text(parentUrl()).html() + '" title="Сбросить выбор" aria-label="Сбросить выбор">' + CROSS + '</a>');
    }
  }

  function setup(ul) {
    var $ul = $(ul);
    selected($ul);
    var $items = $ul.children('.ty-text-links__item');
    var tops = [];
    var first = -1; // номер первой ссылки третьей строки
    var $more;

    $ul.children('.gm-seomenu-more').remove();
    $items.removeClass(HIDDEN);
    if (!window.matchMedia || !window.matchMedia(DESKTOP).matches || !$ul.is(':visible')) {
      $ul.addClass(READY);
      return;
    }
    $items.each(function (i) {
      if (tops.indexOf(this.offsetTop) < 0) {
        tops.push(this.offsetTop);
      }
      if (first < 0 && tops.length > ROWS) {
        first = i;
      }
    });
    if (first < 0) {
      $ul.addClass(READY);
      return;
    }
    if ($ul.data('gmSeomenuOpen')) {
      $ul.append(button('Свернуть', true)).addClass(READY);
      return;
    }
    $items.slice(first).addClass(HIDDEN);
    $more = button('Ещё ' + ($items.length - first), false);
    $items.eq(first - 1).after($more);
    // кнопка не поместилась во вторую строку – прячем ещё по одной ссылке перед ней
    while (first > 1 && $more[0].offsetTop > tops[ROWS - 1]) {
      first--;
      $items.eq(first).addClass(HIDDEN);
      $more.find('button').html('Ещё ' + ($items.length - first) + CHEVRON);
    }
    $ul.addClass(READY);
  }

  function init(context) {
    $('.seomenu .ty-text-links', context || document).each(function () {
      setup(this);
    });
  }

  $(document).on('click', '.gm-seomenu-more__btn', function () {
    var $ul = $(this).closest('.ty-text-links');

    $ul.data('gmSeomenuOpen', !$ul.data('gmSeomenuOpen'));
    setup($ul[0]);
    $ul.find('.gm-seomenu-more__btn').trigger('focus');
  });

  var timer;
  $(window).on('resize', function () {
    clearTimeout(timer);
    timer = setTimeout(init, 150);
  }).on('load', function () {
    init();
  });

  $.ceEvent('on', 'ce.commoninit', function (context) {
    init(context);
  });
}(Tygh, Tygh.$));
