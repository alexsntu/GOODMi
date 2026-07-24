{**
  GOODMi — кастомный виджет выбора города (замена карты в диалоге геолокации)
  Путь установки в теме goodmi.ru:
    templates/addons/geo_maps/views/geo_maps/customer_geolocation.tpl
  (файла там сейчас нет — тема наследует базовый шаблон с картой;
  создание этого файла ничего не ломает на других витринах этой сборки)

  Логика сохранения повторяет js/addons/geo_maps/locator.js -> methods.setLocation():
  POST geo_maps.set_location, data.location = {country, state_code, locality, locality_text, postal_code}.
  Поле `locality` (не только `locality_text`) обязательно — на него подписан хук
  fn_cities_geo_maps_set_customer_location_pre() из аддона cities: он автоматически
  находит state_code/postal_code по названию города в таблице ?:cities, если мы их
  не передали сами. Для городов из живого поиска (autocomplete) мы передаём state_code
  и zipcode напрямую — это точнее, чем полагаться на LIKE-поиск хука (у одноимённых
  городов в разных областях иначе может быть коллизия).

  Поиск — через уже существующий аддон cities:
    GET/POST dispatch=city.autocomplete_city&q=...&check_country=RU&items_per_page=30
  Ответ (AJAX): { autocomplete: [{code, value, label, country, country_code, state, state_code, zipcode}, ...] }
  См. app/addons/cities/func.php -> fn_cities_find_cities() / fn_cities_format_to_autocomplete().
  Требует AJAX_REQUEST — вызывать только через $.ceAjax, не прямым переходом по URL.

  После успешного сохранения: обновляем текст в [data-ca-geo-map-location-element="location"],
  триггерим "ce:geomap:location_set_after" (совместимость с остальным кодом аддона,
  например виджетом расчёта доставки на карточке товара) и закрываем диалог через
  уже существующий .cm-dialog-closer.

  Заголовок диалога ("Местоположение покупателя") берётся из языковой переменной
  geo_maps.select_your_city — чтобы поменять на "Ваш регион", правится в
  Дизайн -> Переводы, отдельно этот файл трогать не нужно.
**}
{if $addons.geo_maps.status == "A"}
<div class="gm-geo-picker">

  <div class="gm-geo-picker__search">
    <svg class="gm-geo-picker__search-icon" width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><circle cx="8" cy="8" r="6" stroke="currentColor" stroke-width="1.6"/><path d="M12.5 12.5L16 16" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
    <input type="text" class="gm-geo-picker__search-input" data-ca-gm-geo-search placeholder="Найдите свой город" autocomplete="off">
  </div>

  <div class="gm-geo-picker__curated" data-ca-gm-geo-curated>
    <div class="gm-geo-picker__priority">
      <div class="gm-geo-picker__priority-label">Наши магазины</div>
      <div class="gm-geo-picker__priority-list">
        <a href="#" class="gm-geo-picker__chip" data-city="Севастополь">Севастополь</a>
        <a href="#" class="gm-geo-picker__chip" data-city="Симферополь">Симферополь</a>
        <a href="#" class="gm-geo-picker__chip" data-city="Ялта">Ялта</a>
      </div>
    </div>

    <div class="gm-geo-picker__grid">
      <a href="#" class="gm-geo-picker__city" data-city="Алушта">Алушта</a>
      <a href="#" class="gm-geo-picker__city" data-city="Керчь">Керчь</a>
      <a href="#" class="gm-geo-picker__city" data-city="Судак">Судак</a>
      <a href="#" class="gm-geo-picker__city" data-city="Красноперекопск">Красноперекопск</a>
      <a href="#" class="gm-geo-picker__city" data-city="Феодосия">Феодосия</a>
      <a href="#" class="gm-geo-picker__city" data-city="Черноморское">Черноморское</a>
      <a href="#" class="gm-geo-picker__city" data-city="Евпатория">Евпатория</a>
      <a href="#" class="gm-geo-picker__city" data-city="Джанкой">Джанкой</a>
      <a href="#" class="gm-geo-picker__city" data-city="Москва">Москва</a>
      <a href="#" class="gm-geo-picker__city" data-city="Краснодар">Краснодар</a>
      <a href="#" class="gm-geo-picker__city" data-city="Санкт-Петербург">Санкт-Петербург</a>
      <a href="#" class="gm-geo-picker__city" data-city="Воронеж">Воронеж</a>
      <a href="#" class="gm-geo-picker__city" data-city="Новороссийск">Новороссийск</a>
    </div>
  </div>

  <div class="gm-geo-picker__results hidden" data-ca-gm-geo-results></div>
  <div class="gm-geo-picker__loading hidden" data-ca-gm-geo-loading>Ищем города&#8230;</div>
  <div class="gm-geo-picker__empty hidden" data-ca-gm-geo-empty>Город не найден</div>

  <a class="cm-dialog-closer" data-ca-gm-geo-closer style="display:none;" aria-hidden="true"></a>
