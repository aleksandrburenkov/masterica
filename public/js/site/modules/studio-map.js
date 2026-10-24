/* ==========================================================================
   Корни — модуль «Карта студии» на Яндекс.Картах (JavaScript API 2.1).
   Координаты, масштаб и текст берутся из data-атрибутов, которые заполняет
   страница контактов из настроек студии.
   ========================================================================== */
(function () {
    "use strict";

    var DEFAULT_ZOOM = 17;
    var MIN_ZOOM = 1;
    var MAX_ZOOM = 19;

    function parseCoordinate(value) {
        if (value === null || value === undefined || String(value).trim() === "") {
            return null;
        }

        var parsed = parseFloat(String(value).replace(",", "."));

        return isFinite(parsed) ? parsed : null;
    }

    function parseZoom(value) {
        var parsed = parseInt(value, 10);

        if (!isFinite(parsed)) {
            return DEFAULT_ZOOM;
        }

        return Math.min(MAX_ZOOM, Math.max(MIN_ZOOM, parsed));
    }

    function escapeHtml(value) {
        return String(value).replace(/[&<>"']/g, function (character) {
            return {
                "&": "&amp;",
                "<": "&lt;",
                ">": "&gt;",
                '"': "&quot;",
                "'": "&#039;",
            }[character];
        });
    }

    function buildBalloonBody(address, hours) {
        var rows = [address, hours]
            .filter(function (value) {
                return value;
            })
            .map(function (value) {
                return "<p>" + escapeHtml(value) + "</p>";
            });

        return '<div class="studio-map__balloon">' + rows.join("") + "</div>";
    }

    function initMap(root) {
        if (root.classList.contains("is-ready")) return;

        var container = root.querySelector("[data-studio-map-canvas]");
        if (!container || !container.id) return;

        if (typeof window.ymaps === "undefined") return;

        var lat = parseCoordinate(root.getAttribute("data-lat"));
        var lng = parseCoordinate(root.getAttribute("data-lng"));
        if (lat === null || lng === null) return;
        if (lat < -90 || lat > 90 || lng < -180 || lng > 180) return;

        var zoom = parseZoom(root.getAttribute("data-zoom"));
        var title = root.getAttribute("data-title") || "Студия";
        var address = root.getAttribute("data-address") || "";
        var hours = root.getAttribute("data-hours") || "";
        var iconHref = root.getAttribute("data-icon") || "";

        window.ymaps.ready(function () {
            var map = new window.ymaps.Map(
                container.id,
                {
                    center: [lat, lng],
                    zoom: zoom,
                    type: "yandex#map",
                    controls: [],
                },
                {
                    suppressMapOpenBlock: true,
                    minZoom: MIN_ZOOM,
                    maxZoom: MAX_ZOOM,
                },
            );

            var zoomControl = new window.ymaps.control.ZoomControl({
                options: {
                    size: "small",
                    float: "none",
                    position: { right: 10, top: 10 },
                },
            });

            map.controls.add(zoomControl);

            var iconOptions = iconHref
                ? {
                      iconLayout: "default#image",
                      iconImageHref: iconHref,
                      iconImageSize: [48, 60],
                      iconImageOffset: [-24, -60],
                      hideIconOnBalloonOpen: false,
                  }
                : {
                      preset: "islands#violetIcon",
                  };

            var placemark = new window.ymaps.Placemark(
                [lat, lng],
                {
                    hintContent: escapeHtml(title),
                    balloonContentHeader: escapeHtml(title),
                    balloonContentBody: buildBalloonBody(address, hours),
                },
                iconOptions,
            );

            map.geoObjects.add(placemark);

            /* Колесо мыши включается после явного клика по карте,
               чтобы она не перехватывала прокрутку страницы. */
            map.behaviors.disable("scrollZoom");
            map.events.add("click", function () {
                map.behaviors.enable("scrollZoom");
            });

            root.addEventListener("mouseleave", function () {
                map.behaviors.disable("scrollZoom");
            });

            /* Контейнер карты растягивается по высоте формы, поэтому после
               изменения размеров сообщаем об этом API. */
            var resizeFrame = 0;
            var fitMap = function () {
                resizeFrame = 0;
                map.container.fitToViewport();
            };
            var scheduleFit = function () {
                if (resizeFrame) return;

                resizeFrame = window.requestAnimationFrame(fitMap);
            };

            window.requestAnimationFrame(fitMap);
            window.setTimeout(fitMap, 350);

            if ("ResizeObserver" in window) {
                var resizeObserver = new ResizeObserver(scheduleFit);
                resizeObserver.observe(container);
            } else {
                window.addEventListener("resize", scheduleFit);
            }

            root.classList.add("is-ready");
        });
    }

    function initStudioMaps() {
        var roots = document.querySelectorAll("[data-studio-map]");
        if (!roots.length) return;

        if (!("IntersectionObserver" in window)) {
            roots.forEach(initMap);
            return;
        }

        var observer = new IntersectionObserver(
            function (entries) {
                entries.forEach(function (entry) {
                    if (!entry.isIntersecting) return;

                    observer.unobserve(entry.target);
                    initMap(entry.target);
                });
            },
            { rootMargin: "240px 0px", threshold: 0.01 },
        );

        roots.forEach(function (root) {
            observer.observe(root);
        });
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", initStudioMaps);
    } else {
        initStudioMaps();
    }
})();
