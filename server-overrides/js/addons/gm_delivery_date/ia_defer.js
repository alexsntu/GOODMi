// Сопутствующие товары в карточке (модуль ab__intelligent_accessories, вкладки «Наушники / Кабели / PowerBank»).
// Модуль сразу при открытии карточки запрашивает товары первой вкладки – 20 товаров с картинками, которые
// держали загрузку страницы. Здесь запрос откладывается: кнопки вкладок временно лишаются класса, по которому
// модуль их находит, и получают его обратно, когда блок подходит к экрану (за 600 px) или через 4 с после
// загрузки страницы, если блок скрыт. Код выполняется до общего запуска скриптов CS-Cart (файл подключён в конце страницы).
// Подключается на витринах GOODMi и Maxmobiles (hooks/index/scripts.post.tpl модуля gm_delivery_date).
// Откат: убрать подключение файла из шаблона (2026-10-14).
(function (_, $) {
  var lists = document.querySelectorAll('.ut2-ab__ia_joins-elements-list');
  if (!lists.length || !('IntersectionObserver' in window)) {
    return;
  }
  Array.prototype.forEach.call(lists, function (list) {
    var tabs = list.querySelectorAll('li.btn-ab__ia_join');
    if (!tabs.length) {
      return;
    }
    Array.prototype.forEach.call(tabs, function (li) {
      li.classList.remove('btn-ab__ia_join');
      li.classList.add('gm-ia-deferred');
    });
    var done = false;
    var io;
    var start = function () {
      if (done) {
        return;
      }
      done = true;
      io.disconnect();
      Array.prototype.forEach.call(tabs, function (li) {
        li.classList.remove('gm-ia-deferred');
        li.classList.add('btn-ab__ia_join');
      });
      $.commonInit($(list.parentNode));
    };
    io = new IntersectionObserver(function (entries) {
      if (entries.some(function (e) { return e.isIntersecting; })) {
        start();
      }
    }, { rootMargin: '600px 0px' });
    io.observe(list);
    window.addEventListener('load', function () {
      setTimeout(start, 4000);
    });
  });
})(Tygh, Tygh.$);
