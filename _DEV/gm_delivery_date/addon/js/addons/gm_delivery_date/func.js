/**
 * GOODMi: сроки получения товара.
 * Находит на странице товары (карточка товара, карточки каталога, строки корзины), одним запросом
 * спрашивает у сервера сроки и вставляет готовые блоки. Работает и для подгружаемых блоков
 * (событие ce.commoninit срабатывает после каждой подгрузки). При смене города в шапке блоки
 * пересчитываются без перезагрузки страницы. На оформлении заказа в конце формы выводятся карточки
 * с фотографиями товаров и сроком по выбранному способу получения.
 */
(function (_, $) {
  var MARK = 'data-gm-dd';
  var pending = {};
  var timer = null;
  var coTimer = null;
  var coBusy = false; // идёт наш собственный запрос – его завершение не должно запускать новый

  function pidFrom($scope) {
    var $input = $scope.find('input[name$="[product_id]"]').first();
    var pid = parseInt($input.val(), 10);
    return pid > 0 ? pid : 0;
  }

  function addSlot(pid, view, $anchor, how) {
    if (!pid || !$anchor.length || $anchor.attr(MARK)) {
      return;
    }
    $anchor.attr(MARK, '1');
    (pending[pid] = pending[pid] || []).push({view: view, $anchor: $anchor, how: how});
  }

  function collect(context) {
    var $ctx = $(context || document);

    // Карточка товара, ПК: в конец блока покупки
    $ctx.find('.ut2-pb__main-content-box').addBack('.ut2-pb__main-content-box').each(function () {
      var $box = $(this);
      addSlot(pidFrom($box.closest('form').length ? $box.closest('form') : $box), 'card', $box, 'append');
    });

    // Карточка товара, телефон: у мобильного шаблона нет блока покупки – ставим под кнопкой «В корзину»
    $ctx.find('.ut2-pb-mobile .ut2-pb__button').each(function () {
      var $btn = $(this);
      var $scope = $btn.closest('form').length ? $btn.closest('form') : $btn.closest('.ut2-pb-mobile');
      addSlot(pidFrom($scope), 'card', $btn, 'after');
    });

    // Каталог, витрины, избранное: под строкой «В наличии»
    $ctx.find('.ut2-gl__item').addBack('.ut2-gl__item').each(function () {
      var $item = $(this);
      addSlot(pidFrom($item), 'line', $item.find('.ut2-gl__amount').first(), 'after');
    });

    // Корзина: под кодом товара
    $ctx.find('.ty-cart-content tbody tr').each(function () {
      var $row = $(this);
      var $in = $row.find('.abt__ut2--content__description__in').first();
      var $sku = $in.find('.ty-cart-content__sku').first();
      addSlot(pidFrom($row), 'cart', $sku.length ? $sku : $in.children().first(), 'after');
    });
  }

  function flush() {
    timer = null;
    var batch = pending;
    pending = {};
    var ids = Object.keys(batch);
    if (!ids.length) {
      return;
    }
    // Для каких товаров нужны условия покупки (гарантия, возврат, оплата, доставка) – только карточка товара
    var termIds = ids.filter(function (pid) {
      return batch[pid].some(function (slot) {
        return slot.view === 'card';
      });
    });

    $.ceAjax('request', fn_url('gm_delivery_date.estimate'), {
      method: 'get',
      hidden: true,
      caching: false,
      // _t – чтобы повторный запрос тех же товаров (после смены города) не брался из памяти страницы
      data: {ids: ids.join(','), terms: termIds.join(','), _t: Date.now()},
      callback: function (data) {
        var items = (data && data.gm_dd) || {};
        var terms = (data && data.gm_dd_terms) || {};
        ids.forEach(function (pid) {
          var html = items[pid] || {};
          batch[pid].forEach(function (slot) {
            // Условия покупки – только в карточке товара, под блоком сроков (показываем и когда сроков нет)
            var block = (html[slot.view] || '') + (slot.view === 'card' ? terms[pid] || '' : '');
            if (!block) {
              return;
            }
            if (slot.how === 'append') {
              slot.$anchor.append(block);
            } else {
              slot.$anchor.after(block);
            }
          });
        });
      }
    });
  }

  function run(context) {
    collect(context);
    if (timer === null) {
      timer = setTimeout(flush, 150);
    }
  }

  // Оформление заказа: в конце формы карточки с фотографиями товаров, по одной на каждый срок получения
  function renderOrder(r) {
    var $steps = $('#litecheckout_form .litecheckout__ab_steps').first();
    $('.gm-dd-order').remove();
    if (!$steps.length || !r.groups || !r.groups.length) {
      return;
    }
    r.groups.forEach(function (g, i) {
      var $card = $('<div class="gm-dd-order"></div>');
      var $head = $('<div class="gm-dd-order__head"></div>').append($('<div class="gm-dd-order__title"></div>').text(g.title));
      if (i === 0 && r.edit_url) {
        $head.append($('<a class="gm-dd-order__edit"></a>').attr('href', r.edit_url).text('Изменить заказ'));
      }
      var $items = $('<div class="gm-dd-order__items"></div>');
      (g.items || []).forEach(function (it) {
        var $a = $('<a class="gm-dd-order__item" target="_blank" rel="noopener"></a>').attr('href', it.url).attr('title', it.name);
        if (it.img) {
          $a.append($('<img loading="lazy">').attr('src', it.img).attr('alt', it.name));
        }
        if (it.amount > 1) {
          $a.append($('<span class="gm-dd-order__qty"></span>').text('\u00d7 ' + it.amount));
        }
        $items.append($a);
      });
      $card.append($head, $('<div class="gm-dd-order__sub"></div>').text(g.sub), $items);
      // Сроки разные – подсказываем, когда можно получить всё сразу
      if (i === r.groups.length - 1 && r.groups.length > 1 && r.total && !r.same) {
        $card.append($('<div class="gm-dd-order__foot"></div>').text('Весь заказ целиком \u2013 ' + r.total));
      }
      $steps.append($card);
    });
  }

  function checkout() {
    if (!$('#litecheckout_form .litecheckout__ab_steps').length) {
      return;
    }
    clearTimeout(coTimer);
    coTimer = setTimeout(function () {
      coBusy = true;
      $.ceAjax('request', fn_url('gm_delivery_date.checkout'), {
        method: 'get',
        hidden: true,
        caching: false,
        data: {_t: Date.now()},
        callback: function (data) {
          setTimeout(function () {
            coBusy = false;
          }, 500);
          renderOrder((data && data.gm_dd_cart) || {});
        }
      });
    }, 250);
  }

  $.ceEvent('on', 'ce.commoninit', function (context) {
    run(context);
    checkout();
  });

  // Способ получения или город на оформлении изменились – пересчитать карточки товаров
  $.ceEvent('on', 'ce.ajaxdone', function (elms, scripts, params) {
    var url = (params && (params.original_url || params.url)) || '';
    if (!coBusy && String(url).indexOf('gm_delivery_date') === -1) {
      checkout();
    }
  });

  // Оформление заказа: нажатие на город открывает то же окно выбора города, что и в шапке.
  // После выбора CS-Cart сам перезагружает оформление с новым городом (lite_checkout.js).
  // На телефоне строки с городом в шапке нет, поэтому окно открываем собственной скрытой ссылкой.
  // С клавиатуры поле доступно как раньше – с подсказками под ним.
  $(_.doc).on('mousedown', '#litecheckout_form [data-ca-lite-checkout-element="city-autocomplete"]', function (e) {
    if (e.which > 1) {
      return;
    }
    var $opener = $('#gm_co_city_opener');
    if (!$opener.length) {
      $opener = $('<a id="gm_co_city_opener" class="cm-dialog-opener cm-dialog-auto-size hidden" rel="nofollow"></a>')
        .attr('href', fn_url('geo_maps.customer_geolocation'))
        .attr('data-ca-target-id', 'gm_co_city_dialog')
        .attr('data-ca-dialog-title', 'Местоположение покупателя')
        .appendTo('body');
    }
    e.preventDefault();
    $(this).trigger('blur');
    $opener.trigger('click');
  });

  // Покупатель сменил город в шапке – убрать старые сроки и посчитать заново
  $.ceEvent('on', 'ce:geomap:location_set_after', function () {
    $('.gm-dd, .gm-terms').remove();
    $('[' + MARK + ']').removeAttr(MARK);
    pending = {};
    run(document);
    checkout();
  });
})(Tygh, Tygh.$);
