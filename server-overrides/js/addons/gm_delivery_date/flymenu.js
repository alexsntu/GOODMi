// Мобильное каталог-меню (выезжающее меню темы, блок 1208): подраздел с моделями раскрывается списком на месте,
// как у М.Видео, вместо перехода на отдельный экран. Нажатие перехватывается раньше обработчика темы
// (abt__ut2_fly_menu.js переводит на экран моделей); открыт один подраздел, повторное нажатие сворачивает.
// Первой строкой в раскрытом списке – «Смотреть все» (ссылка на сам подраздел из скрытой ссылки темы).
// Подразделы без моделей не затрагиваются – ведут на страницу как раньше. Стили – секция 67. 2026-10-14.
(function () {
  var OPEN = 'gm-acc-open';

  var addAllLink = function (lsl) {
    var list = lsl.querySelector(':scope > .ut2-tlw');
    var src = lsl.querySelector(':scope > p > a.hidden');
    if (!list || !src || list.querySelector('.gm-acc-all')) {
      return;
    }
    var p = document.createElement('p');
    p.className = 'gm-acc-all';
    var a = document.createElement('a');
    a.href = src.href;
    a.textContent = 'Смотреть все';
    p.appendChild(a);
    list.insertBefore(p, list.firstChild);
  };

  document.addEventListener('click', function (e) {
    var head = e.target.closest && e.target.closest('.ut2-fm .ut2-lsl > p');
    if (!head) {
      return;
    }
    var lsl = head.parentElement;
    if (!lsl.querySelector(':scope > .ut2-tlw') || lsl.classList.contains('active')) {
      return;
    }
    e.preventDefault();
    e.stopPropagation();
    var open = !lsl.classList.contains(OPEN);
    Array.prototype.forEach.call(lsl.parentElement.querySelectorAll(':scope > .ut2-lsl.' + OPEN), function (el) {
      el.classList.remove(OPEN);
    });
    if (open) {
      addAllLink(lsl);
      lsl.classList.add(OPEN);
    }
  }, true);
})();