</div>

<style>
.gm-geo-picker {
  --gm-accent: #FF6900;
  --gm-accent-dark: #E55A00;
  --gm-accent-tint: #FFF0E6;
  --gm-text: #1C1C1C;
  --gm-text-secondary: #6B6B6B;
  --gm-border: #E5E5E5;
  font-family: "MiSans", "PingFang SC", "Helvetica Neue", system-ui, -apple-system, Arial, sans-serif;
  padding: 4px 4px 20px;
  width: 100%;
  max-width: 640px;
  box-sizing: border-box;
}

.gm-geo-picker * {
  box-sizing: border-box;
}

.gm-geo-picker .hidden {
  display: none !important;
}

.gm-geo-picker__search {
  position: relative;
  margin-bottom: 24px;
}

.gm-geo-picker__search-icon {
  position: absolute;
  top: 50%;
  left: 16px;
  transform: translateY(-50%);
  color: var(--gm-text-secondary);
  pointer-events: none;
}

.gm-geo-picker__search-input {
  width: 100%;
  height: 48px;
  border: none;
  background: #F5F5F5;
  border-radius: 12px;
  padding: 0 16px 0 44px;
  font-size: 15px;
  color: var(--gm-text);
  font-family: inherit;
  outline: none;
  transition: background-color 0.18s ease, box-shadow 0.18s ease;
}

.gm-geo-picker__search-input::placeholder {
  color: #ADADAD;
}

.gm-geo-picker__search-input:focus {
  background: #fff;
  box-shadow: 0 0 0 2px var(--gm-accent);
}

.gm-geo-picker__priority {
  margin-bottom: 24px;
  padding-bottom: 20px;
  border-bottom: 1px solid var(--gm-border);
}

.gm-geo-picker__priority-label {
  font-size: 12px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: var(--gm-text-secondary);
  margin-bottom: 12px;
}

.gm-geo-picker__priority-list {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

.gm-geo-picker__chip {
  display: inline-flex;
  align-items: center;
  padding: 10px 20px;
  background: var(--gm-accent-tint);
  border: 1px solid transparent;
  border-radius: 100px;
  color: var(--gm-accent-dark);
  font-size: 15px;
  font-weight: 600;
  text-decoration: none;
  transition: background-color 0.18s ease, border-color 0.18s ease;
}

.gm-geo-picker__chip:hover {
  background: #fff;
  border-color: var(--gm-accent);
}

.gm-geo-picker__grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 8px 20px;
}

.gm-geo-picker__city {
  position: relative;
  display: inline-block;
  width: fit-content;
  padding: 6px 0;
  color: var(--gm-text);
  font-size: 15px;
  text-decoration: none;
  transition: color 0.18s ease;
}

.gm-geo-picker__city::before {
  content: '';
  position: absolute;
  left: 0;
  bottom: 4px;
  width: 0%;
  height: 1px;
  background: var(--gm-accent);
  transition: width 0.25s ease;
}

.gm-geo-picker__city:hover {
  color: var(--gm-accent);
}

.gm-geo-picker__city:hover::before {
  width: 100%;
}

.gm-geo-picker__results {
  display: flex;
  flex-direction: column;
  gap: 2px;
  max-height: 360px;
  overflow-y: auto;
}

.gm-geo-picker__result {
  display: flex;
  align-items: baseline;
  gap: 8px;
  padding: 10px 8px;
  border-radius: 10px;
  color: var(--gm-text);
  text-decoration: none;
  transition: background-color 0.15s ease;
}

