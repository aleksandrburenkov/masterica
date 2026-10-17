````markdown
# Задача: Полноэкранный видео-фон hero-секции страницы «О студии» (фото photo-2.jpg удаляется)

**Тип:** feature  
**Страница:** `/about` («О студии»)  
**Блок:** `<section class="hero">`  
**Приоритет:** medium  
**Концепция:** фото из hero убирается полностью; вся секция становится видео-блоком (full-bleed), заголовок/текст/кнопка — поверх полупрозрачного видео. Видео только на desktop и tablet. JS разрешён, но запрещены любые «блокировщики анимаций».

---

## 1. Контекст и текущая разметка

Текущее состояние секции (фактический markup):

```html
<section class="hero">
    <div class="container">
        <div class="hero__inner">
            <div class="hero__content is-visible" data-reveal="">
                <h1>О студии «Корни»</h1>
                <p class="lead">
                    Творческая студия в Брянске: занятия для детей и взрослых и
                    авторские работы ручной работы. Основное направление —
                    плетение из бумажной лозы.
                </p>
                <a class="btn" href="http://masterica/master-klassy"
                    >Выбрать занятие</a
                >
            </div>
            <div class="hero__media">
                <img
                    src="http://masterica/images/photo-2.jpg"
                    alt="Студия «Корни»"
                    loading="lazy"
                />
            </div>
        </div>
    </div>
</section>
```
````

Особенности, которые нужно учесть:

- `.hero__inner` — двухколоночная сетка (контент | медиа). Становится одноколоночной.
- На контенте работает существующая reveal-система сайта (`data-reveal` → класс `is-visible`). Её НЕ трогаем и НЕ ломаем.
- Фото-ассет: `images/photo-2.jpg`.
- Хлебные крошки над секцией и карточки преимуществ под секцией не изменяются.

## 2. Цель

Перестроить `<section class="hero">` на схему «полноэкранный видео-фон + контент поверх», удалить фото `photo-2.jpg` по правилу §7.4, показать видео только на desktop/tablet, гарантировать читаемость текста поверх видео.

## 3. Scope

### In scope

- Демонтаж колонки `.hero__media` и её стилей.
- Если класс `.hero__media` задействован на других страницах, адаптировать класс не ломая стили остальных страниц.
- Видео-слой на всю секцию (absolute, object-fit: cover, полупрозрачность).
- Контент-слой поверх: `h1`, `.lead`, `.btn` (тексты и href кнопки не меняются).
- Responsive: видео на ≥768px (tablet + desktop), скрыто на <768px (mobile) — секция на обычном светлом фоне.
- Опциональный JS строго из белого списка §7.3.
- Удаление/аудит ассета `photo-2.jpg`.

### Out of scope

- Reveal-система (`data-reveal` / `is-visible`) — без изменений.
- Хлебные крошки, карточки преимуществ, другие секции и страницы.
- Тексты, кнопка, её href и стили `.btn`.

## 4. Целевая разметка

```html
<section class="hero">
    <video
        class="hero__video"
        autoplay
        muted
        loop
        playsinline
        preload="auto"
        disablepictureinpicture
        tabindex="-1"
        aria-hidden="true"
    >
        <source src="/assets/video/korni-studio-loop.mp4" type="video/mp4" />
    </video>
    <div class="container">
        <div class="hero__inner">
            <div class="hero__content" data-reveal="">
                <h1>О студии «Корни»</h1>
                <p class="lead">
                    Творческая студия в Брянске: занятия для детей и взрослых и
                    авторские работы ручной работы. Основное направление —
                    плетение из бумажной лозы.
                </p>
                <a class="btn" href="http://masterica/master-klassy"
                    >Выбрать занятие</a
                >
            </div>
        </div>
    </div>
</section>
```

Слои:

```
<section class="hero">          ← position: relative; overflow: hidden
  ├─ video.hero__video          ← слой 0: absolute; inset: 0; cover; opacity 0.4
  └─ .container > .hero__inner  ← слой 1: position: relative; z-index: 1
```

Примечания:

