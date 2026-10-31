/* ==========================================================================
   Корни — клиентские взаимодействия (нативный JavaScript)
   Анимации намеренно не зависят от prefers-reduced-motion.
   ========================================================================== */
(function () {
    "use strict";

    document.addEventListener("DOMContentLoaded", function () {
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
        initCheckoutDelivery();
        initOutreachCalculator();
        initOutreachForm();
        initMasterclassRequestForm();
        initReviewVideo();
        initAjaxForms();
        initFlashToasts();
        initHeaderCart();
        initCartPage();
        initStickyHeaderShadow();
    });

    /* --- Кастомная стрелка выпадающего списка --- */
    function initSelects() {
        var chevronSvg =
            '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">' +
            '<path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';

        document.querySelectorAll("select.select").forEach(function (select) {
            if (select.closest(".select-wrap")) return;

            var wrap = document.createElement("div");
            wrap.className = "select-wrap";
            select.parentNode.insertBefore(wrap, select);
            wrap.appendChild(select);

            var chevron = document.createElement("span");
            chevron.className = "select-wrap__chevron";
            chevron.setAttribute("aria-hidden", "true");
            chevron.innerHTML = chevronSvg;
            wrap.appendChild(chevron);

            function open() {
                wrap.classList.add("is-open");
            }
            function close() {
                wrap.classList.remove("is-open");
            }
            function toggle() {
                if (wrap.classList.contains("is-open")) {
                    close();
                } else {
                    open();
                }
            }

            /* Повторный клик по списку закрывает его без события change,
               поэтому состояние переключаем вручную, а не только по focus/blur. */
            select.addEventListener("mousedown", toggle);
            select.addEventListener("change", close);
            select.addEventListener("blur", close);
            select.addEventListener("keydown", function (e) {
                if (e.key === "Escape") {
                    close();
                } else if (
                    e.key === "Enter" ||
                    e.key === " " ||
                    e.key === "ArrowDown" ||
                    e.key === "ArrowUp"
                ) {
                    open();
                }
            });

            document.addEventListener("mousedown", function (e) {
                if (!wrap.contains(e.target)) close();
            });
            document.addEventListener("keydown", function (e) {
                if (e.key === "Escape") close();
            });
        });
    }

    /* --- Кастомный календарь вместо нативного выбора даты --- */
    function initDatepickers() {
        var inputs = document.querySelectorAll('.input[type="date"]');
        if (!inputs.length) return;

        var MONTHS = [
            "январь",
            "февраль",
            "март",
            "апрель",
            "май",
            "июнь",
            "июль",
            "август",
            "сентябрь",
            "октябрь",
            "ноябрь",
            "декабрь",
        ];
        var WEEKDAYS = ["Пн", "Вт", "Ср", "Чт", "Пт", "Сб", "Вс"];
        var CHEVRON =
            '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">' +
            '<path d="M15 6l-6 6 6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
        var CHEVRON_RIGHT =
            '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">' +
            '<path d="M9 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';

        var active = null;

        function pad(n) {
            return (n < 10 ? "0" : "") + n;
        }
        function iso(date) {
            return (
                date.getFullYear() +
                "-" +
                pad(date.getMonth() + 1) +
                "-" +
                pad(date.getDate())
            );
        }
        function parseIso(value) {
            if (!value) return null;
            var parts = value.split("-");
            if (parts.length !== 3) return null;
            return new Date(
                parseInt(parts[0], 10),
                parseInt(parts[1], 10) - 1,
                parseInt(parts[2], 10),
            );
        }
        function sameDay(a, b) {
            return (
                a &&
                b &&
                a.getFullYear() === b.getFullYear() &&
                a.getMonth() === b.getMonth() &&
                a.getDate() === b.getDate()
            );
        }

        function closePicker() {
            if (!active) return;
            active.picker.hidden = true;
            active = null;
        }

        function buildPicker() {
            var picker = document.createElement("div");
            picker.className = "datepicker";
            picker.hidden = true;
            picker.innerHTML =
                '<div class="datepicker__head">' +
                '<span class="datepicker__title"></span>' +
                '<span class="datepicker__nav">' +
                '<button type="button" data-nav="-1" aria-label="Предыдущий месяц">' +
                CHEVRON +
                "</button>" +
                '<button type="button" data-nav="1" aria-label="Следующий месяц">' +
                CHEVRON_RIGHT +
                "</button>" +
                "</span>" +
                "</div>" +
                '<div class="datepicker__weekdays">' +
                WEEKDAYS.map(function (d) {
                    return "<span>" + d + "</span>";
                }).join("") +
                "</div>" +
                '<div class="datepicker__grid"></div>' +
                '<div class="datepicker__foot">' +
                '<button type="button" data-action="clear">Очистить</button>' +
                '<button type="button" data-action="today">Сегодня</button>' +
                "</div>";
            document.body.appendChild(picker);
            return picker;
        }

        function render(state) {
            var input = state.input;
            var min = parseIso(input.getAttribute("min"));
            var max = parseIso(input.getAttribute("max"));
            var selected = parseIso(input.value);
            var today = new Date();

            state.picker.querySelector(".datepicker__title").textContent =
                MONTHS[state.viewMonth] + " " + state.viewYear;

            var grid = state.picker.querySelector(".datepicker__grid");
            grid.innerHTML = "";

            var first = new Date(state.viewYear, state.viewMonth, 1);
            var startOffset = (first.getDay() + 6) % 7;
            var start = new Date(
                state.viewYear,
                state.viewMonth,
                1 - startOffset,
            );

            for (var i = 0; i < 42; i++) {
                var day = new Date(
                    start.getFullYear(),
                    start.getMonth(),
                    start.getDate() + i,
                );
                var btn = document.createElement("button");
                btn.type = "button";
                btn.className = "datepicker__day";
                btn.textContent = day.getDate();
                btn.setAttribute("data-date", iso(day));

                if (day.getMonth() !== state.viewMonth)
                    btn.classList.add("is-muted");
                if (sameDay(day, today)) btn.classList.add("is-today");
                if (sameDay(day, selected)) btn.classList.add("is-selected");
                if ((min && day < min) || (max && day > max))
                    btn.disabled = true;

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

            if (
                top + height > window.innerHeight - 8 &&
                rect.top - height - 6 > 8
            ) {
                top = rect.top - height - 6;
            }

            picker.style.left = Math.max(8, left) + "px";
            picker.style.top = top + "px";
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
            var state = {
                input: input,
                picker: buildPicker(),
                viewYear: 0,
                viewMonth: 0,
            };

            state.picker.addEventListener("mousedown", function (e) {
                e.preventDefault();
            });

            state.picker.addEventListener("click", function (e) {
                var nav = e.target.closest("[data-nav]");
                if (nav) {
                    state.viewMonth += parseInt(
                        nav.getAttribute("data-nav"),
                        10,
                    );
                    if (state.viewMonth < 0) {
                        state.viewMonth = 11;
                        state.viewYear--;
                    }
                    if (state.viewMonth > 11) {
                        state.viewMonth = 0;
                        state.viewYear++;
                    }
                    render(state);
                    return;
                }

                var action = e.target.closest("[data-action]");
                if (action) {
                    var kind = action.getAttribute("data-action");
                    if (kind === "clear") {
                        input.value = "";
                        input.dispatchEvent(
                            new Event("change", { bubbles: true }),
                        );
                    } else {
                        var now = new Date();
                        var min = parseIso(input.getAttribute("min"));
                        var max = parseIso(input.getAttribute("max"));
                        if ((min && now < min) || (max && now > max)) return;
                        input.value = iso(now);
                        input.dispatchEvent(
                            new Event("change", { bubbles: true }),
                        );
                    }
                    closePicker();
                    return;
                }

                var day = e.target.closest(".datepicker__day");
                if (day && !day.disabled) {
                    input.value = day.getAttribute("data-date");
                    input.dispatchEvent(new Event("change", { bubbles: true }));
                    closePicker();
                }
            });

            input.addEventListener("mousedown", function (e) {
                e.preventDefault();
                input.focus();
                if (active === state) {
                    closePicker();
                } else {
                    openPicker(state);
                }
            });

            input.addEventListener("keydown", function (e) {
                if (e.key === "Escape") {
                    closePicker();
                } else if (
                    e.key === "Enter" ||
                    e.key === " " ||
                    e.key === "ArrowDown"
                ) {
                    e.preventDefault();
                    openPicker(state);
                }
            });
        });

        document.addEventListener("mousedown", function (e) {
            if (!active) return;
            if (
                active.input.contains(e.target) ||
                active.picker.contains(e.target)
            )
                return;
            closePicker();
        });

        document.addEventListener("keydown", function (e) {
            if (e.key === "Escape") closePicker();
        });

        window.addEventListener("resize", function () {
            if (active) position(active);
        });
        window.addEventListener(
            "scroll",
            function () {
                if (active) position(active);
            },
            { passive: true },
        );
    }

    /* --- Раскрывающийся поиск в шапке --- */
    function initHeaderSearch() {
        var wrap = document.querySelector("[data-search]");
        if (!wrap) return;

        var toggle = wrap.querySelector("[data-search-toggle]");
        var input = wrap.querySelector('input[type="search"]');

        toggle.addEventListener("click", function () {
            var open = wrap.classList.toggle("is-open");
            toggle.setAttribute("aria-expanded", open ? "true" : "false");
            if (open && input) {
                setTimeout(function () {
                    input.focus();
                }, 120);
            } else if (!open && input) {
                input.blur();
            }
        });

        document.addEventListener("keydown", function (e) {
            if (e.key === "Escape" && wrap.classList.contains("is-open")) {
                wrap.classList.remove("is-open");
                toggle.setAttribute("aria-expanded", "false");
            }
        });
    }

    /* --- Мобильное меню --- */
    function initMobileMenu() {
        var header = document.querySelector(".site-header");
        var burger = document.querySelector("[data-burger]");
        if (!header || !burger) return;

        burger.addEventListener("click", function () {
            var open = header.classList.toggle("menu-open");
            burger.setAttribute("aria-expanded", open ? "true" : "false");
        });
    }

    /* --- Кнопка «наверх»: появляется при прокрутке, скрывается через 3 с покоя --- */
    function initBackToTop() {
        var btn = document.querySelector("[data-to-top]");
        if (!btn) return;

        var idleTimer = null;
        var minScroll = 400;

        function show() {
            btn.classList.add("is-visible");
        }
        function scheduleHide() {
            clearTimeout(idleTimer);
            idleTimer = setTimeout(function () {
                if (window.scrollY > minScroll) {
                    btn.classList.remove("is-visible");
                }
            }, 3000);
        }

        function onActivity() {
            if (window.scrollY > minScroll) {
                show();
                scheduleHide();
            }
        }

        window.addEventListener("scroll", onActivity, { passive: true });
        ["mousemove", "touchstart", "keydown"].forEach(function (evt) {
            window.addEventListener(
                evt,
                function () {
                    if (window.scrollY > minScroll) {
                        show();
                        scheduleHide();
                    }
                },
                { passive: true },
            );
        });

        btn.addEventListener("click", function () {
            window.scrollTo({ top: 0, behavior: "smooth" });
        });
    }

    /* --- Плавные переходы к якорям --- */
    function initSmoothAnchors() {
        document.querySelectorAll('a[href^="#"]').forEach(function (link) {
            var href = link.getAttribute("href");
            if (href === "#" || href.length < 2) return;
            link.addEventListener("click", function (e) {
                var target = document.querySelector(href);
                if (!target) return;
                e.preventDefault();
                var top =
                    target.getBoundingClientRect().top + window.scrollY - 130;
                window.scrollTo({ top: top, behavior: "smooth" });
                history.replaceState(null, "", href);
            });
        });
    }

    /* --- Аккордеон FAQ --- */
    function initAccordions() {
        document.querySelectorAll(".faq-item").forEach(function (item) {
            var btn = item.querySelector(".faq-item__q");
            var panel = item.querySelector(".faq-item__a");
            if (!btn || !panel) return;

            btn.addEventListener("click", function () {
                var isOpen = item.classList.contains("is-open");
                var group = item.closest(".accordion");
                if (group && !isOpen) {
                    group
                        .querySelectorAll(".faq-item.is-open")
                        .forEach(function (other) {
                            other.classList.remove("is-open");
                            other.querySelector(
                                ".faq-item__a",
                            ).style.maxHeight = null;
                            other
                                .querySelector(".faq-item__q")
                                .setAttribute("aria-expanded", "false");
                        });
                }
                item.classList.toggle("is-open", !isOpen);
                btn.setAttribute("aria-expanded", !isOpen ? "true" : "false");
                panel.style.maxHeight = !isOpen
                    ? panel.scrollHeight + "px"
                    : null;
            });
        });
    }

    /* --- Появление блоков при прокрутке --- */
    function initReveal() {
        var nodes = document.querySelectorAll("[data-reveal]");
        if (!nodes.length) return;

        if (!("IntersectionObserver" in window)) {
            nodes.forEach(function (n) {
                n.classList.add("is-visible");
            });
            return;
        }

        var observer = new IntersectionObserver(
            function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        entry.target.classList.add("is-visible");
                        observer.unobserve(entry.target);
                    }
                });
            },
            { threshold: 0.12, rootMargin: "0px 0px -40px 0px" },
        );

        nodes.forEach(function (n) {
            observer.observe(n);
        });
    }

    /* --- Счётчики количества --- */
    function initQuantity() {
        document.querySelectorAll("[data-qty]").forEach(function (wrap) {
            /* Берём именно поле количества, а не скрытое поле _token,
               которое стоит первым в форме. */
            var input = wrap.querySelector(
                'input[type="number"], input[name="quantity"]',
            );
            if (!input) return;
            wrap.querySelectorAll("[data-qty-step]").forEach(function (btn) {
                btn.addEventListener("click", function () {
                    var step =
                        parseInt(btn.getAttribute("data-qty-step"), 10) || 1;
                    var min = parseInt(input.getAttribute("min"), 10) || 1;
                    var max = parseInt(input.getAttribute("max"), 10) || 99;
                    var value = (parseInt(input.value, 10) || min) + step;
                    input.value = Math.min(max, Math.max(min, value));
                    input.dispatchEvent(new Event("change", { bubbles: true }));
                });
            });
        });
    }

    /* --- Показ/скрытие пароля --- */
    function initPasswordToggles() {
        document
            .querySelectorAll("[data-password-toggle]")
            .forEach(function (btn) {
                var input = btn.parentElement.querySelector("input");
                if (!input) return;

                btn.addEventListener("click", function () {
                    var show = input.type === "password";
                    input.type = show ? "text" : "password";
                    btn.classList.toggle("is-visible", show);
                    btn.setAttribute("aria-pressed", show ? "true" : "false");
                    btn.setAttribute(
                        "aria-label",
                        show ? "Скрыть пароль" : "Показать пароль",
                    );
                });
            });
    }

    /* --- Адрес доставки обязателен только для доставки по городу --- */
    function initCheckoutDelivery() {
        var radios = document.querySelectorAll('input[name="delivery_method"]');
        var address = document.getElementById("c-address");
        if (!radios.length || !address) return;

        function sync() {
            var selected = document.querySelector(
                'input[name="delivery_method"]:checked',
            );
            address.required = !!selected && selected.value === "city_delivery";
        }

        radios.forEach(function (radio) {
            radio.addEventListener("change", sync);
        });
        sync();
    }

    /* --- Калькулятор выездного мастер-класса (предпросмотр; сервер считает итог) --- */
    function initOutreachCalculator() {
        var form = document.querySelector("[data-outreach]");
        if (!form) return;

        var direction = form.querySelector("[data-outreach-direction]");
        var input = form.querySelector("[data-outreach-participants]");
        if (!direction || !input) return;

        var root = form.closest(".calc") || form;
        var currency = form.getAttribute("data-currency") || "₽";

        var outTotal = root.querySelector("[data-outreach-total]");
        var outBase = root.querySelector("[data-outreach-base]");
        var outGroupLabel = root.querySelector("[data-outreach-group-label]");
        var outPercent = root.querySelector("[data-outreach-percent]");
        var outPercentLabel = root.querySelector(
            "[data-outreach-percent-label]",
        );
        var outDuration = root.querySelector("[data-outreach-duration]");
        var outDistanceRow = root.querySelector("[data-outreach-distance-row]");
        var outDistanceKm = root.querySelector("[data-outreach-distance-km]");
        var outDistanceSurcharge = root.querySelector(
            "[data-outreach-distance-surcharge]",
        );
        var distanceInput = form.querySelector("[data-outreach-distance]");

        function format(cents) {
            return (
                new Intl.NumberFormat("ru-RU", {
                    maximumFractionDigits: 2,
                }).format(cents / 100) +
                " " +
                currency
            );
        }

        function parseSurcharges(raw) {
            var tiers = [];

            try {
                var parsed = JSON.parse(raw || "{}");
                Object.keys(parsed).forEach(function (key) {
                    var count = parseInt(key, 10);
                    var percent = parseFloat(
                        String(parsed[key]).replace(",", "."),
                    );

                    if (count > 0 && percent > 0) {
                        tiers.push({ count: count, percent: percent });
                    }
                });
            } catch (e) {
                return [];
            }

            return tiers.sort(function (a, b) {
                return a.count - b.count;
            });
        }

        function pickPercent(tiers, extra) {
            for (var i = 0; i < tiers.length; i++) {
                if (extra <= tiers[i].count) return tiers[i].percent;
            }

            return tiers.length ? tiers[tiers.length - 1].percent : 0;
        }

        function selectedMasterclass() {
            var option = direction.options[direction.selectedIndex];
            if (
                !option ||
                option.value === "" ||
                option.value === "Свободная тема"
            )
                return null;

            return {
                price: parseInt(option.getAttribute("data-price"), 10) || 0,
                included:
                    parseInt(option.getAttribute("data-included"), 10) || 0,
                duration:
                    parseInt(option.getAttribute("data-duration"), 10) || 0,
                surcharges: parseSurcharges(
                    option.getAttribute("data-surcharges"),
                ),
            };
        }

        function distanceState(participants) {
            var raw = distanceInput ? String(distanceInput.value) : "";
            var km = parseFloat(raw.replace(",", "."));
            var perParticipant = parseInt(
                form.getAttribute("data-distance-per-participant") || "0",
                10,
            );

            if (isNaN(perParticipant) || perParticipant < 0) perParticipant = 0;

            if (raw.trim() === "" || isNaN(km) || km < 0) {
                return { known: false, km: 0, cents: 0 };
            }

            return {
                known: true,
                km: km,
                cents: perParticipant * 100 * participants,
            };
        }

        function formatKm(km) {
            return (
                new Intl.NumberFormat("ru-RU", {
                    maximumFractionDigits: 1,
                }).format(km) + " км"
            );
        }

        function recalc() {
            var participants = parseInt(input.value, 10);
            if (isNaN(participants) || participants < 0) participants = 0;

            var masterclass = selectedMasterclass();
            var base = masterclass ? masterclass.price * participants : 0;
            var included = masterclass ? masterclass.included : 0;
            var extra = masterclass ? Math.max(0, participants - included) : 0;
            var percent =
                extra > 0 ? pickPercent(masterclass.surcharges, extra) : 0;
            var distance = distanceState(participants);
            var total =
                base +
                Math.round((base * percent) / 100) +
                distance.cents;
            var hours = Math.floor(
                (masterclass ? masterclass.duration : 0) / 60,
            );

            if (outDistanceRow) outDistanceRow.hidden = !distance.known;
            if (outDistanceKm && distance.known)
                outDistanceKm.textContent = "(" + formatKm(distance.km) + ")";
            if (outDistanceSurcharge)
                outDistanceSurcharge.textContent = format(distance.cents);

            if (outTotal) outTotal.textContent = format(total);
            if (outBase) outBase.textContent = format(base);
            if (outGroupLabel)
                outGroupLabel.textContent =
                    "Группа из " + participants + " участников";
            if (outPercent)
                outPercent.textContent =
                    (percent > 0 ? String(percent).replace(".", ",") : "0") +
                    " %";
            if (outPercentLabel)
                outPercentLabel.textContent =
                    "% за увеличенное количество участников" +
                    (extra > 0 ? " " + extra : "");
            if (outDuration) outDuration.textContent = hours + " часа";
        }

        direction.addEventListener("change", recalc);
        input.addEventListener("input", recalc);
        input.addEventListener("change", recalc);
        form.addEventListener("outreach:distance", recalc);
        form.addEventListener("reset", function () {
            window.setTimeout(recalc, 0);
        });
        recalc();
    }

    /* --- Отправка формы запроса без перезагрузки и прокрутки страницы --- */
    function initOutreachForm() {
        var forms = document.querySelectorAll("form[data-outreach]");
        if (!forms.length || !window.fetch || !window.FormData) return;

        forms.forEach(function (form) {
            form.addEventListener("submit", function (event) {
                event.preventDefault();

                if (form.checkValidity && !form.checkValidity()) {
                    if (form.reportValidity) form.reportValidity();
                    return;
                }

                submitFormWithoutReload(form, {
                    successMessage: "Заявка отправлена. Мы свяжемся с вами.",
                    errorMessage:
                        "Не удалось отправить заявку. Попробуйте ещё раз.",
                    networkErrorMessage:
                        "Не удалось отправить заявку. Проверьте соединение и попробуйте ещё раз.",
                    afterSuccess: function (submittedForm) {
                        var participants = submittedForm.querySelector(
                            "[data-outreach-participants]",
                        );
                        if (participants) {
                            participants.dispatchEvent(
                                new Event("input", { bubbles: true }),
                            );
                        }
                    },
                });
            });
        });
    }

    /* --- Запрос на мастер-класс без активных сеансов: после отправки поле
       очищается и заменяется на обычный выбор активного сеанса. Форма остаётся
       на AJAX-отправке и после переключения в режим выбора сеанса. --- */
    function initMasterclassRequestForm() {
        var forms = document.querySelectorAll("form[data-request-form]");
        if (!forms.length || !window.fetch || !window.FormData) return;

        forms.forEach(function (form) {
            form.addEventListener("submit", function (event) {
                event.preventDefault();

                if (form.checkValidity && !form.checkValidity()) {
                    if (form.reportValidity) form.reportValidity();
                    return;
                }

                var requestMode = form.getAttribute("data-mode") !== "select";
                var switched = false;

                submitFormWithoutReload(form, {
                    successMessage: requestMode
                        ? "Ваш запрос принят и обрабатывается, ответ будет отправлен по Email"
                        : "Готово.",
                    errorMessage: requestMode
                        ? "Не удалось отправить запрос. Попробуйте ещё раз."
                        : "Не удалось отправить форму. Попробуйте ещё раз.",
                    networkErrorMessage: requestMode
                        ? "Не удалось отправить запрос. Проверьте соединение и попробуйте ещё раз."
                        : "Не удалось отправить форму. Проверьте соединение и попробуйте ещё раз.",
                    afterSuccess: function (submittedForm) {
                        if (!requestMode) return;
                        switched = true;
                        switchRequestToSelect(submittedForm);
                    },
                    finalize: function (submittedForm) {
                        if (!switched) return;

                        var select = submittedForm.querySelector(
                            "[data-select-field] select",
                        );
                        var button = submittedForm.querySelector(
                            'button[type="submit"]',
                        );

                        if (button && select) button.disabled = select.disabled;
                    },
                });
            });
        });
    }

    function switchRequestToSelect(form) {
        var requestField = form.querySelector("[data-request-field]");
        if (requestField) {
            requestField.hidden = true;

            var title = requestField.querySelector(
                'input[name="request_title"]',
            );
            if (title) title.value = "";

            // Скрытые поля не должны участвовать в проверке и отправке формы:
            // обязательное невидимое поле браузер не может сфокусировать и
            // блокирует отправку ошибкой «not focusable».
            requestField
                .querySelectorAll("input, select, textarea")
                .forEach(function (control) {
                    control.required = false;
                    control.disabled = true;
                });
        }

        var selectField = form.querySelector("[data-select-field]");
        if (selectField) selectField.hidden = false;

        var select = selectField ? selectField.querySelector("select") : null;
        if (select) {
            var hasOptions = Array.prototype.some.call(
                select.options,
                function (option) {
                    return option.value !== "";
                },
            );
            select.disabled = !hasOptions;
            select.required = hasOptions;
            if (hasOptions) select.value = "";
        }

        var quick = form.getAttribute("data-quick-action");
        if (quick) form.setAttribute("action", quick);

        form.setAttribute("data-mode", "select");
    }

    /* --- Видеоотзывы: ленивая загрузка (preload="none"), тематический
       прелоадер при старте и автозапуск после загрузки. Одновременно
       проигрывается только один ролик. --- */
    function initReviewVideo() {
        var roots = document.querySelectorAll("[data-review-video]");
        if (!roots.length) return;

        function pauseOthers(current) {
            document.querySelectorAll("[data-review-video] video").forEach(function (other) {
                if (other !== current && !other.paused) other.pause();
            });
        }

        roots.forEach(function (root) {
            var player = root.querySelector("[data-review-player]");
            if (!player) return;

            var cover = root.querySelector("[data-review-cover]");
            var preloader = root.querySelector("[data-review-preloader]");

            player.addEventListener("play", function () {
                pauseOthers(player);
            });

            function hidePreloader() {
                if (!preloader || preloader.hidden) return;

                preloader.classList.add("is-hidden");
                window.setTimeout(function () {
                    preloader.hidden = true;
                }, 520);
            }

            function revealCover() {
                if (cover) cover.classList.add("is-hidden");
            }

            var started = false;

            function startPlayback() {
                if (started) return;
                started = true;

                revealCover();

                if (preloader) {
                    preloader.hidden = false;
                    preloader.classList.remove("is-hidden");
                }

                var resolved = false;
                function ready() {
                    if (resolved) return;
                    resolved = true;

                    ["canplaythrough", "canplay", "loadeddata", "playing"].forEach(function (eventName) {
                        player.removeEventListener(eventName, ready);
                    });

                    hidePreloader();
                }

                ["canplaythrough", "canplay", "loadeddata", "playing"].forEach(function (eventName) {
                    player.addEventListener(eventName, ready);
                });

                /* Страховка: не оставляем прелоадер навсегда, если браузер
                   не сообщит о готовности. */
                window.setTimeout(ready, 12000);

                var attempt = player.play();
                if (attempt && typeof attempt.catch === "function") {
                    attempt.catch(function () {
                        /* Автозапуск отклонён: пользователь запустит вручную
                           через встроенные элементы управления. */
                    });
                }
            }

            if (cover) cover.addEventListener("click", startPlayback);

            player.addEventListener("playing", function () {
                revealCover();
                hidePreloader();
            });
        });
    }

    /* --- Общая AJAX-отправка форм: страница не перезагружается и не прокручивается --- */
    function initAjaxForms() {
        var forms = document.querySelectorAll("form[data-ajax-form]");
        if (!forms.length || !window.fetch || !window.FormData) return;

        forms.forEach(function (form) {
            form.addEventListener("submit", function (event) {
                event.preventDefault();

                if (form.checkValidity && !form.checkValidity()) {
                    if (form.reportValidity) form.reportValidity();
                    return;
                }

                submitFormWithoutReload(form, {
                    successMessage: "Готово.",
                    errorMessage:
                        "Не удалось отправить форму. Попробуйте ещё раз.",
                    networkErrorMessage:
                        "Не удалось отправить форму. Проверьте соединение и попробуйте ещё раз.",
                });
            });
        });
    }

    function submitFormWithoutReload(form, options) {
        options = options || {};

        var button = form.querySelector('button[type="submit"]');
        var originalText = button ? button.textContent : "";
        if (button) {
            button.disabled = true;
            button.textContent = "Отправляем…";
        }

        fetch(form.action, {
            method: "POST",
            headers: {
                Accept: "application/json",
                "X-Requested-With": "XMLHttpRequest",
            },
            body: new FormData(form),
            credentials: "same-origin",
        })
            .then(function (response) {
                return response
                    .json()
                    .catch(function () {
                        return {};
                    })
                    .then(function (data) {
                        return {
                            ok: response.ok,
                            status: response.status,
                            data: data,
                        };
                    });
            })
            .then(function (result) {
                if (result.ok) {
                    if (result.data.redirect) {
                        window.location.assign(result.data.redirect);
                        return;
                    }

                    showFlashToast(
                        result.data.message ||
                            options.successMessage ||
                            "Готово.",
                        "success",
                    );
                    /* Корзина вернула свежий снапшот — обновляем бейдж, мини-
                       корзину и страницу корзины, открывая панель после добавления. */
                    if (result.data.cart) {
                        cartSeq++;
                        publishCart(result.data.cart, { open: true });
                    }
                    form.reset();
                    /* Программный сброс не вызывает событие reset: сообщаем
                       виджетам (например, загрузке видео) очистить состояние. */
                    form.dispatchEvent(new Event("form:reset"));

                    if (options.afterSuccess) {
                        options.afterSuccess(form);
                    }
                } else if (result.status === 422) {
                    showFlashToast(collectFormErrors(result.data), "error");
                } else {
                    showFlashToast(
                        result.data.message ||
                            options.errorMessage ||
                            "Не удалось отправить форму. Попробуйте ещё раз.",
                        "error",
                    );
                }
            })
            .catch(function () {
                showFlashToast(
                    options.networkErrorMessage ||
                        "Не удалось отправить форму. Проверьте соединение и попробуйте ещё раз.",
                    "error",
                );
            })
            .then(function () {
                if (button) {
                    button.disabled = false;
                    button.textContent = originalText;
                }

                if (typeof options.finalize === "function") {
                    options.finalize(form);
                }
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
        if (!messages.length && data && data.message)
            messages.push(data.message);
        return messages.length
            ? messages.join(" ")
            : "Проверьте заполнение формы.";
    }

    /* --- Всплывающие уведомления в верхней части видимой области --- */
    function getFlashStack() {
        var stack = document.querySelector("[data-flash-stack]");
        if (!stack) {
            stack = document.createElement("div");
            stack.className = "flash-stack";
            stack.setAttribute("data-flash-stack", "");
            stack.setAttribute("aria-live", "polite");
            document.body.appendChild(stack);
        }
        positionFlashStack(stack);
        return stack;
    }

    function positionFlashStack(stack) {
        var header = document.querySelector(".site-header");
        var top = header ? header.getBoundingClientRect().bottom : 0;
        stack.style.top = Math.max(0, top) + 12 + "px";
    }

    function dismissFlashToast(toast) {
        if (
            !toast ||
            !toast.parentNode ||
            toast.classList.contains("is-hiding")
        )
            return;
        toast.classList.add("is-hiding");
        window.setTimeout(function () {
            if (toast.parentNode) toast.parentNode.removeChild(toast);
        }, 200);
    }

    /* Автозакрытие: через 3,5 с уведомление плавно уходит так же, как при клике на крестик.
       Ручное закрытие отменяет таймер, чтобы не было повторного срабатывания. */
    var FLASH_TOAST_TIMEOUT = 3500;

    function registerFlashToast(toast) {
        if (!toast || toast.getAttribute("data-flash-bound") === "1") return;
        toast.setAttribute("data-flash-bound", "1");

        var timer = window.setTimeout(function () {
            timer = null;
            dismissFlashToast(toast);
        }, FLASH_TOAST_TIMEOUT);

        function cancelTimer() {
            if (timer) {
                window.clearTimeout(timer);
                timer = null;
            }
        }

        var close = toast.querySelector("[data-flash-close]");
        if (close) {
            close.addEventListener("click", function () {
                cancelTimer();
                dismissFlashToast(toast);
            });
        }
    }

    function initFlashToasts() {
        var stack = document.querySelector("[data-flash-stack]");
        if (stack) {
            positionFlashStack(stack);
            window.addEventListener("resize", function () {
                positionFlashStack(stack);
            });
        }

        document
            .querySelectorAll("[data-flash-toast]")
            .forEach(function (toast) {
                registerFlashToast(toast);
            });
    }

    function showFlashToast(message, type) {
        var stack = getFlashStack();

        var toast = document.createElement("div");
        toast.className =
            "flash-toast flash flash--" +
            (type === "error" ? "error" : "success");
        toast.setAttribute("data-flash-toast", "");
        toast.setAttribute("role", type === "error" ? "alert" : "status");

        var text = document.createElement("span");
        text.className = "flash-toast__text";
        text.textContent = message;
        toast.appendChild(text);

        var close = document.createElement("button");
        close.type = "button";
        close.className = "flash-toast__close";
        close.setAttribute("data-flash-close", "");
        close.setAttribute("aria-label", "Закрыть уведомление");
        close.innerHTML = "&times;";
        toast.appendChild(close);

        stack.appendChild(toast);

        registerFlashToast(toast);
    }

    /* --- Корзина: мини-виджет в шапке и страница корзины --- */
    function getCsrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute("content") : "";
    }

    var CART_ICON =
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="21" r="1" /><circle cx="20" cy="21" r="1" /><path d="M1 1h4l2.7 13.4a2 2 0 0 0 2 1.6h9.7a2 2 0 0 0 2-1.6L23 6H6" /></svg>';
    var TRASH_ICON =
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" /></svg>';

    /* Общий счётчик запросов: применяем только ответ последней операции,
       чтобы при быстрых кликах количество не «откатывалось». */
    var cartSeq = 0;

    function escapeHtml(value) {
        return String(value == null ? "" : value).replace(
            /[&<>"']/g,
            function (ch) {
                return {
                    "&": "&amp;",
                    "<": "&lt;",
                    ">": "&gt;",
                    '"': "&quot;",
                    "'": "&#39;",
                }[ch];
            },
        );
    }

    /* Верхняя граница количества для позиции: не больше фактического остатка
       (и не больше 20, как допускает серверная валидация). */
    function cartLimit(item) {
        var max = parseInt(item && item.max, 10);
        if (!max || max < 1) max = parseInt(item && item.stock, 10);
        if (!max || max < 1) max = 1;
        return Math.min(20, max);
    }

    /* Единая точка публикации состояния корзины: на неё подписаны и бейдж
       в шапке, и мини-корзина, и страница корзины. */
    function publishCart(cart, options) {
        if (!cart) return;
        document.dispatchEvent(
            new CustomEvent("cart:updated", {
                detail: { cart: cart, open: !!(options && options.open) },
            }),
        );
    }

    function cartApi(method, url, payload) {
        var id = ++cartSeq;
        var headers = {
            Accept: "application/json",
            "X-Requested-With": "XMLHttpRequest",
        };
        var token = getCsrfToken();
        if (token) headers["X-CSRF-TOKEN"] = token;

        var options = {
            method: method,
            headers: headers,
            credentials: "same-origin",
        };
        if (payload) {
            headers["Content-Type"] = "application/json";
            options.body = JSON.stringify(payload);
        }

        return fetch(url, options)
            .then(function (response) {
                return response
                    .json()
                    .catch(function () {
                        return {};
                    })
                    .then(function (data) {
                        return {
                            id: id,
                            ok: response.ok,
                            status: response.status,
                            data: data,
                        };
                    });
            })
            .catch(function () {
                return { id: id, ok: false, status: 0, data: {} };
            });
    }

    function cartMutate(method, url, payload, options) {
        return cartApi(method, url, payload).then(function (result) {
            if (result.id !== cartSeq) return result;
            if (result.ok && result.data && result.data.cart) {
                publishCart(result.data.cart, options);
            } else if (result.data) {
                showFlashToast(collectFormErrors(result.data), "error");
            }
            return result;
        });
    }

    function initHeaderCart() {
        var root = document.querySelector("[data-cart]");
        if (!root) return;

        var panel = root.querySelector("[data-cart-panel]");
        var toggle = root.querySelector("[data-cart-toggle]");
        var closeBtn = root.querySelector("[data-cart-close]");
        var body = root.querySelector("[data-cart-body]");
        var foot = root.querySelector("[data-cart-foot]");
        var countEl = root.querySelector("[data-cart-count]");
        var subtotalEl = root.querySelector("[data-cart-subtotal]");
        if (!panel || !toggle || !body) return;

        var stateUrl = root.getAttribute("data-cart-url");
        var updateUrl = root.getAttribute("data-cart-update-url");
        var removeUrl = root.getAttribute("data-cart-remove-url");

        var EMPTY_HTML =
            '<div class="cart-panel__empty">' +
            CART_ICON +
            "<p>Корзина пуста.<br>Добавьте работы из магазина.</p>" +
            "</div>";

        function lineHtml(item) {
            var max = cartLimit(item);
            var quantity = parseInt(item.quantity, 10) || 1;
            var minusDisabled = quantity <= 1 ? " disabled" : "";
            var plusDisabled = quantity >= max ? " disabled" : "";

            return (
                '<div class="cart-line" data-cart-variant="' +
                escapeHtml(item.variant_id) +
                '" data-cart-max="' +
                max +
                '">' +
                '<a class="cart-line__media" href="' +
                escapeHtml(item.url) +
                '"><img src="' +
                escapeHtml(item.image) +
                '" alt="" loading="lazy"></a>' +
                '<div class="cart-line__info">' +
                '<a class="cart-line__title" href="' +
                escapeHtml(item.url) +
                '">' +
                escapeHtml(item.title) +
                "</a>" +
                '<div class="cart-line__meta">' +
                escapeHtml(item.variant_name) +
                " · арт. " +
                escapeHtml(item.sku) +
                "</div>" +
                '<div class="cart-line__controls">' +
                '<div class="qty qty--sm">' +
                '<button type="button" data-cart-step="-1" aria-label="Уменьшить количество"' +
                minusDisabled +
                ">−</button>" +
                '<input type="number" min="1" max="' +
                max +
                '" value="' +
                escapeHtml(item.quantity) +
                '" data-cart-qty aria-label="Количество товара">' +
                '<button type="button" data-cart-step="1" aria-label="Увеличить количество"' +
                plusDisabled +
                ">+</button>" +
                "</div>" +
                '<button type="button" class="cart-line__remove" data-cart-remove aria-label="Удалить товар из корзины">' +
                TRASH_ICON +
                "</button>" +
                "</div></div>" +
                '<div class="cart-line__price">' +
                escapeHtml(item.line_total) +
                "</div>" +
                "</div>"
            );
        }

        function render(cart) {
            if (!cart) return;

            var count = parseInt(cart.count, 10) || 0;
            if (countEl) {
                countEl.textContent = count;
                if (count > 0) countEl.removeAttribute("hidden");
                else countEl.setAttribute("hidden", "");
            }
            toggle.setAttribute("aria-label", "Корзина, товаров: " + count);

            var items = Array.isArray(cart.items) ? cart.items : [];
            if (!items.length) {
                body.innerHTML = EMPTY_HTML;
                if (foot) foot.setAttribute("hidden", "");
                return;
            }

            body.innerHTML = items.map(lineHtml).join("");
            if (subtotalEl) subtotalEl.textContent = cart.subtotal || "";
            if (foot) foot.removeAttribute("hidden");
        }

        function openPanel() {
            root.classList.add("is-open");
            toggle.setAttribute("aria-expanded", "true");
        }
        function closePanel() {
            root.classList.remove("is-open");
            toggle.setAttribute("aria-expanded", "false");
        }
        function isOpen() {
            return root.classList.contains("is-open");
        }

        function updateQuantity(variantId, quantity) {
            if (!variantId) return;
            var url = updateUrl.replace(
                "__VARIANT__",
                encodeURIComponent(variantId),
            );
            cartMutate("PATCH", url, { quantity: quantity });
        }

        function removeItem(variantId) {
            if (!variantId) return;
            var url = removeUrl.replace(
                "__VARIANT__",
                encodeURIComponent(variantId),
            );
            cartMutate("DELETE", url);
        }

        function refresh() {
            cartApi("GET", stateUrl).then(function (result) {
                if (result.id !== cartSeq) return;
                if (result.ok && result.data && result.data.cart) {
                    render(result.data.cart);
                }
            });
        }

        toggle.addEventListener("click", function () {
            if (isOpen()) {
                closePanel();
            } else {
                openPanel();
                refresh();
            }
        });
        if (closeBtn) closeBtn.addEventListener("click", closePanel);

        function syncLineButtons(line, quantity) {
            var max = parseInt(line.getAttribute("data-cart-max"), 10) || 20;
            var minus = line.querySelector('[data-cart-step="-1"]');
            var plus = line.querySelector('[data-cart-step="1"]');
            if (minus) minus.disabled = quantity <= 1;
            if (plus) plus.disabled = quantity >= max;
        }

        body.addEventListener("click", function (event) {
            var step = event.target.closest("[data-cart-step]");
            if (step) {
                if (step.disabled) return;
                var line = step.closest("[data-cart-variant]");
                if (!line) return;
                var input = line.querySelector("[data-cart-qty]");
                if (!input) return;
                var max = parseInt(line.getAttribute("data-cart-max"), 10) || 20;
                var value =
                    (parseInt(input.value, 10) || 1) +
                    parseInt(step.getAttribute("data-cart-step"), 10);
                value = Math.min(max, Math.max(1, value));
                if (String(value) === input.value) return;
                input.value = value;
                syncLineButtons(line, value);
                updateQuantity(line.getAttribute("data-cart-variant"), value);
                return;
            }

            var remove = event.target.closest("[data-cart-remove]");
            if (remove) {
                var item = remove.closest("[data-cart-variant]");
                if (item) removeItem(item.getAttribute("data-cart-variant"));
            }
        });

        body.addEventListener("change", function (event) {
            var input = event.target.closest("[data-cart-qty]");
            if (!input) return;
            var line = input.closest("[data-cart-variant]");
            if (!line) return;
            var max = parseInt(line.getAttribute("data-cart-max"), 10) || 20;
            var value = Math.min(
                max,
                Math.max(1, parseInt(input.value, 10) || 1),
            );
            input.value = value;
            syncLineButtons(line, value);
            updateQuantity(line.getAttribute("data-cart-variant"), value);
        });

        document.addEventListener("click", function (event) {
            if (isOpen() && !root.contains(event.target)) closePanel();
        });
        document.addEventListener("keydown", function (event) {
            if (event.key === "Escape" && isOpen()) {
                closePanel();
                toggle.focus();
            }
        });

        document.addEventListener("cart:updated", function (event) {
            var detail = event.detail || {};
            render(detail.cart);
            if (detail.open) openPanel();
        });
    }

    /* --- Страница корзины: мгновенные изменения и синхронизация с шапкой --- */
    function initCartPage() {
        var page = document.querySelector("[data-cart-page]");
        if (!page) return;

        var updateUrl = page.getAttribute("data-cart-update-url");
        var removeUrl = page.getAttribute("data-cart-remove-url");
        var shopUrl = page.getAttribute("data-cart-shop-url") || "/magazin";

        /* Кнопка «Обновить» нужна только как резерв без JavaScript. */
        page.querySelectorAll("[data-cart-submit]").forEach(function (btn) {
            btn.hidden = true;
        });

        function rowOf(element) {
            return element.closest("[data-cart-item]");
        }

        function maxOf(row) {
            return parseInt(row.getAttribute("data-cart-max"), 10) || 20;
        }

        function syncButtons(row, quantity) {
            var max = maxOf(row);
            var minus = row.querySelector('[data-cart-step="-1"]');
            var plus = row.querySelector('[data-cart-step="1"]');
            if (minus) minus.disabled = quantity <= 1;
            if (plus) plus.disabled = quantity >= max;
        }

        function updateQuantity(variantId, quantity) {
            if (!variantId) return;
            var url = updateUrl.replace(
                "__VARIANT__",
                encodeURIComponent(variantId),
            );
            cartMutate("PATCH", url, { quantity: quantity });
        }

        function removeItem(variantId) {
            if (!variantId) return;
            var url = removeUrl.replace(
                "__VARIANT__",
                encodeURIComponent(variantId),
            );
            cartMutate("DELETE", url);
        }

        function commitRow(row, quantity) {
            var max = maxOf(row);
            var value = Math.min(max, Math.max(1, quantity));
            var input = row.querySelector("[data-cart-qty]");
            if (input) input.value = value;
            syncButtons(row, value);
            updateQuantity(row.getAttribute("data-variant-id"), value);
        }

        page.addEventListener("click", function (event) {
            var step = event.target.closest("[data-cart-step]");
            if (step) {
                if (step.disabled) return;
                var row = rowOf(step);
                if (!row) return;
                var input = row.querySelector("[data-cart-qty]");
                if (!input) return;
                var current = parseInt(input.value, 10) || 1;
                var next =
                    current + parseInt(step.getAttribute("data-cart-step"), 10);
                if (next === current) return;
                commitRow(row, next);
                return;
            }

            var remove = event.target.closest("[data-cart-remove]");
            if (remove) {
                event.preventDefault();
                var itemRow = rowOf(remove);
                if (itemRow)
                    removeItem(itemRow.getAttribute("data-variant-id"));
            }
        });

        page.addEventListener("change", function (event) {
            var input = event.target.closest("[data-cart-qty]");
            if (!input) return;
            var row = rowOf(input);
            if (!row) return;
            commitRow(row, parseInt(input.value, 10) || 1);
        });

        page.addEventListener("submit", function (event) {
            var form = event.target.closest("[data-cart-update-form]");
            if (!form) return;
            event.preventDefault();
            var row = rowOf(form);
            if (!row) return;
            var input = row.querySelector("[data-cart-qty]");
            if (input) commitRow(row, parseInt(input.value, 10) || 1);
        });

        document.addEventListener("cart:updated", function (event) {
            var cart = event.detail && event.detail.cart;
            if (cart) renderCartPage(page, cart, shopUrl);
        });
    }

    function renderCartPage(page, cart, shopUrl) {
        var items = Array.isArray(cart.items) ? cart.items : [];
        var byId = {};
        items.forEach(function (item) {
            byId[String(item.variant_id)] = item;
        });

        page.querySelectorAll("[data-cart-item]").forEach(function (row) {
            var item = byId[row.getAttribute("data-variant-id")];
            if (!item) {
                if (row.parentNode) row.parentNode.removeChild(row);
                return;
            }

            var max = cartLimit(item);
            row.setAttribute("data-cart-max", max);

            var input = row.querySelector("[data-cart-qty]");
            if (input) {
                input.value = item.quantity;
                input.setAttribute("max", max);
            }

            var minus = row.querySelector('[data-cart-step="-1"]');
            var plus = row.querySelector('[data-cart-step="1"]');
            var quantity = parseInt(item.quantity, 10) || 1;
            if (minus) minus.disabled = quantity <= 1;
            if (plus) plus.disabled = quantity >= max;

            var lineTotal = row.querySelector("[data-cart-line-total]");
            if (lineTotal) lineTotal.textContent = item.line_total;
        });

        var subtotal = page.querySelector("[data-cart-subtotal]");
        if (subtotal) subtotal.textContent = cart.subtotal || "";
        var total = page.querySelector("[data-cart-total]");
        if (total) total.textContent = cart.subtotal || "";

        if (!items.length) showCartPageEmpty(page, shopUrl);
    }

    function showCartPageEmpty(page, shopUrl) {
        var layout = page.querySelector("[data-cart-layout]");
        if (!layout) return;

        layout.outerHTML =
            '<div class="empty" style="margin-top:24px">' +
            CART_ICON +
            "<h3>Корзина пуста</h3>" +
            "<p>Добавьте авторские работы из магазина — здесь появятся выбранные товары.</p>" +
            '<a class="btn" href="' +
            escapeHtml(shopUrl) +
            '">Перейти в магазин</a>' +
            "</div>";
    }

    /* --- Тень шапки при прокрутке --- */
    function initStickyHeaderShadow() {
        var header = document.querySelector(".site-header");
        if (!header) return;
        window.addEventListener(
            "scroll",
            function () {
                header.style.boxShadow =
                    window.scrollY > 10
                        ? "0 10px 30px rgba(38,46,110,.08)"
                        : "none";
            },
            { passive: true },
        );
    }
})();
