(function () {
    "use strict";

    if (window.korniAdminSidebarScrollLock) {
        return;
    }

    window.korniAdminSidebarScrollLock = true;

    var LOCK_CLASS = "admin-sidebar-scroll-locked";
    var mobileQuery = window.matchMedia("(max-width: 1023px)");
    var effectStarted = false;

    function getSidebar() {
        return window.Alpine && typeof window.Alpine.store === "function"
            ? window.Alpine.store("sidebar")
            : null;
    }

    function hasOpenModal() {
        return Array.prototype.some.call(
            document.querySelectorAll('[aria-modal="true"]'),
            function (modal) {
                return window.getComputedStyle(modal).display !== "none";
            },
        );
    }

    function setLocked(locked) {
        document.documentElement.classList.toggle(LOCK_CLASS, locked);
        document.body.classList.toggle(LOCK_CLASS, locked);
    }

    function sync() {
        var sidebar = getSidebar();

        setLocked(Boolean(sidebar && sidebar.isOpen && mobileQuery.matches));
    }

    function closeSidebar() {
        if (!mobileQuery.matches) {
            return;
        }

        var sidebar = getSidebar();

        if (!sidebar || !sidebar.isOpen) {
            return;
        }

        sidebar.close();
        sync();
    }

    function handleKeydown(event) {
        if (event.key !== "Escape" || !mobileQuery.matches || hasOpenModal()) {
            return;
        }

        var sidebar = getSidebar();

        if (sidebar && sidebar.isOpen) {
            event.preventDefault();
            closeSidebar();
        }
    }

    function handleClick(event) {
        if (!mobileQuery.matches || !event.target.closest) {
            return;
        }

        if (event.target.closest("#fi-main-sidebar a")) {
            closeSidebar();
        }
    }

    function handleNavigate() {
        closeSidebar();
        sync();
    }

    function startEffect() {
        if (
            effectStarted ||
            !window.Alpine ||
            typeof window.Alpine.effect !== "function"
        ) {
            return;
        }

        var sidebar = getSidebar();

        if (!sidebar) {
            return;
        }

        effectStarted = true;

        window.Alpine.effect(function () {
            void sidebar.isOpen;
            sync();
        });

        sync();
    }

    document.addEventListener("keydown", handleKeydown);
    document.addEventListener("click", handleClick, true);
    document.addEventListener("livewire:navigate", handleNavigate);
    document.addEventListener("livewire:navigated", handleNavigate);
    document.addEventListener("alpine:initialized", startEffect);

    if (typeof mobileQuery.addEventListener === "function") {
        mobileQuery.addEventListener("change", sync);
    } else if (typeof mobileQuery.addListener === "function") {
        mobileQuery.addListener(sync);
    }

    window.addEventListener("pageshow", sync);

    if (window.Alpine) {
        startEffect();
    }

    sync();
})();
