/**
 * GOODMi: кнопка «Поделиться» в карточке товара (шаблон goodmi_card).
 * Разметку кнопки и меню выдаёт шаблон (blocks/product_templates/components/goodmi_share.tpl), стили – секция 36
 * «Своего CSS». Скрипт только открывает и закрывает меню и копирует ссылку. На телефоне, если браузер умеет,
 * вместо меню открывается штатное окно «Поделиться» самого телефона. Ссылки на соцсети – обычные ссылки,
 * работают и без скрипта.
 */
(function (_, $) {
  var OPEN = 'gm-share--open';

  function close() {
    $('.' + OPEN).removeClass(OPEN).find('.gm-share__btn').attr('aria-expanded', 'false');
  }

  function copy(text, done) {
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(text).then(done, function () {
        legacyCopy(text, done);
      });
    } else {
      legacyCopy(text, done);
    }
  }

  function legacyCopy(text, done) {
    var $t = $('<textarea readonly></textarea>').val(text).css({position: 'fixed', top: 0, left: '-9999px'}).appendTo('body');
    $t[0].select();
    try {
      if (document.execCommand('copy')) {
        done();
      }
    } catch (e) {
    }
    $t.remove();
  }

  $(_.doc).on('click', '.gm-share__btn', function (e) {
    var $box = $(this).closest('.gm-share');
    e.preventDefault();
    e.stopPropagation();

    // Телефон: штатное окно «Поделиться», если оно есть
    if (navigator.share && window.matchMedia && window.matchMedia('(max-width: 767px)').matches) {
      navigator.share({title: $box.attr('data-gm-share-title') || document.title, url: $box.attr('data-gm-share-url')}).catch(function () {
      });
      return;
    }

    var wasOpen = $box.hasClass(OPEN);
    close();
    if (!wasOpen) {
      $box.addClass(OPEN);
      $(this).attr('aria-expanded', 'true');
    }
  });

  $(_.doc).on('click', '.gm-share__item', function (e) {
    var $item = $(this);
    if ($item.attr('data-gm-share') !== 'copy') {
      // Ссылка на соцсеть открывается в новой вкладке сама, меню просто закрываем
      close();
      return;
    }
    e.preventDefault();
    e.stopPropagation();
    var $label = $item.find('span');
    var text = $label.text();
    copy($item.closest('.gm-share').attr('data-gm-share-url'), function () {
      $label.text('Ссылка скопирована');
      setTimeout(function () {
        $label.text(text);
        close();
      }, 1200);
    });
  });

  $(_.doc).on('click', function (e) {
    if (!$(e.target).closest('.gm-share').length) {
      close();
    }
  });

  $(_.doc).on('keydown', function (e) {
    if (e.key === 'Escape') {
      close();
    }
  });
})(Tygh, Tygh.$);