- `<video>` — первый дочерний элемент секции, ДО `.container`.
- `data-reveal` на `.hero__content` сохраняем; класс `is-visible` в шаблон руками не дописываем (его ставит существующий reveal-скрипт; если в шаблоне он уже есть статически — оставить как есть).
- Блок `.hero__media` удаляется вместе с `<img>`.

## 5. Дизайн-требования

- Прозрачность видео: `opacity: 0.4` (коридор 0.3–0.5, финал фиксируется проверкой контраста §8). Токен `--hero-video-opacity`.
- Фон секции под видео — текущий светлый фон страницы; дополнительных scrim/градиентов не добавлять, если контраст проходит без них.
- `.hero`: full-bleed, без скруглений, `overflow: hidden`, `min-height: 480px` (desktop), вертикальные паддинги секции сохранить текущие.
- `.hero__inner`: убрать двухколоночную сетку (grid-template-columns / gap медиа-колонки), сохранить паддинги; контент выровнен слева как сейчас.
- Стили `.hero__media` и вложенных селекторов удалить из CSS полностью.
- Кнопка кликабельна поверх видео, hover-состояние работает.
- Новые токены кроме `--hero-video-opacity` не вводить; палитру текстов/кнопки не менять.

## 6. Видеоассет (генерируется)

| Параметр     | Значение                                                                      |
| ------------ | ----------------------------------------------------------------------------- |
| Источник     | генерация видеомоделью (Veo / Runway / Kling / аналог), промпт — Приложение A |
| Длительность | 10–15 с                                                                       |
| Звук         | отсутствует (без аудиодорожки)                                                |
| Разрешение   | 1920×1080 или шире (кроп object-fit: cover)                                   |
| Кодек        | H.264, mp4                                                                    |
| Вес          | ≤ 8 МБ                                                                        |
| Петля        | бесшовная (first frame = last frame)                                          |
| Путь         | `/assets/video/korni-studio-loop.mp4`                                         |
| Постер       | не используется                                                               |

Если генерация агенту недоступна: плейсхолдер-луп студийной тематики + комментарий `<!-- TODO(video): replace placeholder with generated Korni loop -->` + TODO-тикет.

## 7. Технические требования

### 7.1 CSS (референс ключевых правил)

```css
.hero {
    position: relative;
    overflow: hidden;
    min-height: 480px;
}

.hero__video {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    opacity: var(--hero-video-opacity, 0.4);
    z-index: 0;
}

.hero .container {
    position: relative;
    z-index: 1;
}

@media (max-width: 767.98px) {
    .hero__video {
        display: none;
    } /* единственное допустимое скрытие видео */
    .hero {
        min-height: 0;
    }
}
```

### 7.2 Разметка/стили

- Удалить `.hero__media` из HTML и все CSS-правила с этим селектором.
- Проверить не ломает ли верстку при удалении `.hero__media`, если ломает модифицировать или исправить.
- `.hero__inner` перевести на одну колонку; проверить tablet 768–1023px: контент не наезжает на края, паддинги сохранены.

### 7.3 JS: белый и чёрный списки

JS разрешён, но базовый сценарий должен работать и без него (autoplay/muted/loop/playsinline — нативно).

**Белый список (опционально, модуль `hero-video.js`):**

1. `video.muted = true` до попытки автоплея (фикс iOS Safari) + `video.play().catch(() => {})`.
2. Fade-in видео-слоя: по событию `loadeddata` добавлять класс `hero--video-ready` на секцию; в CSS плавный переход opacity от 0 к целевой (это анимация, а не блокировка).
3. Обработка `error` на video/source: класс `hero--no-video` на секции (секция остаётся на светлом фоне, текст читаем).

**Чёрный список («блокировщики анимаций») — ЗАПРЕЩЕНО:**

- `pause()` / остановка видео любым способом: IntersectionObserver-pause, `visibilitychange`-pause, снятие `autoplay`, удаление/подмена атрибутов воспроизведения.
- Обработки `prefers-reduced-motion`, отключающие или гасящие видео и любые анимации блока.
- `animation-play-state: paused`, принудительные `animation: none` / `transition: none` в рамках секции.
- Вмешательство в reveal-систему `data-reveal` (паузы, отключения, подмена её скрипта).
- Сторонние библиотеки управления автоплеем/ленивыми медиа.

