/**
 * GOODMi: чат Jivo на телефоне – пункт «Помощь» в нижнем закреплённом меню.
 * Штатная кнопка Jivo на узком экране перекрывает закреплённую кнопку «В корзину», а в кабинете
 * Jivo мобильный вид не настраивается. Поэтому на экранах до 767px штатная кнопка сделана невидимой
 * стилями (секция 31 «Своего CSS»), а вместо неё в нижнее меню темы (.ut2-sticky-panel) ставится пункт
 * «Помощь» – на место убранной «Главной» (порядок задан в стилях). Он нажимает штатную кнопку, так что
 * открывается то же меню каналов Jivo (Telegram, ВКонтакте, звонок, онлайн-чат). Если штатной кнопки
 * нет или меню не появилось – открывается окно чата. Красная точка на значке – сообщение оператора
 * при закрытом чате. Пункт ставится сразу, не дожидаясь Jivo: нажатие до загрузки виджета запоминается
 * и срабатывает, когда он готов.
 * Если нижнего меню на странице нет, остаётся прежняя круглая кнопка .gm-jivo-btn; пока чат открыт,
 * у <html> стоит класс gm-jivo-open – стили её прячут.
 * На ПК скрипт ничего не показывает: и пункт, и кнопка видны только до 767px.
 */
(function (window, document) {
  var OPEN = 'gm-jivo-open';
  var NEW_BTN = 'gm-jivo-btn--new';
  var NEW_NAV = 'gm-jivo-nav--new';
  var ICON = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"'
    + ' stroke-linejoin="round" aria-hidden="true"><path d="M21 11.5a8.4 8.4 0 0 1-12.3 7.4L3 21l2.1-5.7A8.4 8.4 0 1 1 21 11.5z"/></svg>';
  var root = document.documentElement;
  var btn = null;      // круглая кнопка (запасной вариант)
  var nav = null;      // ссылка пункта «Помощь» в нижнем меню
  var unread = false;
  var pending = false; // нажали до загрузки Jivo
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

  function ready() {
    return !!(window.jivo_api && typeof window.jivo_api.open === 'function' && document.querySelector('body > jdiv'));
  }

  function mark(on) {
    unread = !!on;
    if (btn) {
      btn.className = 'gm-jivo-btn' + (unread ? ' ' + NEW_BTN : '');
    }
    if (nav) {
      nav.className = 'ut2-sticky-panel__link gm-jivo-nav' + (unread ? ' ' + NEW_NAV : '');
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

  function open() {
    if (!ready()) {
      pending = true;
      return;
    }
    pending = false;
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
  }

  // Пункт «Помощь» в нижнем меню. Меню может перерисоваться – тогда пункт ставится заново.
  function buildNav() {
    var panel = document.querySelector('.ut2-sticky-panel');
    if (!panel) {
      return false;
    }
    if (nav && panel.contains(nav)) {
      return true;
    }
    var item = document.createElement('div');
    item.className = 'ut2-sticky-panel__item gm-jivo-item';
    nav = document.createElement('a');
    nav.setAttribute('role', 'button');
    nav.setAttribute('tabindex', '0');
    nav.innerHTML = '<i>' + ICON + '</i><span>Помощь</span>';
    nav.addEventListener('click', function (e) {
      e.preventDefault();
      open();
    });
    item.appendChild(nav);
    panel.appendChild(item);
    mark(unread);
    return true;
  }

  // Запасной вариант: круглая кнопка, если нижнего меню на странице нет
  function buildBtn() {
    if (btn || !document.body) {
      return;
    }
    btn = document.createElement('button');
    btn.type = 'button';
    btn.setAttribute('aria-label', 'Написать в чат');
    btn.innerHTML = ICON;
    btn.addEventListener('click', open);
    document.body.appendChild(btn);
    mark(unread);
  }

  function place() {
    if (buildNav()) {
      if (btn) {
        btn.parentNode.removeChild(btn);
        btn = null;
      }
    } else if (ready()) {
      // Кнопку показываем, только когда виджет готов и нажатие сработает
      buildBtn();
    }
  }

  function start() {
    place();
    // Jivo подгружается с задержкой; заодно следим, не перерисовалось ли нижнее меню
    setInterval(function () {
      if (ready() && !hooked) {
        hook();
        try {
          if (window.jivo_api.getUnreadMessagesCount && window.jivo_api.getUnreadMessagesCount() > 0) {
            mark(true);
          }
        } catch (e) {}
        if (pending) {
          open();
        }
      }
      place();
    }, 700);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', start);
  } else {
    start();
  }
})(window, document);
