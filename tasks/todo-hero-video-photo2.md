# TODO: заменить плейсхолдер видео и удалить photo-2.jpg

Создано в рамках задачи `tasks/task-section-video.md`.

## 1. Видео-фон hero (`.hero--video`)

- Ожидаемый ассет: `public/assets/video/korni-studio-loop.mp4`
- Статус: **плейсхолдер отсутствует** (видеомодель недоступна в среде выполнения).
- Разметка вынесена в партиал `resources/views/partials/hero-video.blade.php`
  (комментарий `TODO(video): replace placeholder with generated Korni loop`),
  подключается на страницах: `/about`, «Как проходят занятия», «Мастер-классы», «Курсы».
- Пока файла нет: `<source>` даёт ошибку, срабатывает обработчик `error`
  в `public/js/site/modules/hero-video.js` → секция получает класс
  `hero--no-video` и остаётся на светлом фоне, текст читаем.
- Промпт для генерации: Приложение A в `tasks/task-section-video.md`.
  Требования: 10–15 с, без звука, 1920×1080, H.264 mp4, ≤ 8 МБ, бесшовная петля.

## 2. photo-2.jpg на других страницах

Файл `public/images/photo-2.jpg` **не удалён** — он используется вне hero:

- `resources/views/auth/login.blade.php` (hero-медиа)
- `database/seeders/CatalogSeeder.php` (поле `image`, две записи: строки ~101 и ~236)

Использование в hero страниц `/about` и «Мастер-классы» удалено. Файл можно
удалить только после замены перечисленных использований.
