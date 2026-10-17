/* ==========================================================================
   Корни — модуль карусели отзывов на базе Swiper.
   Требует загруженный Swiper (public/vendor/swiper/swiper-bundle.min.js).
   Если Swiper недоступен, срабатывает резервная сетка из CSS-модуля.
   ========================================================================== */
(function () {
    'use strict';

    function initReviewsSliders() {
        var roots = document.querySelectorAll('[data-reviews-slider]');
        if (!roots.length) return;

        if (typeof window.Swiper === 'undefined') return;

        roots.forEach(function (root) {
            var container = root.querySelector('.swiper');
            if (!container || root.classList.contains('is-ready')) return;

            var prevEl = root.querySelector('[data-reviews-prev]');
            var nextEl = root.querySelector('[data-reviews-next]');

            new window.Swiper(container, {
                slidesPerView: 1,
                spaceBetween: 20,
                speed: 550,
                grabCursor: true,
                watchOverflow: true,
                keyboard: {
                    enabled: true,
                    onlyInViewport: true,
                },
                a11y: {
                    enabled: true,
                    prevSlideMessage: 'Предыдущий отзыв',
                    nextSlideMessage: 'Следующий отзыв',
                    containerMessage: 'Карусель отзывов',
                },
                navigation: {
                    prevEl: prevEl,
                    nextEl: nextEl,
                    disabledClass: 'swiper-button-disabled',
                },
                breakpoints: {
                    560: { slidesPerView: 1, spaceBetween: 20 },
                    680: { slidesPerView: 2, spaceBetween: 22 },
                    1024: { slidesPerView: 3, spaceBetween: 28 },
                },
            });

            root.classList.add('is-ready');
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initReviewsSliders);
    } else {
        initReviewsSliders();
    }
})();
