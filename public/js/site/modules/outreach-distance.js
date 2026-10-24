/* ==========================================================================
   Корни — расчёт удалённости выездного мастер-класса.
   Адрес площадки определяет серверный /geokod (Яндекс.Геокодер с резервом OSM),
   расстояние до офиса считается по координатам, затем JS выбирает тариф из сетки,
   которую страница отдаёт в data-атрибуте. Сетка тарифов не требует fetch.
   ========================================================================== */
(function () {
    "use strict";

    var GEOCODE_DELAY = 700;
    var SUGGEST_DELAY = 250;
    var MIN_QUERY_LENGTH = 3;
    var SUGGEST_LIMIT = 8;
    var DEFAULT_STATUS =
        "Укажите населённый пункт — расстояние от студии рассчитаем автоматически.";

    var kmFormatter = new Intl.NumberFormat("ru-RU", {
        maximumFractionDigits: 1,
    });

    function parseNumber(value) {
        if (value === null || value === undefined || String(value).trim() === "") {
            return null;
        }

        var parsed = parseFloat(String(value).replace(",", "."));

        return isFinite(parsed) ? parsed : null;
    }

    function readOffice(form) {
        var lat = parseNumber(form.getAttribute("data-office-lat"));
        var lng = parseNumber(form.getAttribute("data-office-lng"));

        if (lat === null || lng === null) return null;
        if (lat < -90 || lat > 90 || lng < -180 || lng > 180) return null;

        return [lat, lng];
    }

    function readTariffs(form) {
        var raw = form.getAttribute("data-distance-tariffs");
        if (!raw) return [];

        try {
            var parsed = JSON.parse(raw);
            if (!Array.isArray(parsed)) return [];

            return parsed
                .map(function (row) {
                    var min = parseNumber(row.min);
                    var max = parseNumber(row.max);
                    var surcharge = parseInt(row.surcharge, 10);

                    return {
                        min: min === null ? 0 : Math.max(0, min),
                        max: max === null ? null : Math.max(0, max),
                        surcharge:
                            isNaN(surcharge) || surcharge < 0
                                ? 0
                                : surcharge,
                    };
                })
                .sort(function (a, b) {
                    return a.min - b.min;
                });
        } catch (error) {
            return [];
        }
    }

    function tariffCovers(tariff, km) {
        if (km < tariff.min) return false;
        return tariff.max === null || km <= tariff.max;
    }

    /* Как и на сервере: сначала точное попадание в интервал, затем самый
       дальний тариф ниже расстояния (защита от «дыр» и выездов за максимум). */
    function pickTariff(tariffs, km) {
        for (var i = 0; i < tariffs.length; i++) {
            if (tariffCovers(tariffs[i], km)) return tariffs[i];
        }

        var fallback = null;
        tariffs.forEach(function (tariff) {
            if (km >= tariff.min) fallback = tariff;
        });

        return fallback;
    }

    /* Запасной расчёт по формуле гаверсинуса, если coordSystem недоступен. */
    function haversineMeters(from, to) {
        var radius = 6371000;
        var rad = Math.PI / 180;
        var dLat = (to[0] - from[0]) * rad;
        var dLng = (to[1] - from[1]) * rad;
        var a =
            Math.sin(dLat / 2) * Math.sin(dLat / 2) +
            Math.cos(from[0] * rad) *
                Math.cos(to[0] * rad) *
                Math.sin(dLng / 2) *
                Math.sin(dLng / 2);

        return 2 * radius * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    }

    function distanceKm(office, target) {
        var geo = window.ymaps && window.ymaps.coordSystem && window.ymaps.coordSystem.geo;
        var meters =
            geo && typeof geo.distance === "function"
                ? geo.distance(office, target)
                : haversineMeters(office, target);
        var parsed = parseFloat(meters);

        return isFinite(parsed) && parsed >= 0 ? parsed / 1000 : null;
    }

    function geocodeYandex(query) {
        if (!window.ymaps || typeof window.ymaps.geocode !== "function") {
            return Promise.resolve(null);
        }

        try {
            return window.ymaps
                .geocode(query, { results: 1 })
                .then(function (result) {
                    var object = result.geoObjects.get(0);
                    if (!object) return null;

                    var coords = object.geometry.getCoordinates();
                    return Array.isArray(coords) && coords.length === 2
                        ? coords
                        : null;
                })
                .catch(function () {
                    return null;
                });
        } catch (error) {
            return Promise.resolve(null);
        }
    }

    /* Резервный геокодер OpenStreetMap. Нужен, когда у Яндекс.Карт нет
       API-ключа (геокодер и подсказки в этом случае недоступны), но карта
       ещё загружается. Запрос уходит напрямую: Nominatim отдаёт CORS. */
    function geocodeOpenStreetMap(query) {
        if (typeof window.fetch !== "function") {
            return Promise.resolve(null);
        }

        var url =
            "https://nominatim.openstreetmap.org/search?format=jsonv2&limit=1" +
            "&addressdetails=0&accept-language=ru&q=" +
            encodeURIComponent(query);

        return window
            .fetch(url, {
                method: "GET",
                headers: { Accept: "application/json" },
                credentials: "omit",
            })
            .then(function (response) {
                return response.ok ? response.json() : null;
            })
            .then(function (rows) {
                if (!Array.isArray(rows) || rows.length === 0) return null;

                var lat = parseFloat(rows[0].lat);
                var lng = parseFloat(rows[0].lon);

                if (!isFinite(lat) || !isFinite(lng)) return null;
                if (lat < -90 || lat > 90 || lng < -180 || lng > 180) return null;

                return [lat, lng];
            })
            .catch(function () {
                return null;
            });
    }

    /* Серверный геокодер использует активный ключ API Геокодера и не
       раскрывает его в браузере. При недоступности — JS-геокодер Яндекса
       и прямой запрос к OpenStreetMap. */
    function geocodeServer(query, url) {
        if (!url || typeof window.fetch !== "function") {
            return Promise.resolve(null);
        }

        var separator = url.indexOf("?") === -1 ? "?" : "&";

        return window
            .fetch(url + separator + "q=" + encodeURIComponent(query), {
                method: "GET",
                headers: { Accept: "application/json" },
                credentials: "same-origin",
            })
            .then(function (response) {
                return response.ok ? response.json() : null;
            })
            .then(function (data) {
                if (!data) return null;

                var lat = parseNumber(data.lat);
                var lng = parseNumber(data.lng);

                if (lat === null || lng === null) return null;
                if (lat < -90 || lat > 90 || lng < -180 || lng > 180) return null;

                return [lat, lng];
            })
            .catch(function () {
                return null;
            });
    }

    var geocodeCache = {};
    var YANDEX_GEOCODE_TIMEOUT = 5000;

    /* Не даём зависшему запросу Яндекса заблокировать резервный геокодер. */
    function withTimeout(promise, ms) {
        return new Promise(function (resolve) {
            var settled = false;
            var timer = window.setTimeout(function () {
                if (settled) return;
                settled = true;
                resolve(null);
            }, ms);

            promise
                .then(function (value) {
                    if (settled) return;
                    settled = true;
                    window.clearTimeout(timer);
                    resolve(value);
                })
                .catch(function () {
                    if (settled) return;
                    settled = true;
                    window.clearTimeout(timer);
                    resolve(null);
                });
        });
    }

    function geocode(query, preferYandex, geocodeUrl) {
        var key = query.toLowerCase();

        if (Object.prototype.hasOwnProperty.call(geocodeCache, key)) {
            return Promise.resolve(geocodeCache[key]);
        }

        return geocodeServer(query, geocodeUrl).then(function (coords) {
            if (coords) {
                geocodeCache[key] = coords;
                return coords;
            }

            var primary = preferYandex
                ? withTimeout(geocodeYandex(query), YANDEX_GEOCODE_TIMEOUT)
                : Promise.resolve(null);

            return primary.then(function (jsCoords) {
                if (jsCoords) {
                    geocodeCache[key] = jsCoords;
                    return jsCoords;
                }

                return geocodeOpenStreetMap(query).then(function (fallback) {
                    if (fallback) geocodeCache[key] = fallback;
                    return fallback;
                });
            });
        });
    }

    function initSuggest(input) {
        var list = document.getElementById("o-location-suggestions");
        if (
            !list ||
            !window.ymaps ||
            typeof window.ymaps.suggest !== "function"
        ) {
            return;
        }

        var timer = 0;

        input.addEventListener("input", function () {
            window.clearTimeout(timer);
            timer = window.setTimeout(function () {
                var query = input.value.trim();
                if (query.length < MIN_QUERY_LENGTH) {
                    list.innerHTML = "";
                    return;
                }

                try {
                    var result = window.ymaps.suggest(query);
                    if (!result || typeof result.then !== "function") return;

                    result
                        .then(function (items) {
                            if (!Array.isArray(items)) return;

                            list.innerHTML = "";
                            items
                                .slice(0, SUGGEST_LIMIT)
                                .forEach(function (item) {
                                    var value = item && item.value;
                                    if (!value) return;

                                    var option = document.createElement("option");
                                    option.value = String(value);
                                    list.appendChild(option);
                                });
                        })
                        .catch(function () {});
                } catch (error) {
                    /* Без подсказок геокодер продолжит работать по введённому тексту. */
                }
            }, SUGGEST_DELAY);
        });
    }

    function initForm(form) {
        var input = form.querySelector("[data-outreach-location]");
        if (!input) return;

        var hidden = form.querySelector("[data-outreach-distance]");
        var status = form.querySelector("[data-outreach-distance-status]");
        var root = form.closest(".calc") || form;
        var row = root.querySelector("[data-outreach-distance-row]");
        var office = readOffice(form);
        var tariffs = readTariffs(form);
        var preferYandex = form.getAttribute("data-yandex-geocoder") !== "0";
        var geocodeUrl = form.getAttribute("data-geocode-url") || "";
        var requestId = 0;
        var timer = 0;

        function setStatus(text) {
            if (status) status.textContent = text;
        }

        function dispatch() {
            form.dispatchEvent(new Event("outreach:distance"));
        }

        function clearState(message) {
            requestId++;
            if (hidden) hidden.value = "";
            form.setAttribute("data-distance-per-participant", "0");
            if (row) row.hidden = true;
            setStatus(message || DEFAULT_STATUS);
            dispatch();
        }

        function applyDistance(km) {
            if (row) row.hidden = false;
            if (hidden) hidden.value = km.toFixed(1);

            var tariff = pickTariff(tariffs, km);
            var surcharge = tariff ? tariff.surcharge : 0;
            form.setAttribute("data-distance-per-participant", String(surcharge));

            var distanceLabel = kmFormatter.format(km) + " км";

            if (!tariffs.length) {
                setStatus(
                    "Расстояние от студии: " +
                        distanceLabel +
                        ". Наценка за удалённость не настроена.",
                );
            } else if (surcharge > 0) {
                setStatus(
                    "Расстояние от студии: " +
                        distanceLabel +
                        " · +" +
                        kmFormatter.format(surcharge) +
                        " ₽ за участника.",
                );
            } else {
                setStatus(
                    "Расстояние от студии: " +
                        distanceLabel +
                        " — без наценки за удалённость.",
                );
            }

            dispatch();
        }

        function resolve() {
            window.clearTimeout(timer);

            var query = input.value.trim();

            if (query.length < MIN_QUERY_LENGTH) {
                clearState();
                return;
            }

            var current = ++requestId;
            setStatus("Определяем расстояние…");

            geocode(query, preferYandex, geocodeUrl).then(function (coords) {
                if (current !== requestId) return;

                if (!coords) {
                    clearState(
                        "Не удалось определить адрес. Уточните населённый пункт, например «Брянск, ул. Московская, 23», — расстояние проверит менеджер.",
                    );
                    return;
                }

                var km = distanceKm(office, coords);

                if (km === null) {
                    clearState(
                        "Не удалось рассчитать расстояние. Его уточнит менеджер при подтверждении.",
                    );
                    return;
                }

                applyDistance(km);
            });
        }

        function schedule() {
            window.clearTimeout(timer);
            timer = window.setTimeout(resolve, GEOCODE_DELAY);
        }

        if (!office) {
            clearState(
                "Координаты студии не заданы в настройках — расстояние уточнит менеджер.",
            );
            return;
        }

        form.addEventListener("reset", function () {
            window.clearTimeout(timer);
            window.setTimeout(function () {
                clearState();
            }, 0);
        });

        input.addEventListener("input", schedule);
        input.addEventListener("change", resolve);
        if (preferYandex) initSuggest(input);

        if (input.value.trim().length >= MIN_QUERY_LENGTH) {
            resolve();
        }
    }

    function init() {
        var forms = document.querySelectorAll(
            "form[data-outreach][data-distance-tariffs]",
        );
        if (!forms.length) return;

        var started = false;
        var start = function () {
            if (started) return;
            started = true;
            forms.forEach(initForm);
        };

        /* Если Яндекс.Карты не загрузились, расстояние всё равно считается:
           координаты берутся из резервного геокодера, а километраж — по
           формуле гаверсинуса. */
        if (window.ymaps && typeof window.ymaps.ready === "function") {
            window.ymaps.ready(start);
            window.setTimeout(start, 2500);
            return;
        }

        start();
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", init);
    } else {
        init();
    }
})();
