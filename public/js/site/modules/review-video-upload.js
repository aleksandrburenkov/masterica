/* ==========================================================================
   Корни — модуль «Загрузка видеоотзыва».
   Поле принимает файл перетаскиванием и по клику открывает выбор файла.
   Клиентская проверка формата и размера лишь дополняет серверную.
   ========================================================================== */
(function () {
    'use strict';

    function formatSize(bytes) {
        var units = ['Б', 'КБ', 'МБ', 'ГБ'];
        var value = bytes;
        var unit = 0;

        while (value >= 1024 && unit < units.length - 1) {
            value /= 1024;
            unit++;
        }

        return (unit === 0
            ? String(Math.round(value))
            : value.toFixed(1).replace('.', ',')) + ' ' + units[unit];
    }

    function initReviewVideoUploads() {
        var drops = document.querySelectorAll('[data-video-drop]');
        if (!drops.length) return;

        drops.forEach(function (drop) {
            if (drop.getAttribute('data-video-ready') === '1') return;
            drop.setAttribute('data-video-ready', '1');

            var body = drop.querySelector('[data-video-drop-body]');
            var input = drop.querySelector('[data-video-drop-input]');
            var preview = drop.querySelector('[data-video-drop-preview]');
            var player = drop.querySelector('[data-video-drop-player]');
            var nameEl = drop.querySelector('[data-video-drop-name]');
            var removeBtn = drop.querySelector('[data-video-drop-remove]');
            var errorEl = drop.querySelector('[data-video-drop-error]');
            var form = drop.closest('form');

            if (!input) return;

            var maxKb = parseInt(drop.getAttribute('data-video-max'), 10) || 0;
            var allowed = (drop.getAttribute('data-video-accept') || '')
                .split(',')
                .map(function (item) {
                    return item.trim().toLowerCase();
                })
                .filter(Boolean);

            var objectUrl = null;

            function showError(message) {
                drop.classList.add('is-invalid');
                if (errorEl) {
                    errorEl.textContent = message;
                    errorEl.hidden = false;
                }
            }

            function clearError() {
                drop.classList.remove('is-invalid');
                if (errorEl) {
                    errorEl.textContent = '';
                    errorEl.hidden = true;
                }
            }

            function extensionOf(fileName) {
                var parts = (fileName || '').split('.');
                return parts.length > 1 ? parts.pop().toLowerCase() : '';
            }

            function validate(file) {
                if (!file) return 'Файл не выбран.';

                var extension = extensionOf(file.name);
                if (allowed.length && allowed.indexOf(extension) === -1) {
                    return 'Недопустимый формат. Разрешены: ' + allowed.join(', ') + '.';
                }

                if (file.type && file.type.indexOf('video/') !== 0) {
                    return 'Можно загрузить только видеофайл.';
                }

                if (maxKb && file.size > maxKb * 1024) {
                    return 'Файл больше ' + Math.round(maxKb / 1024) + ' МБ.';
                }

                return null;
            }

            function clearPreview() {
                if (objectUrl) {
                    URL.revokeObjectURL(objectUrl);
                    objectUrl = null;
                }
                if (player) player.removeAttribute('src');
                if (nameEl) nameEl.textContent = '';
                if (preview) preview.hidden = true;
                if (body) body.hidden = false;
            }

            function showPreview(file) {
                if (objectUrl) URL.revokeObjectURL(objectUrl);
                objectUrl = URL.createObjectURL(file);

                if (player) {
                    player.src = objectUrl;
                    player.load();
                }
                if (nameEl) {
                    nameEl.textContent = file.name + ' · ' + formatSize(file.size);
                }
                if (preview) preview.hidden = false;
                if (body) body.hidden = true;
            }

            function assign(file) {
                try {
                    var data = new DataTransfer();
                    data.items.add(file);
                    input.files = data.files;
                } catch (error) {
                    /* Старый браузер: файл всё равно отправлен не будет — просим выбрать снова. */
                    if (!input.files || !input.files.length) {
                        return false;
                    }
                }

                return true;
            }

            function accept(file) {
                clearError();

                var message = validate(file);
                if (message) {
                    showError(message);

                    return;
                }

                showPreview(file);
            }

            body.addEventListener('click', function () {
                input.click();
            });

            input.addEventListener('change', function () {
                if (input.files && input.files[0]) {
                    accept(input.files[0]);
                }
            });

            ['dragenter', 'dragover'].forEach(function (eventName) {
                drop.addEventListener(eventName, function (event) {
                    event.preventDefault();
                    event.stopPropagation();
                    drop.classList.add('is-dragover');
                });
            });

            ['dragleave', 'dragend'].forEach(function (eventName) {
                drop.addEventListener(eventName, function (event) {
                    event.preventDefault();
                    drop.classList.remove('is-dragover');
                });
            });

            drop.addEventListener('drop', function (event) {
                event.preventDefault();
                event.stopPropagation();
                drop.classList.remove('is-dragover');

                var file =
                    event.dataTransfer && event.dataTransfer.files
                        ? event.dataTransfer.files[0]
                        : null;

                if (!file) return;

                var message = validate(file);
                if (message) {
                    showError(message);

                    return;
                }

                if (!assign(file)) {
                    showError('Не удалось прикрепить файл. Выберите его кнопкой.');

                    return;
                }

                accept(file);
            });

            if (removeBtn) {
                removeBtn.addEventListener('click', function () {
                    clearError();
                    input.value = '';
                    clearPreview();
                });
            }

            function resetDrop() {
                clearError();
                clearPreview();
            }

            if (form) {
                form.addEventListener('reset', resetDrop);
                form.addEventListener('form:reset', resetDrop);
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initReviewVideoUploads);
    } else {
        initReviewVideoUploads();
    }
})();
