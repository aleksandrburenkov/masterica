/* ==========================================================================
   Корни — клиентские взаимодействия (нативный JavaScript)
   Анимации намеренно не зависят от prefers-reduced-motion.
   ========================================================================== */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        initHeaderSearch();
        initMobileMenu();
        initBackToTop();
        initSmoothAnchors();
        initAccordions();
        initReveal();
        initQuantity();
        initPasswordToggles();
        initSelects();
        initOutreachCalculator();
        initStickyHeaderShadow();
    });

    /* --- Кастомная стрелка выпадающего списка --- */
    function initSelects() {
        var chevronSvg = '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">' +
            '<path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';

        document.querySelectorAll('select.select').forEach(function (select) {
            if (select.closest('.select-wrap')) return;

            var wrap = document.createElement('div');
            wrap.className = 'select-wrap';
            select.parentNode.insertBefore(wrap, select);
            wrap.appendChild(select);

            var chevron = document.createElement('span');
            chevron.className = 'select-wrap__chevron';
            chevron.setAttribute('aria-hidden', 'true');
            chevron.innerHTML = chevronSvg;
            wrap.appendChild(chevron);

            function open() { wrap.classList.add('is-open'); }
            function close() { wrap.classList.remove('is-open'); }
            function toggle() {
                if (wrap.classList.contains('is-open')) {
                    close();
                } else {
                    open();
                }
            }

            /* Повторный клик по списку закрывает его без события change,
               поэтому состояние переключаем вручную, а не только по focus/blur. */
            select.addEventListener('mousedown', toggle);
            select.addEventListener('change', close);
            select.addEventListener('blur', close);
            select.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') {
                    close();
                } else if (e.key === 'Enter' || e.key === ' ' || e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                    open();
                }
            });

            document.addEventListener('mousedown', function (e) {
                if (!wrap.contains(e.target)) close();
            });
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') close();
            });
        });
    }

    /* --- Раскрывающийся поиск в шапке --- */
    function initHeaderSearch() {
        var wrap = document.querySelector('[data-search]');
        if (!wrap) return;

        var toggle = wrap.querySelector('[data-search-toggle]');
        var input = wrap.querySelector('input[type="search"]');

        toggle.addEventListener('click', function () {
            var open = wrap.classList.toggle('is-open');
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            if (open && input) {
                setTimeout(function () { input.focus(); }, 120);
            } else if (!open && input) {
                input.blur();
            }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && wrap.classList.contains('is-open')) {
                wrap.classList.remove('is-open');
                toggle.setAttribute('aria-expanded', 'false');
            }
        });
    }

    /* --- Мобильное меню --- */
    function initMobileMenu() {
        var header = document.querySelector('.site-header');
        var burger = document.querySelector('[data-burger]');
        if (!header || !burger) return;

        burger.addEventListener('click', function () {
            var open = header.classList.toggle('menu-open');
            burger.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    }

    /* --- Кнопка «наверх»: появляется при прокрутке, скрывается через 3 с покоя --- */
    function initBackToTop() {
        var btn = document.querySelector('[data-to-top]');
        if (!btn) return;

        var idleTimer = null;
        var minScroll = 400;

        function show() {
            btn.classList.add('is-visible');
        }
        function scheduleHide() {
            clearTimeout(idleTimer);
            idleTimer = setTimeout(function () {
                if (window.scrollY > minScroll) {
                    btn.classList.remove('is-visible');
                }
            }, 3000);
        }

        function onActivity() {
            if (window.scrollY > minScroll) {
                show();
                scheduleHide();
            }
        }

        window.addEventListener('scroll', onActivity, { passive: true });
        ['mousemove', 'touchstart', 'keydown'].forEach(function (evt) {
            window.addEventListener(evt, function () {
                if (window.scrollY > minScroll) {
                    show();
                    scheduleHide();
                }
            }, { passive: true });
        });

        btn.addEventListener('click', function () {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    /* --- Плавные переходы к якорям --- */
    function initSmoothAnchors() {
        document.querySelectorAll('a[href^="#"]').forEach(function (link) {
            var href = link.getAttribute('href');
            if (href === '#' || href.length < 2) return;
            link.addEventListener('click', function (e) {
                var target = document.querySelector(href);
                if (!target) return;
                e.preventDefault();
                var top = target.getBoundingClientRect().top + window.scrollY - 130;
                window.scrollTo({ top: top, behavior: 'smooth' });
                history.replaceState(null, '', href);
            });
        });
    }

    /* --- Аккордеон FAQ --- */
    function initAccordions() {
        document.querySelectorAll('.faq-item').forEach(function (item) {
            var btn = item.querySelector('.faq-item__q');
            var panel = item.querySelector('.faq-item__a');
            if (!btn || !panel) return;

            btn.addEventListener('click', function () {
                var isOpen = item.classList.contains('is-open');
                var group = item.closest('.accordion');
                if (group && !isOpen) {
                    group.querySelectorAll('.faq-item.is-open').forEach(function (other) {
                        other.classList.remove('is-open');
                        other.querySelector('.faq-item__a').style.maxHeight = null;
                        other.querySelector('.faq-item__q').setAttribute('aria-expanded', 'false');
                    });
                }
                item.classList.toggle('is-open', !isOpen);
                btn.setAttribute('aria-expanded', !isOpen ? 'true' : 'false');
                panel.style.maxHeight = !isOpen ? (panel.scrollHeight + 'px') : null;
            });
        });
    }

    /* --- Появление блоков при прокрутке --- */
    function initReveal() {
        var nodes = document.querySelectorAll('[data-reveal]');
        if (!nodes.length) return;

        if (!('IntersectionObserver' in window)) {
            nodes.forEach(function (n) { n.classList.add('is-visible'); });
            return;
        }

        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });

        nodes.forEach(function (n) { observer.observe(n); });
    }

    /* --- Счётчики количества --- */
    function initQuantity() {
        document.querySelectorAll('[data-qty]').forEach(function (wrap) {
            var input = wrap.querySelector('input');
            if (!input) return;
            wrap.querySelectorAll('[data-qty-step]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var step = parseInt(btn.getAttribute('data-qty-step'), 10) || 1;
                    var min = parseInt(input.getAttribute('min'), 10) || 1;
                    var max = parseInt(input.getAttribute('max'), 10) || 99;
                    var value = (parseInt(input.value, 10) || min) + step;
                    input.value = Math.min(max, Math.max(min, value));
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                });
            });
        });
    }

    /* --- Показ/скрытие пароля --- */
    function initPasswordToggles() {
        document.querySelectorAll('[data-password-toggle]').forEach(function (btn) {
            var input = btn.parentElement.querySelector('input');
            if (!input) return;

            btn.addEventListener('click', function () {
                var show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                btn.classList.toggle('is-visible', show);
                btn.setAttribute('aria-pressed', show ? 'true' : 'false');
                btn.setAttribute('aria-label', show ? 'Скрыть пароль' : 'Показать пароль');
            });
        });
    }

    /* --- Калькулятор выездного мастер-класса (предпросмотр; сервер считает итог) --- */
    function initOutreachCalculator() {
        var form = document.querySelector('[data-outreach]');
        if (!form) return;

        var input = form.querySelector('[data-outreach-participants]');
        if (!input) return;

        var base = parseInt(form.getAttribute('data-base'), 10) || 0;
        var included = parseInt(form.getAttribute('data-included'), 10) || 8;
        var extra = parseInt(form.getAttribute('data-extra'), 10) || 0;
        var currency = form.getAttribute('data-currency') || '₽';

        var outTotal = form.querySelector('[data-outreach-total]');
        var outExtra = form.querySelector('[data-outreach-extra]');
        var outIncluded = form.querySelector('[data-outreach-included]');

        function format(value) {
            return new Intl.NumberFormat('ru-RU').format(Math.round(value)) + ' ' + currency;
        }

        function recalc() {
            var participants = Math.max(1, parseInt(input.value, 10) || 1);
            var extraCount = Math.max(0, participants - included);
            var total = base + extraCount * extra;
            if (outTotal) outTotal.textContent = format(total);
            if (outExtra) outExtra.textContent = extraCount > 0 ? format(extraCount * extra) : '0 ' + currency;
            if (outIncluded) outIncluded.textContent = String(extraCount);
        }

        input.addEventListener('input', recalc);
        input.addEventListener('change', recalc);
        recalc();
    }

    /* --- Тень шапки при прокрутке --- */
    function initStickyHeaderShadow() {
        var header = document.querySelector('.site-header');
        if (!header) return;
        window.addEventListener('scroll', function () {
            header.style.boxShadow = window.scrollY > 10 ? '0 10px 30px rgba(38,46,110,.08)' : 'none';
        }, { passive: true });
    }
})();