### 7.4 Удаление photo-2.jpg (правило принятия решения)

1. Выполнить grep по `photo-2.jpg` во всём репозитории (шаблоны, CSS, конфиги, CMS-поля).
2. **Единственное использование — hero `/about`:** удалить файл из репозитория, убедиться в отсутствии 404 после деплоя.
3. **Есть другие использования:** файл временно оставить, удалить только использование в hero; создать TODO-тикет «Заменить photo-2.jpg на других страницах и удалить файл», перечислив найденные места в описании PR.
4. Фолбэков на фото в hero НЕ оставлять: при ошибке видео секция работает на светлом фоне (§7.3 п.3).

## 8. Критерии приёмки

- [ ] `.hero__media` и `<img>` отсутствуют в разметке; CSS-правила `.hero__media*` удалены.
- [ ] Видео занимает всю секцию full-bleed; контент (`h1`, `.lead`, `.btn`) отображается поверх, выровнен слева.
- [ ] Кнопка «Выбрать занятие» кликабельна, href `http://masterica/master-klassy` и hover не изменены.
- [ ] Контраст текста поверх видео при выбранной opacity: body ≥ 4.5:1, large text ≥ 3:1 (WCAG AA); значение opacity зафиксировано в PR.
- [ ] Видео: autoplay, без звука, бесшовный loop, без контролов; стартует в Chrome, Firefox, Safari, iPadOS.
- [ ] Desktop (≥1024px) и tablet (768–1023px): видео видно. Mobile (<768px): видео скрыто, секция на светлом фоне, контент как раньше.
- [ ] Reveal-анимация `.hero__content` (`data-reveal` → `is-visible`) работает как до задачи.
- [ ] В diff нет ни одной конструкции из чёрного списка §7.3; используемый JS (если есть) входит только в белый список.
- [ ] photo-2.jpg: файл удалён ИЛИ создан TODO-тикет по правилу §7.4; 404 в hero нет.
- [ ] CLS = 0; консоль без ошибок; валидация W3C без новых предупреждений.
- [ ] Lighthouse Performance ≥ 0.9 (desktop); деградация LCP ≤ 0.2 с к прежней версии.
- [ ] Хлебные крошки и карточки преимуществ не изменены.

## 9. План работ

1. Найти файлы шаблона и стилей секции (по селектору `.hero__inner`); зафиксировать текущие паддинги/сетку.
2. Grep `photo-2.jpg` → решение по §7.4.
3. Правка разметки по §4 (видео первым элементом секции, демонтаж `.hero__media`).
4. Стили: слоистость, opacity, media-запросы, чистка `.hero__media*`, одноколоночный `.hero__inner`.
5. Опционально JS из белого списка §7.3.
6. Ассет: генерация по Приложению A или плейсхолдер + TODO.
7. Прогон чек-листа §8 + Lighthouse (mobile/desktop) + проверка контраста.
8. Коммит: `feat(about): full-bleed generated video background in hero, drop photo-2.jpg`.

## 10. Definition of Done

- Все пункты §8 отмечены.
- CI зелёный, PR смержен.
- Созданы TODO-тикеты (плейсхолдер видео и/или photo-2.jpg на других страницах), если применимо.

---

## Приложение A. Промпт генерации видео

**Prompt (EN):**

> Seamless looping cinematic shot, 12 seconds: bright airy handicraft studio in soft daylight. Slow dolly-in from close-up of woman's hands weaving a basket from paper vine to wide view of the studio: light wooden tables, shelves with woven baskets and vases, spools of paper reed, blurred silhouettes of children and adults crafting in warm bokeh background. Airy pastel palette: white, milky beige, soft lavender accents. Gentle filmic light, shallow depth of field, calm smooth camera motion. No text, no logos, no watermarks, no direct faces in focus. First and last frames identical for seamless loop. 1920x1080, 24 fps, no audio track.

**Negative prompt:** text, captions, logo, watermark, UI elements, sharp faces, dark moody lighting, fast cuts, camera shake, audio.

**Приёмка ассета:** 10–15 с; стык петли неразличим при 3-кратном просмотре; после применения opacity 0.4 на светлом фоне палитра не спорит с тёмно-синим текстом заголовка.

```

```
