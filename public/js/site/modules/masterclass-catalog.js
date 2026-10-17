/* ==========================================================================
   Корни — каталог мастер-классов: табы техник и фильтры целиком на клиенте.
   Сервер отдаёт все карточки сразу; скрипт скрывает и показывает их по
   data-атрибутам. Никаких GET-параметров и перезагрузок страницы.
   ========================================================================== */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var pills = Array.prototype.slice.call(document.querySelectorAll('[data-filter-techniques] .filter-pill'));
        var form = document.querySelector('[data-filter-form]');
        var grid = document.querySelector('[data-catalog-grid]');
        var empty = document.querySelector('[data-catalog-empty]');
        var resets = document.querySelectorAll('[data-filter-reset]');

        if (!pills.length || !form || !grid) return;

        var cards = Array.prototype.slice.call(grid.querySelectorAll('[data-catalog-card]'));
        var activeTechnique = '';

        function field(name) {
            var input = form.elements[name];
            return input ? String(input.value).trim() : '';
        }

        function matches(card) {
            var data = card.dataset;
            var level = field('level');
            var format = field('format');
            var age = parseInt(field('age'), 10);

            if (activeTechnique && data.technique !== activeTechnique) return false;
            if (level && data.level !== level) return false;
            if (format && data.format !== format) return false;

            if (age) {
                var min = parseInt(data.ageMin, 10);
                var max = data.ageMax ? parseInt(data.ageMax, 10) : 0;

                if (min && age < min) return false;
                if (max && age > max) return false;
            }

            return true;
        }

        function hasFilters() {
            return Boolean(activeTechnique || field('age') || field('level') || field('format'));
        }

        function apply() {
            var visible = 0;

            cards.forEach(function (card) {
                if (matches(card)) {
                    card.hidden = false;
                    card.classList.add('is-visible');
                    visible++;
                } else {
                    card.hidden = true;
                }
            });

            if (empty) empty.hidden = visible > 0;
            resets.forEach(function (button) {
                button.hidden = !hasFilters();
            });
        }

        function selectTechnique(slug) {
            activeTechnique = slug;
            pills.forEach(function (pill) {
                pill.classList.toggle('is-active', (pill.getAttribute('data-technique') || '') === slug);
            });
            apply();
        }

        pills.forEach(function (pill) {
            pill.addEventListener('click', function () {
                selectTechnique(pill.getAttribute('data-technique') || '');
            });
        });

        form.addEventListener('submit', function (event) {
            event.preventDefault();
            apply();
        });

        resets.forEach(function (button) {
            button.addEventListener('click', function () {
                form.reset();
                selectTechnique('');
            });
        });
    });
})();
