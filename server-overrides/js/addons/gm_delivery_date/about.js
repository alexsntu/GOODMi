/**
 * GOODMi: кнопка в заголовке блока «О товаре» в карточке товара (шаблон goodmi_card).
 * Заголовок и кнопку выдаёт сам шаблон: на ПК – «Перейти к описанию», на телефоне – «Все характеристики».
 * Скрипт только плавно прокручивает страницу к нужному разделу: на странице задан <base href>, поэтому
 * обычный переход по «#description» увёл бы на главную. Куда прокручивать, берётся из data-gm-about-target
 * (по умолчанию – к описанию). Отступ под закреплённую шапку задан в стилях (scroll-margin-top).
 * До 2026-10-09 скрипт ещё и дорисовывал заголовок на старом шаблоне карточки – это убрано вместе с шаблоном.
 */
(function (_, $) {
  var TARGET = 'description';

  $(_.doc).on('click', '.gm-about__link', function (e) {
    var target = document.getElementById(this.getAttribute('data-gm-about-target') || TARGET);
    e.preventDefault();
    if (target) {
      target.scrollIntoView({behavior: 'smooth', block: 'start'});
    }
  });
})(Tygh, Tygh.$);
