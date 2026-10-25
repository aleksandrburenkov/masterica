/* ==========================================================================
   Корни — улучшения выпадающего календаря Filament.
   Добавляет кнопки перехода на месяц/год вперёд-назад и «Сегодня».
   Работает поверх Alpine-компонента dateTimePickerFormComponent, не меняя
   его исходники; при недоступности Alpine использует select/input.
   ========================================================================== */
(function () {
    "use strict";

    var ICONS = {
        prevMonth:
            '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>',
        nextMonth:
            '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>',
        prevYear:
            '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m11 17-5-5 5-5"/><path d="m18 17-5-5 5-5"/></svg>',
        nextYear:
            '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m13 17 5-5-5-5"/><path d="m6 17 5-5-5-5"/></svg>',
    };

    function makeNavButton(step, label, icon) {
        var button = document.createElement("button");
        button.type = "button";
        button.className = "k-dtp-nav-btn";
        button.dataset.kStep = String(step);
        button.setAttribute("aria-label", label);
        button.title = label;
        button.innerHTML = icon;

        return button;
    }

    function getPickerState(root) {
        if (!window.Alpine || typeof window.Alpine.$data !== "function") {
            return null;
        }

        try {
            return window.Alpine.$data(root);
        } catch (error) {
            return null;
        }
    }

    function shiftMonths(root, months) {
        var state = getPickerState(root);

        if (state && state.focusedDate && typeof state.focusedDate.add === "function") {
            state.focusedDate = state.focusedDate.add(months, "month");
            return;
        }

        /* Резервный путь: управляем теми же select/input, что и Alpine. */
        var monthSelect = root.querySelector(".fi-fo-date-time-picker-month-select");
        var yearInput = root.querySelector(".fi-fo-date-time-picker-year-input");

        if (!monthSelect || !yearInput) {
            return;
        }

        var currentMonth = parseInt(monthSelect.value, 10);
        var currentYear = parseInt(yearInput.value, 10);

        if (isNaN(currentMonth) || isNaN(currentYear)) {
            return;
        }

        var target = new Date(currentYear, currentMonth + months, 1);

        yearInput.value = String(target.getFullYear());
        yearInput.dispatchEvent(new Event("input", { bubbles: true }));
        yearInput.dispatchEvent(new Event("change", { bubbles: true }));

        monthSelect.value = String(target.getMonth());
        monthSelect.dispatchEvent(new Event("change", { bubbles: true }));
    }

    function selectToday(root) {
        var state = getPickerState(root);

        if (!state) {
            return;
        }

        var now = typeof window.dayjs === "function" ? window.dayjs() : null;

        if (!now) {
            return;
        }

        /* Пишем значение в то же состояние, которое связано с Livewire.
           Так дата и текущее локальное время применяются независимо от
           того, какой месяц открыт в календаре, и сразу попадают в поле. */
        state.focusedDate = now;
        state.state = now.format("YYYY-MM-DD HH:mm:ss");

        if (typeof state.setDisplayText === "function") {
            state.setDisplayText();
        }

        if (
            typeof state.isOpen === "function" &&
            typeof state.togglePanelVisibility === "function" &&
            state.isOpen()
        ) {
            state.togglePanelVisibility();
        }
    }

    function enhancePanel(panel) {
        if (!panel) {
            return;
        }

        if (panel.dataset.kCalendar === "ready") {
            if (panel.querySelector(".k-dtp-header")) {
                return;
            }

            delete panel.dataset.kCalendar;
        }

        /* x-data находится на внутреннем контейнере Filament, а не на
           внешней обёртке .fi-fo-date-time-picker. */
        var root = panel.closest('[x-data*="dateTimePickerFormComponent"]');

        if (!root) {
            root = panel.closest(".fi-fo-date-time-picker");
        }

        var header = panel.querySelector(".fi-fo-date-time-picker-panel-header");
        var monthSelect = panel.querySelector(".fi-fo-date-time-picker-month-select");
        var yearInput = panel.querySelector(".fi-fo-date-time-picker-year-input");

        if (!root || !monthSelect || !yearInput) {
            return;
        }

        var nav = document.createElement("div");
        nav.className = "k-dtp-header";

        var monthRow = document.createElement("div");
        monthRow.className = "k-dtp-row k-dtp-row--month";

        var monthCaption = document.createElement("div");
        monthCaption.className = "k-dtp-caption";
        monthCaption.appendChild(monthSelect);

        var yearRow = document.createElement("div");
        yearRow.className = "k-dtp-row k-dtp-row--year";

        var yearCaption = document.createElement("div");
        yearCaption.className = "k-dtp-caption k-dtp-caption--year";

        var prevYear = makeNavButton(-12, "Предыдущий год", ICONS.prevYear);
        var prevMonth = makeNavButton(-1, "Предыдущий месяц", ICONS.prevMonth);
        var nextMonth = makeNavButton(1, "Следующий месяц", ICONS.nextMonth);
        var nextYear = makeNavButton(12, "Следующий год", ICONS.nextYear);

        var yearSuffix = document.createElement("span");
        yearSuffix.className = "k-dtp-year-suffix";
        yearSuffix.textContent = "г.";
        yearSuffix.setAttribute("aria-hidden", "true");

        yearCaption.appendChild(yearInput);
        yearCaption.appendChild(yearSuffix);

        monthRow.appendChild(prevMonth);
        monthRow.appendChild(monthCaption);
        monthRow.appendChild(nextMonth);

        yearRow.appendChild(prevYear);
        yearRow.appendChild(yearCaption);
        yearRow.appendChild(nextYear);

        nav.appendChild(monthRow);
        nav.appendChild(yearRow);

        if (header) {
            header.replaceWith(nav);
        } else {
            panel.insertBefore(nav, panel.firstChild);
        }

        nav.addEventListener("click", function (event) {
            var button = event.target.closest("[data-k-step]");

            if (!button) {
                return;
            }

            shiftMonths(root, parseInt(button.dataset.kStep, 10) || 0);
        });

        var footer = panel.querySelector(".k-dtp-footer");

        if (!footer) {
            footer = document.createElement("div");
            footer.className = "k-dtp-footer";

            var todayButton = document.createElement("button");
            todayButton.type = "button";
            todayButton.className = "k-dtp-today";
            todayButton.textContent = "Сегодня";
            todayButton.setAttribute(
                "aria-label",
                "Установить сегодняшнюю дату и текущее время",
            );

            todayButton.addEventListener("click", function () {
                selectToday(root);
            });

            footer.appendChild(todayButton);
            panel.appendChild(footer);
        }

        panel.dataset.kCalendar = "ready";
    }

    function scan(scope) {
        var root = scope || document;

        if (root.matches && root.matches(".fi-fo-date-time-picker-panel")) {
            enhancePanel(root);
        }

        if (root.querySelectorAll) {
            root.querySelectorAll(".fi-fo-date-time-picker-panel").forEach(enhancePanel);
        }
    }

    function boot() {
        scan(document);

        if (typeof MutationObserver !== "undefined" && document.body) {
            var observer = new MutationObserver(function (records) {
                records.forEach(function (record) {
                    record.addedNodes.forEach(function (node) {
                        if (node.nodeType === 1) {
                            scan(node);
                        }
                    });
                });
            });

            observer.observe(document.body, {
                childList: true,
                subtree: true,
            });
        }

        document.addEventListener("livewire:navigated", function () {
            scan(document);
        });

        document.addEventListener(
            "click",
            function (event) {
                if (
                    event.target.closest &&
                    event.target.closest(".fi-fo-date-time-picker-trigger")
                ) {
                    window.setTimeout(function () {
                        scan(document);
                    }, 0);
                }
            },
            true,
        );
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", boot);
    } else {
        boot();
    }
})();
