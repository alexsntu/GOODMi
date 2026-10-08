/**
 * GOODMi: компактная кнопка чата Jivo на телефоне.
 * Штатная кнопка Jivo на узком экране перекрывает закреплённую кнопку «В корзину», а в кабинете
 * Jivo мобильный вид не настраивается. Поэтому на экранах до 767px штатная кнопка сделана невидимой
 * стилями (секция 31 «Своего CSS»), а вместо неё – своя круглая кнопка. Она нажимает штатную, так что
 * открывается то же меню каналов Jivo (Telegram, ВКонтакте, звонок, онлайн-чат). Если штатной кнопки
 * нет или меню не появилось – открывается окно чата. Пока чат открыт, у <html> стоит класс
 * gm-jivo-open – стили прячут нашу кнопку. Красная точка – сообщение оператора при закрытом чате.
 * На ПК скрипт ничего не меняет: все правила внутри @media (max-width: 767px).
 */
(function (window, document) {
  var OPEN = 'gm-jivo-open';
  var NEW = 'gm-jivo-btn--new';
  var root = document.documentElement;
  var btn = null;
  var hooked = false;

  function chain(name, fn) {
    var prev = window[name];
    window[name] = function () {
      try {
        fn.apply(this, arguments);
      } catch (e) {}
      if (typeof prev === 'function') {
        return prev.apply(this, arguments);
      }
    };
  }

  function mark(on) {
    if (btn) {
      btn.className = 'gm-jivo-btn' + (on ? ' ' + NEW : '');
    }
  }

  function hook() {
    if (hooked) {
      return;
    }
    hooked = true;
    // Jivo вызывает эти функции по имени в момент события, поэтому их можно задать и после загрузки виджета
    chain('jivo_onOpen', function () {
      root.classList.add(OPEN);
      mark(false);
    });
    chain('jivo_onClose', function () {
      root.classList.remove(OPEN);
    });
    chain('jivo_onMessageReceived', function () {
      if (!root.classList.contains(OPEN)) {
        mark(true);
      }
    });
  }

  function build() {
    if (btn || !document.body) {
      return;
    }
    btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'gm-jivo-btn';
    btn.setAttribute('aria-label', 'Написать в чат');
    btn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"'
      + ' stroke-linejoin="round" aria-hidden="true"><path d="M21 11.5a8.4 8.4 0 0 1-12.3 7.4L3 21l2.1-5.7A8.4 8.4 0 1 1 21 11.5z"/></svg>';
    btn.addEventListener('click', function () {
      if (!window.jivo_api || typeof window.jivo_api.open !== 'function') {
        return;
      }
      mark(false);
      var wrap = document.querySelector('body > jdiv .__jivoMobileButton');
      var native = wrap && (wrap.querySelector('jdiv[class*="button__"]') || wrap);
      if (!native) {
        window.jivo_api.open();
        return;
      }
      native.click();
      // Меню не появилось (Jivo поменял разметку) – открываем чат напрямую
      setTimeout(function () {
        var menu = document.querySelector('body > jdiv a[href^="tel:"], body > jdiv a[href*="telegram"], body > jdiv a[href*="vk."]');
        if (!menu && !root.classList.contains(OPEN)) {
          window.jivo_api.open();
        }
      }, 800);
    });
    document.body.appendChild(btn);

    try {
      if (window.jivo_api.getUnreadMessagesCount && window.jivo_api.getUnreadMessagesCount() > 0) {
        mark(true);
      }
    } catch (e) {}
  }

  // Jivo подгружается с задержкой: кнопку показываем, только когда виджет готов и нажатие сработает
  var timer = setInterval(function () {
    if (window.jivo_api && document.querySelector('body > jdiv')) {
      clearInterval(timer);
      hook();
      build();
    }
  }, 700);
})(window, document);
