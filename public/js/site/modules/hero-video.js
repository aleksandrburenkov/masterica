/* ==========================================================================
   Корни — видео-фон hero-секции (.hero--video).
   Только белый список: muted до автоплея, fade-in по loadeddata, обработка
   ошибки загрузки. Видео не останавливается и не блокируется.
   ========================================================================== */
(function () {
    'use strict';

    function initHeroVideo(hero) {
        var video = hero.querySelector('.hero__video');
        if (!video) return;

        video.muted = true;
        video.setAttribute('muted', '');

        var playPromise = video.play();
        if (playPromise && typeof playPromise.catch === 'function') {
            playPromise.catch(function () {});
        }

        function ready() {
            hero.classList.remove('hero--video-loading');
            hero.classList.add('hero--video-ready');
        }

        function fail() {
            hero.classList.remove('hero--video-loading');
            hero.classList.add('hero--no-video');
        }

        if (video.readyState >= 2) {
            ready();
            return;
        }

        hero.classList.add('hero--video-loading');
        video.addEventListener('loadeddata', ready, { once: true });
        video.addEventListener('error', fail, { once: true });

        var source = video.querySelector('source');
        if (source) {
            source.addEventListener('error', fail, { once: true });
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.hero--video').forEach(initHeroVideo);
    });
})();
