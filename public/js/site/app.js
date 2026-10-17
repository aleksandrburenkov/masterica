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
        initDatepickers();
        initOutreachCalculator();
        initOutreachForm();
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

    /* --- Кастомный календарь вместо нативного выбора даты --- */
    function initDatepickers() {
        var inputs = document.querySelectorAll('.input[type="date"]');
        if (!inputs.length) return;

        var MONTHS = [
            'январь', 'февраль', 'март', 'апрель', 'май', 'июнь',
            'июль', 'август', 'сентябрь', 'октябрь', 'ноябрь', 'декабрь'
        ];
        var WEEKDAYS = ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'];
        var CHEVRON = '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">' +
            '<path d="M15 6l-6 6 6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
        var CHEVRON_RIGHT = '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">' +
            '<path d="M9 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';

        var active = null;

        function pad(n) { return (n < 10 ? '0' : '') + n; }
        function iso(date) { return date.getFullYear() + '-' + pad(date.getMonth() + 1) + '-' + pad(date.getDate()); }
        function parseIso(value) {
            if (!value) return null;
            var parts = value.split('-');
            if (parts.length !== 3) return null;
            return new Date(parseInt(parts[0], 10), parseInt(parts[1], 10) - 1, parseInt(parts[2], 10));
        }
        function sameDay(a, b) {
            return a && b && a.getFullYear() === b.getFullYear() &&
                a.getMonth() === b.getMonth() && a.getDate() === b.getDate();
        }

        function closePicker() {
            if (!active) return;
            active.picker.hidden = true;
            active = null;
        }

        function buildPicker() {
            var picker = document.createElement('div');
            picker.className = 'datepicker';
            picker.hidden = true;
            picker.innerHTML =
                '<div class="datepicker__head">' +
                    '<span class="datepicker__title"></span>' +
                    '<span class="datepicker__nav">' +
                        '<button type="button" data-nav="-1" aria-label="Предыдущий месяц">' + CHEVRON + '</button>' +
                        '<button type="button" data-nav="1" aria-label="Следующий месяц">' + CHEVRON_RIGHT + '</button>' +
                    '</span>' +
                '</div>' +
                '<div class="datepicker__weekdays">' + WEEKDAYS.map(function (d) { return '<span>' + d + '</span>'; }).join('') + '</div>' +
                '<div class="datepicker__grid"></div>' +
                '<div class="datepicker__foot">' +
                    '<button type="button" data-action="clear">Очистить</button>' +
                    '<button type="button" data-action="today">Сегодня</button>' +
                '</div>';
            document.body.appendChild(picker);
            return picker;
        }

        function render(state) {
            var input = state.input;
            var min = parseIso(input.getAttribute('min'));
            var max = parseIso(input.getAttribute('max'));
            var selected = parseIso(input.value);
            var today = new Date();

            state.picker.querySelector('.datepicker__title').textContent =
                MONTHS[state.viewMonth] + ' ' + state.viewYear;

            var grid = state.picker.querySelector('.datepicker__grid');
            grid.innerHTML = '';

            var first = new Date(state.viewYear, state.viewMonth, 1);
            var startOffset = (first.getDay() + 6) % 7;
            var start = new Date(state.viewYear, state.viewMonth, 1 - startOffset);

            for (var i = 0; i < 42; i++) {
                var day = new Date(start.getFullYear(), start.getMonth(), start.getDate() + i);
                var btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'datepicker__day';
                btn.textContent = day.getDate();
                btn.setAttribute('data-date', iso(day));

                if (day.getMonth() !== state.viewMonth) btn.classList.add('is-muted');
                if (sameDay(day, today)) btn.classList.add('is-today');
                if (sameDay(day, selected)) btn.classList.add('is-selected');
                if ((min && day < min) || (max && day > max)) btn.disabled = true;

                grid.appendChild(btn);
            }
        }

        function position(state) {
            var rect = state.input.getBoundingClientRect();
            var picker = state.picker;
            var width = picker.offsetWidth || 296;
            var height = picker.offsetHeight || 340;
            var left = Math.min(rect.left, window.innerWidth - width - 8);
            var top = rect.bottom + 6;

            if (top + height > window.innerHeight - 8 && rect.top - height - 6 > 8) {
                top = rect.top - height - 6;
            }

            picker.style.left = Math.max(8, left) + 'px';
            picker.style.top = top + 'px';
        }

        function openPicker(state) {
            if (active && active !== state) closePicker();
            var selected = parseIso(state.input.value) || new Date();
            state.viewYear = selected.getFullYear();
            state.viewMonth = selected.getMonth();
            render(state);
            state.picker.hidden = false;
            position(state);
            active = state;
        }

        inputs.forEach(function (input) {
            var state = { input: input, picker: buildPicker(), viewYear: 0, viewMonth: 0 };

            state.picker.addEventListener('mousedown', function (e) { e.preventDefault(); });

            state.picker.addEventListener('click', function (e) {
                var nav = e.target.closest('[data-nav]');
                if (nav) {
                    state.viewMonth += parseInt(nav.getAttribute('data-nav'), 10);
                    if (state.viewMonth < 0) { state.viewMonth = 11; state.viewYear--; }
                    if (state.viewMonth > 11) { state.viewMonth = 0; state.viewYear++; }
                    render(state);
                    return;
                }

                var action = e.target.closest('[data-action]');
                if (action) {
                    var kind = action.getAttribute('data-action');
                    if (kind === 'clear') {
                        input.value = '';
                        input.dispatchEvent(new Event('change', { bubbles: true }));
                    } else {
                        var now = new Date();
                        var min = parseIso(input.getAttribute('min'));
                        var max = parseIso(input.getAttribute('max'));
                        if ((min && now < min) || (max && now > max)) return;
                        input.value = iso(now);
                        input.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                    closePicker();
                    return;
                }

                var day = e.target.closest('.datepicker__day');
                if (day && !day.disabled) {
                    input.value = day.getAttribute('data-date');
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                    closePicker();
                }
            });

            input.addEventListener('mousedown', function (e) {
                e.preventDefault();
                input.focus();
                if (active === state) {
                    closePicker();
                } else {
                    openPicker(state);
                }
            });

            input.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') {
                    closePicker();
                } else if (e.key === 'Enter' || e.key === ' ' || e.key === 'ArrowDown') {
                    e.preventDefault();
                    openPicker(state);
                }
            });
        });

        document.addEventListener('mousedown', function (e) {
            if (!active) return;
            if (active.input.contains(e.target) || active.picker.contains(e.target)) return;
            closePicker();
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closePicker();
        });

        window.addEventListener('resize', function () {
            if (active) position(active);
        });
        window.addEventListener('scroll', function () {
            if (active) position(active);
        }, { passive: true });
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

    /* --- Отправка формы запроса без перезагрузки и прокрутки страницы --- */
    function initOutreachForm() {
        var forms = document.querySelectorAll('form[data-outreach]');
        if (!forms.length || !window.fetch || !window.FormData) return;

        forms.forEach(function (form) {
            form.addEventListener('submit', function (event) {
                event.preventDefault();

                if (form.checkValidity && !form.checkValidity()) {
                    if (form.reportValidity) form.reportValidity();
                    return;
                }

                var button = form.querySelector('button[type="submit"]');
                var originalText = button ? button.textContent : '';
                if (button) {
                    button.disabled = true;
                    button.textContent = 'Отправляем…';
                }

                fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: new FormData(form),
                    credentials: 'same-origin'
                }).then(function (response) {
                    return response.json().catch(function () {
                        return {};
                    }).then(function (data) {
                        return { ok: response.ok, status: response.status, data: data };
                    });
                }).then(function (result) {
                    if (result.ok) {
                        showFlashToast(result.data.message || 'Заявка отправлена. Мы свяжемся с вами.', 'success');
                        form.reset();
                        var participants = form.querySelector('[data-outreach-participants]');
                        if (participants) {
                            participants.dispatchEvent(new Event('input', { bubbles: true }));
                        }
                    } else if (result.status === 422) {
                        showFlashToast(collectFormErrors(result.data), 'error');
                    } else {
                        showFlashToast(result.data.message || 'Не удалось отправить заявку. Попробуйте ещё раз.', 'error');
                    }
                }).catch(function () {
                    showFlashToast('Не удалось отправить заявку. Проверьте соединение и попробуйте ещё раз.', 'error');
                }).then(function () {
                    if (button) {
                        button.disabled = false;
                        button.textContent = originalText;
                    }
                });
            });
        });
    }

    function collectFormErrors(data) {
        var errors = (data && data.errors) || {};
        var messages = [];
        Object.keys(errors).forEach(function (key) {
            if (Array.isArray(errors[key])) {
                messages = messages.concat(errors[key]);
            }
        });
        if (!messages.length && data && data.message) messages.push(data.message);
        return messages.length ? messages.join(' ') : 'Проверьте заполнение формы.';
    }

    function showFlashToast(message, type) {
        var previous = document.querySelector('.flash-toast');
        if (previous && previous.parentNode) previous.parentNode.removeChild(previous);

        var toast = document.createElement('div');
        toast.className = 'flash-toast flash flash--' + (type === 'error' ? 'error' : 'success');
        toast.setAttribute('role', type === 'error' ? 'alert' : 'status');

        var text = document.createElement('span');
        text.className = 'flash-toast__text';
        text.textContent = message;
        toast.appendChild(text);

        var close = document.createElement('button');
        close.type = 'button';
        close.className = 'flash-toast__close';
        close.setAttribute('aria-label', 'Закрыть');
        close.innerHTML = '&times;';
        toast.appendChild(close);

        document.body.appendChild(toast);

        var header = document.querySelector('.site-header');
        var offset = (header ? header.getBoundingClientRect().height : 0) + 12;
        toast.style.top = offset + 'px';

        var timer = null;
        function remove() {
            if (timer) window.clearTimeout(timer);
            toast.classList.remove('is-visible');
            window.setTimeout(function () {
                if (toast.parentNode) toast.parentNode.removeChild(toast);
            }, 300);
        }
        close.addEventListener('click', remove);

        window.requestAnimationFrame(function () {
            toast.classList.add('is-visible');
        });
        timer = window.setTimeout(remove, 7000);
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
