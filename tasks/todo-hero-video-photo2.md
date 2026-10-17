# TODO: заменить плейсхолдер видео и удалить photo-2.jpg

Создано в рамках задачи `tasks/task-section-video.md`.

## 1. Видео-фон hero `/about`

- Ожидаемый ассет: `public/assets/video/korni-studio-loop.mp4`
- Статус: **плейсхолдер отсутствует** (видеомодель недоступна в среде выполнения).
- В разметке `resources/views/pages/about.blade.php` оставлен комментарий
  `TODO(video): replace placeholder with generated Korni loop`.
- Пока файла нет: `<source>` даёт ошибку, срабатывает обработчик `error`
  в `public/js/site/modules/hero-video.js` → секция получает класс
  `hero--no-video` и остаётся на светлом фоне, текст читаем.
- Промпт для генерации: Приложение A в `tasks/task-section-video.md`.
  Требования: 10–15 с, без звука, 1920×1080, H.264 mp4, ≤ 8 МБ, бесшовная петля.

## 2. photo-2.jpg на других страницах

Файл `public/images/photo-2.jpg` **не удалён** — он используется вне hero `/about`:

- `resources/views/pages/masterclasses-index.blade.php` (hero-медиа)
- `resources/views/auth/login.blade.php` (hero-медиа)
- `database/seeders/CatalogSeeder.php` (поле `image`, две записи: строки ~101 и ~236)

Использование в hero страницы `/about` удалено. Файл можно удалить только после
замены перечисленных использований.
