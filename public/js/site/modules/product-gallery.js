/* ==========================================================================
   Корни — модуль «Карусель изображений товара» на базе Swiper.
   Требует загруженный Swiper (public/vendor/swiper/swiper-bundle.min.js).
   При наведении (или фокусе/клике) миниатюра подменяет основное фото.
   ========================================================================== */
(function () {
    'use strict';

    function initProductGalleries() {
        var roots = document.querySelectorAll('[data-product-gallery]');
        if (!roots.length) return;

        roots.forEach(function (root) {
            if (root.classList.contains('is-ready')) return;

            var media = root.closest('.detail__media') || document;
            var main = media.querySelector('[data-product-gallery-main]');
            var thumbs = Array.prototype.slice.call(root.querySelectorAll('[data-gallery-src]'));

            function activate(thumb) {
                if (!main) return;

                var src = thumb.getAttribute('data-gallery-src');
                if (!src) return;

                main.setAttribute('src', src);

                var alt = thumb.getAttribute('data-gallery-alt');
                if (alt) {
                    main.setAttribute('alt', alt);
                }

                thumbs.forEach(function (item) {
                    item.classList.toggle('is-active', item === thumb);
                });
            }

            thumbs.forEach(function (thumb) {
                ['mouseenter', 'focus', 'click'].forEach(function (eventName) {
                    thumb.addEventListener(eventName, function () {
                        activate(thumb);
                    });
                });
            });

            var container = root.querySelector('.swiper');

            if (container && typeof window.Swiper !== 'undefined') {
                new window.Swiper(container, {
                    slidesPerView: 'auto',
                    spaceBetween: 10,
                    watchOverflow: true,
                    a11y: {
                        enabled: true,
                        containerMessage: 'Карусель изображений товара',
                    },
                });

                root.classList.add('is-ready');
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initProductGalleries);
    } else {
        initProductGalleries();
    }
})();