.gm-geo-picker__result:hover {
  background: var(--gm-accent-tint);
}

.gm-geo-picker__result-city {
  font-size: 15px;
  font-weight: 500;
}

.gm-geo-picker__result-state {
  font-size: 13px;
  color: var(--gm-text-secondary);
}

.gm-geo-picker__loading,
.gm-geo-picker__empty {
  padding: 24px 0;
  text-align: center;
  color: var(--gm-text-secondary);
  font-size: 14px;
}

@media (max-width: 640px) {
  .gm-geo-picker__grid {
    grid-template-columns: repeat(2, 1fr);
  }
}
</style>

<script>
(function (_, $) {
  var $root = $('.gm-geo-picker');
  if (!$root.length) {
    return;
  }

  var $search  = $root.find('[data-ca-gm-geo-search]');
  var $curated = $root.find('[data-ca-gm-geo-curated]');
  var $results = $root.find('[data-ca-gm-geo-results]');
  var $loading = $root.find('[data-ca-gm-geo-loading]');
  var $empty   = $root.find('[data-ca-gm-geo-empty]');
  var $closer  = $root.find('[data-ca-gm-geo-closer]');

  var search_timer = null;
  var min_query_length = 2;
  var debounce_delay = 300;

  function selectCity(city) {
    var country = city.country_code || 'RU';
    var name = city.value || city.city;

    $.ceAjax('request', fn_url('geo_maps.set_location'), {
      method: 'post',
      data: {
        location: {
          country: country,
          state_code: city.state_code || '',
          locality: name,
          locality_text: name,
          postal_code: city.zipcode || ''
        },
        auto_detect: 0
      },
      hidden: true,
      caching: false,
      callback: function (response) {
        if (!response.is_can_select_location) {
          return;
        }

        $('[data-ca-geo-map-location-element="location"]').text(response.city);
        $('[data-ca-geo-map-location-element="location_block"]').data('caGeoMapLocationIsLocationDetected', true);

        $.ceEvent('trigger', 'ce:geomap:location_set_after', [
          { country: country, locality_text: name },
          $('[data-ca-geo-map-location-element="location_block"]'),
          response,
          0
        ]);

        $closer.trigger('click');
      }
    });
  }

  // Клик по чипам "Наши магазины" и статичной сетке городов —
  // state_code/postal_code достанет хук cities по названию (locality)
  $curated.on('click', '[data-city]', function (e) {
    e.preventDefault();
    selectCity({ country_code: 'RU', value: $(this).data('city') });
  });

  function renderResults(cities) {
    $results.empty();

    if (!cities.length) {
      $empty.removeClass('hidden');
      return;
    }

    $empty.addClass('hidden');

    cities.forEach(function (city) {
      var $item = $('<a href="#" class="gm-geo-picker__result"></a>');
      $('<span class="gm-geo-picker__result-city"></span>').text(city.value).appendTo($item);
      if (city.state) {
        $('<span class="gm-geo-picker__result-state"></span>').text(city.state).appendTo($item);
      }
      $item.on('click', function (e) {
        e.preventDefault();
        selectCity(city);
      });
      $results.append($item);
    });
  }

  $search.on('input', function () {
    var query = $(this).val().trim();

    clearTimeout(search_timer);

    if (!query) {
      $curated.removeClass('hidden');
      $results.addClass('hidden').empty();
      $empty.addClass('hidden');
      $loading.addClass('hidden');
      return;
    }

    $curated.addClass('hidden');
    $results.removeClass('hidden');
    $empty.addClass('hidden');

    if (query.length < min_query_length) {
      $loading.addClass('hidden');
      return;
    }

    $loading.removeClass('hidden');

    search_timer = setTimeout(function () {
      $.ceAjax('request', fn_url('city.autocomplete_city'), {
        method: 'get',
        data: {
          q: query,
          check_country: 'RU',
          items_per_page: 30
        },
        hidden: true,
        caching: false,
        callback: function (response) {
          $loading.addClass('hidden');
          renderResults((response && response.autocomplete) || []);
        }
      });
    }, debounce_delay);
  });
})(Tygh, Tygh.$);
</script>
{/if}
