# DEPLOY_BEGET.md

Краткий рабочий мануал по деплою текущего проекта на Beget.

Предполагается, что домен, SSL, PHP 8.3, SSH и доступ к MySQL уже настроены. Ниже — только то, что нужно для загрузки файлов, `public_html`, `.env` и двух вариантов базы: чистая БД и импорт существующей.

**Стек:** Laravel 13, Filament 5, PHP 8.3, MySQL/MariaDB. Точка входа — `public/index.php`, админка — `/admin`.

---

## 1. Placeholders

| Placeholder | Пример | Где взять |
|---|---|---|
| `<LOGIN>` | `myaccount` | Логин Beget / SSH. |
| `<HOST>` | `myaccount.beget.tech` | Сервер SSH из панели. |
| `<SITE_DIR>` | `masterica` | Каталог сайта в `~/`. |
| `<DOMAIN>` | `example.ru` | Основной домен без `https://`. |
| `<DB_NAME>` | `myaccount_masterica` | Раздел MySQL; Beget добавляет `<LOGIN>_`. |
| `<DB_USER>` | `myaccount_masterica` | На Beget совпадает с именем БД. |
| `<DB_PASSWORD>` | длинный пароль | Пароль БД. |
| `<DUMP_FILE>` | `masterica.sql` | Локальный SQL-дамп. |
| `<DUMP_FILE_NAME>` | `masterica.sql` | Имя файла дампа без локального пути. |
| `<MYSQLDUMP_PATH>` | `D:\...\mysqldump.exe` | Путь к `mysqldump` в OSPanel. |
| `<LOCAL_DB_NAME>` | `masterica_db` | Имя локальной БД. |
| `<LOCAL_DB_USER>` | `root` | Локальный пользователь MySQL. |
| `<ADMIN_EMAIL>` | `owner@example.ru` | Email владельца. |
| `<ADMIN_PASSWORD>` | длинный пароль | Пароль владельца. |
| `<MAILBOX>` | `noreply@example.ru` | Ящик Beget. |
| `<MAIL_PASSWORD>` | пароль ящика | Пароль ящика. |
| `<YANDEX_MAPS_API_KEY>` | ключ JS API | Yandex Console. |
| `<YANDEX_GEOCODER_API_KEY>` | ключ Geocoder API | Yandex Console. |

Команды выполняются по SSH:

```bash
ssh <LOGIN>@<HOST>
cd ~/<SITE_DIR>
```

Если команды `php8.3` нет, использовать `/usr/local/php/cgi/8.3/bin/php` вместо `php8.3`.

---

## 2. Какие файлы загружать, какие нет

### Загружать

```
app/
bootstrap/            # кроме bootstrap/cache/*.php
config/
database/
lang/
public/               # кроме public/storage и public/build
resources/
routes/
storage/              # структура + storage/app/public + storage/app/private/reviews
artisan
composer.json
composer.lock
.env.example
# при желании: подготовленный production-.env (не локальный .env как есть)
```

Для текущего проекта обязательно загрузить статику и медиа:

```
public/css/site/
public/js/site/
public/fonts/
public/images/
public/vendor/
public/assets/video/
storage/app/public/
storage/app/private/reviews/     # если есть видео
```

### Не загружать

```
.env                  # локальный, как есть — не загружать
vendor/               # создаст Composer
node_modules/         # не нужен
.git/                 # не нужен, если не деплой через git
storage/logs/*
storage/framework/cache/*
storage/framework/sessions/*
storage/framework/views/*
storage/framework/testing/*
bootstrap/cache/*.php
public/storage        # создаст artisan storage:link
public/css/filament   # создаст filament:upgrade
public/js/filament    # создаст filament:upgrade
public/fonts/filament # создаст filament:upgrade
public/hot
public/build          # текущие Blade-шаблоны его не используют
tests/
tasks/
```

### Сервер создаст сам

| Что | Чем создаётся |
|---|---|
| `vendor/` | `composer install` |
| `bootstrap/cache/packages.php`, `services.php` | Composer/artisan |
| `public/storage` | `php8.3 artisan storage:link` |
| `public/css/filament`, `public/js/filament`, `public/fonts/filament` | `php8.3 artisan filament:upgrade` |
| `storage/framework/{cache/data,sessions,views}` | Laravel; лучше создать пустыми заранее |
| `storage/logs` | Laravel; лучше создать пустым заранее |
| `.env` | вы создаёте вручную |

`storage/app/public` и `storage/app/private/reviews` — это **данные**, а не генерируемые файлы. Их нужно загрузить, если они есть локально.

---

## 3. Загрузка кода

### 3.1 Собрать архив локально

Из корня проекта (PowerShell):

```powershell
tar -czf <ARCHIVE> `
  --exclude=.git `
  --exclude=.kilo `
  --exclude=node_modules `
  --exclude=vendor `
  --exclude=.env `
  --exclude=.phpunit.result.cache `
  --exclude=storage/logs `
  --exclude=storage/framework/cache `
  --exclude=storage/framework/sessions `
  --exclude=storage/framework/views `
  --exclude=storage/framework/testing `
  --exclude=public/storage `
  --exclude=public/hot `
  --exclude=public/build `
  .
```

Проверить, что `.env` не попал в архив:

```powershell
tar -tzf <ARCHIVE> | Select-String "\.env$"
```

### 3.2 Загрузить и распаковать

```bash
# локально
scp <ARCHIVE> <LOGIN>@<HOST>:~/

# на сервере
cd ~/<SITE_DIR>
tar -xzf ~/<ARCHIVE>
ls -la
```

Ожидаемо: в `~/<SITE_DIR>` есть `app`, `public`, `artisan`, `composer.json`, `composer.lock`.

Если в архиве лишняя верхняя папка:

```bash
tar -xzf ~/<ARCHIVE> --strip-components=1
```

---

## 4. `public_html` → `public`

Проект должен лежать в `~/<SITE_DIR>`, а не внутри `public_html`.

### Если `public_html` — обычный каталог

```bash
cd ~/<SITE_DIR>

# сохранить дефолтный public_html, если он не нужен
mv public_html public_html.default

# создать symlink
ln -s public public_html

# проверить
ls -ld public_html
readlink -f public_html
ls -la public_html/index.php
```

Ожидаемо:

```
public_html -> public
readlink -f public_html  →  /home/<LOGIN>/<SITE_DIR>/public
```

### Если symlink уже есть

Ничего не делать. Проверить:

```bash
ls -ld public_html
readlink -f public_html
```

### Если в панели уже указан docroot `public`

Symlink не нужен. Удалить/переименовать старый `public_html`, чтобы он не перехватывал домен.

### Если после symlink 500/403

Добавить в `public/.htaccess`:

```apache
Options +FollowSymLinks
```

Если хостинг запрещает `Options`, использовать настройку docroot в панели.

### Проверка

```bash
curl -I https://<DOMAIN>/
curl -I https://<DOMAIN>/css/site/base.css
curl -I https://<DOMAIN>/.env
```

Ожидаемо:

- главная — 200;
- CSS — 200;
- `.env` — 403/404, никогда не 200.

Если `.env` отдаёт 200 — остановиться, исправить public root и сменить секреты.

---

## 5. `.env`

Можно двумя способами.

**Вариант A — создать прямо на сервере:**

```bash
cd ~/<SITE_DIR>
cp .env.example .env
chmod 600 .env
nano .env
```

**Вариант B — подготовить локально production-копию и загрузить её как `.env`:**

1. Сделать копию локального `.env`, например `.env.production`.
2. Изменить только production-значения: `APP_ENV`, `APP_DEBUG`, `APP_URL`, `DB_*`, `MAIL_*`, `SESSION_SECURE_COOKIE`, `QUEUE_CONNECTION`, флаги `VIDEO_*`.
3. Ключи Яндекса и прочие рабочие ключи можно оставить, если они не ограничены localhost/IP.
4. Загрузить файл на сервер **как** `~/<SITE_DIR>/.env`.
5. `chmod 600 .env`.

```bash
scp .env.production <LOGIN>@<HOST>:~/<SITE_DIR>/.env
```

Оба варианта рабочие. Нельзя загружать **локальный** `.env` без изменений: в нём локальные `DB_*`, `APP_DEBUG=true`, локальный `APP_KEY` и секреты, которые не должны попасть в архив/репозиторий. `.env.production` не коммитить и удалить после загрузки. Не оставлять ведущие пробелы перед ключами.

### Production-шаблон

```dotenv
APP_NAME="Корни"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://<DOMAIN>

APP_LOCALE=ru
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=ru_RU

LOG_CHANNEL=daily
LOG_LEVEL=warning
LOG_DAILY_DAYS=14

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=<DB_NAME>
DB_USERNAME=<DB_USER>
DB_PASSWORD="<DB_PASSWORD>"

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null
SESSION_SECURE_COOKIE=true

CACHE_STORE=database
QUEUE_CONNECTION=sync

FILESYSTEM_DISK=local
FILAMENT_FILESYSTEM_DISK=public

MAIL_MAILER=smtp
MAIL_SCHEME=smtps
MAIL_HOST=smtp.beget.com
MAIL_PORT=465
MAIL_USERNAME="<MAILBOX>"
MAIL_PASSWORD="<MAIL_PASSWORD>"
MAIL_TIMEOUT=15
MAIL_FROM_ADDRESS="<MAILBOX>"
MAIL_FROM_NAME="${APP_NAME}"

VIDEO_DISK=local
VIDEO_MAX_SIZE_KB=204800
VIDEO_MAX_DURATION=600
VIDEO_COMPRESSION=false
VIDEO_POSTER=false
VIDEO_CLAMAV_ENABLED=false

YANDEX_MAPS_API_KEY=<YANDEX_MAPS_API_KEY>
YANDEX_GEOCODER_API_KEY=<YANDEX_GEOCODER_API_KEY>
```

Пояснения:

- `APP_ENV=production` включает принудительный HTTPS в `AppServiceProvider`; SSL уже должен работать.
- `APP_DEBUG=false` обязателен.
- `QUEUE_CONNECTION=sync` — текущему проекту worker не нужен; письма и видео обрабатываются синхронно.
- `VIDEO_COMPRESSION=false`, `VIDEO_POSTER=false`, `VIDEO_CLAMAV_ENABLED=false` — если на хостинге нет `ffmpeg`/`clamscan`. Если есть — можно включить.
- `SESSION_SECURE_COOKIE=true` — только при рабочем HTTPS.
- `APP_KEY`: в шаблоне пустой. Если заливаете production-копию с локальным ключом и в БД есть зашифрованные поля — оставьте ключ. Иначе сгенерируйте новый после Composer.

После изменения `.env`:

```bash
php8.3 artisan config:clear
php8.3 artisan config:cache
```

---

## 6. Composer, ключ и Filament

### 6.1 Проверить Composer 2

```bash
php8.3 ~/.local/bin/composer --version
```

Если команда не найдена — установить локальный Composer 2:

```bash
mkdir -p ~/.local/bin
wget https://getcomposer.org/installer -O ~/.local/composer-setup.php
php8.3 ~/.local/composer-setup.php --install-dir="$HOME/.local/bin" --filename=composer
rm ~/.local/composer-setup.php
php8.3 ~/.local/bin/composer --version
```

Если системный Composer уже второй версии, можно использовать его:

```bash
php8.3 "$(which composer)" --version
```

### 6.2 Установить зависимости

```bash
cd ~/<SITE_DIR>

mkdir -p bootstrap/cache \
  storage/framework/cache/data \
  storage/framework/sessions \
  storage/framework/views \
  storage/logs

export COMPOSER_MEMORY_LIMIT=-1
php8.3 ~/.local/bin/composer install \
  --no-dev \
  --prefer-dist \
  --optimize-autoloader \
  --no-interaction

# если APP_KEY уже заполнен и его нужно сохранить — эту команду пропустить
php8.3 artisan key:generate --force
php8.3 artisan filament:upgrade
```

Ожидаемо:

- `Generating optimized autoload files`;
- `Application key set successfully`;
- ассеты Filament обновились.

Если Composer-скрипты упали:

```bash
php8.3 ~/.local/bin/composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction --no-scripts
php8.3 artisan package:discover --ansi
php8.3 artisan key:generate --force
php8.3 artisan filament:upgrade
```

---

## 7A. База данных: чистая БД

1. Создать **пустую** БД в панели MySQL.
2. Вписать `<DB_NAME>`, `<DB_USER>`, `<DB_PASSWORD>` в `.env`.
3. Убедиться, что `APP_ENV=production`.
4. Выполнить:

```bash
cd ~/<SITE_DIR>
php8.3 artisan migrate --force
php8.3 artisan db:seed --force
```

Ожидаемо: таблицы созданы, настройки/каталог/страницы заполнены. `DemoSeeder` не запускается, потому что `APP_ENV=production`.

Создать администратора:

```bash
php8.3 artisan tinker
```

```php
\App\Models\User::updateOrCreate(
    ['email' => '<ADMIN_EMAIL>'],
    [
        'name' => 'Администратор',
        'password' => '<ADMIN_PASSWORD>',
        'role' => \App\Enums\UserRole::Owner,
        'email_verified_at' => now(),
    ]
);
```

Важно:

- `CatalogSeeder` не полностью идемпотентен: повторный `db:seed` может создать дубликаты сессий/модулей/уроков. Сиды запускать **один раз**.
- Не использовать `migrate:fresh`, `migrate:refresh`, `db:wipe` на боевой БД.
- Если `APP_ENV` не `production`, запустится `DemoSeeder` и создаст демо-аккаунты с паролем `password`.

Проверка:

```bash
php8.3 artisan migrate:status
php8.3 artisan about
```

---

## 7B. База данных: импорт существующей БД

Это вариант, когда БД уже наполнена и вы переносите её вместе с данными. Raw-файлы MySQL (`ibdata1`, `*.ibd`) на shared-хостинг не копируются — нужен SQL-дамп.

### B1. Дамп локально

Найти `mysqldump.exe` в OSPanel, например:

```
D:\...\OSPanel\modules\database\MariaDB-10.5-Win10\bin\mysqldump.exe
```

PowerShell:

```powershell
& "<MYSQLDUMP_PATH>" `
  -u <LOCAL_DB_USER> `
  --default-character-set=utf8mb4 `
  --single-transaction `
  --quick `
  --routines `
  --triggers `
  --no-create-db `
  --skip-comments `
  --result-file="<DUMP_FILE>" `
  <LOCAL_DB_NAME>
```

Если у локального пользователя есть пароль — добавить `-p`.

Использовать `--result-file`, а не `>`: PowerShell 5.1 может записать файл в UTF-16, и MySQL его не прочитает.

Если дамп делает MySQL 8-клиент:

- `Unknown table 'COLUMN_STATISTICS'` → добавить `--column-statistics=0`;
- `@@GLOBAL.GTID_PURGED` → добавить `--set-gtid-purged=OFF` или использовать MariaDB-клиент.

### B2. Проверить дамп

```powershell
Select-String -Path "<DUMP_FILE>" -Pattern "CREATE DATABASE|^USE |DEFINER=|utf8mb4_0900"
```

Убрать/заменить:

- `CREATE DATABASE` и `USE masterica_db` — удалить или заменить на `<DB_NAME>`;
- `DEFINER=...` — удалить (на Beget нет SUPER);
- `utf8mb4_0900_ai_ci` → `utf8mb4_unicode_ci`, если целевая MariaDB не поддерживает;
- BOM/UTF-16 — пересохранить как UTF-8 без BOM.

### B3. Создать пустую БД на Beget

Создать БД в панели MySQL. Имя и пользователь должны совпадать, например `<LOGIN>_masterica`.

### B4. Загрузить и импортировать

```bash
# локально
ssh <LOGIN>@<HOST> "mkdir -p ~/dumps"
scp <DUMP_FILE> <LOGIN>@<HOST>:~/dumps/

# на сервере
mysql -h localhost -u <DB_USER> -p <DB_NAME> < ~/dumps/<DUMP_FILE_NAME>
```

Для больших дампов:

```bash
mysql --max-allowed-packet=64M -h localhost -u <DB_USER> -p <DB_NAME> < ~/dumps/<DUMP_FILE_NAME>
```

Ожидаемо: команда завершилась без ошибок.

### B5. Проверить импорт

```bash
mysql -h localhost -u <DB_USER> -p <DB_NAME> -e "SHOW TABLES;"
mysql -h localhost -u <DB_USER> -p <DB_NAME> -e "SELECT COUNT(*) FROM users; SELECT COUNT(*) FROM migrations;"
```

Ожидаемо: таблицы и данные на месте, таблица `migrations` существует.

### B6. Только новые миграции

```bash
cd ~/<SITE_DIR>
php8.3 artisan migrate:status
php8.3 artisan migrate --force
```

Ожидаемо:

- если дамп соответствует коду — `Nothing to migrate`;
- если в коде есть новые миграции — применятся только они.

Если таблицы `migrations` нет, а таблицы уже созданы — **не запускать** `migrate --force` вслепую: он попытается создать существующие таблицы. Нужен полный дамп с `migrations` или осознанный baseline.

### B7. Не запускать сиды

На импортированной БД **не выполнять**:

```bash
# НЕ НУЖНО:
# php8.3 artisan db:seed --force
# php8.3 artisan migrate:fresh --seed
# php8.3 artisan migrate:refresh --seed
```

Почему:

- `SettingsSeeder` может перезаписать настройки;
- `CatalogSeeder` создаёт дубликаты;
- `migrate:fresh` уничтожит данные.

### B8. Почистить демо-данные и сессии

Если дамп делался из локальной БД, там могут быть аккаунты `@korni.local` с паролем `password`.

```bash
mysql -h localhost -u <DB_USER> -p <DB_NAME> -e "SELECT id,email,role FROM users;"
```

Сменить пароль владельцу, удалить/заблокировать демо-аккаунты. Через tinker:

```bash
php8.3 artisan tinker
```

```php
\App\Models\User::where('email', 'like', '%@korni.local')->get(['id','email','role']);
// \App\Models\User::where('email', 'like', '%@korni.local')->delete();
```

Очистить старые сессии/кеш:

```bash
mysql -h localhost -u <DB_USER> -p <DB_NAME> -e "TRUNCATE TABLE sessions; TRUNCATE TABLE cache; TRUNCATE TABLE cache_locks;"
```

### B9. Проверить локальные URL и медиа

Поиск локальных ссылок в дампе:

```powershell
Select-String -Path "<DUMP_FILE>" -Pattern "http://masterica","localhost","masterica_db"
```

Если в контенте/настройках есть `http://masterica`, заменить на `https://<DOMAIN>` точечно. Не делать слепой `REPLACE` по всей БД.

Загрузить медиа (архивом, чтобы сохранить пути):

```powershell
tar -czf media.tar.gz storage/app/public storage/app/private/reviews public/images public/assets/video
scp media.tar.gz <LOGIN>@<HOST>:~/
```

На сервере:

```bash
cd ~/<SITE_DIR>
tar -xzf ~/media.tar.gz
```

Проверить, что файлы из БД существуют:

```bash
ls storage/app/public
ls storage/app/private/reviews
ls public/images
```

---

## 8. Права, `storage:link` и медиа

### 8.1 Создать каталоги, если их нет

```bash
cd ~/<SITE_DIR>
mkdir -p \
  storage/app/public \
  storage/app/private/livewire-tmp \
  storage/app/private/reviews \
  storage/framework/cache/data \
  storage/framework/sessions \
  storage/framework/views \
  storage/logs \
  bootstrap/cache
```

### 8.2 Symlink публичных медиа

```bash
php8.3 artisan storage:link
```

Ожидаемо:

```
The [public/storage] link has been connected to [storage/app/public].
```

Проверка:

```bash
ls -ld public/storage
readlink -f public/storage
readlink -f storage/app/public
```

Если `public/storage` уже существует и это не symlink:

```bash
rm public/storage
php8.3 artisan storage:link
```

### 8.3 Права

```bash
cd ~/<SITE_DIR>

find . -type d -exec chmod 755 {} \;
find . -type f -exec chmod 644 {} \;

chmod -R 775 storage bootstrap/cache
chmod 600 .env
chmod +x artisan
```

Проверка:

```bash
ls -ld storage storage/logs bootstrap/cache public/storage
ls -l .env artisan
```

Ожидаемо: `storage`/`bootstrap/cache` writable, `.env` — 600, `artisan` — исполняемый. `777` не использовать.

---

## 9. Кеш и оптимизация

После загрузки кода, `.env`, БД и медиа:

```bash
cd ~/<SITE_DIR>
php8.3 artisan optimize:clear
php8.3 artisan optimize
php8.3 artisan filament:optimize
```

Ожидаемо:

- конфиг/маршруты/шаблоны/события закешированы;
- Filament-компоненты закешированы.

Если после изменения `.env` значения не применяются:

```bash
php8.3 artisan config:clear
php8.3 artisan config:cache
```

Если админка сломалась после оптимизации:

```bash
php8.3 artisan filament:optimize-clear
php8.3 artisan optimize:clear
```

---

## 10. Проверки после деплоя

```bash
curl -I https://<DOMAIN>/
curl -I https://<DOMAIN>/admin
curl -I https://<DOMAIN>/up
curl -I https://<DOMAIN>/css/site/base.css
curl -I https://<DOMAIN>/storage/<SAMPLE_MEDIA_PATH>
curl -I https://<DOMAIN>/.env
```

Ожидаемо:

- главная, админка, health, статика, медиа — 200/302;
- `.env` — 403/404.

Laravel/БД:

```bash
cd ~/<SITE_DIR>
php8.3 artisan about
php8.3 artisan migrate:status
tail -n 100 storage/logs/laravel.log
```

Проверить вручную:

- вход в `/admin`;
- загрузку изображения из админки;
- форму заявки и письмо;
- карту/геокодер;
- видеоотзыв (если есть);
- отсутствие 404 на `/storage/...`.

---

## 11. Быстрый повторный деплой

Если БД не меняется:

```bash
ssh <LOGIN>@<HOST>
cd ~/<SITE_DIR>

# загрузить новую версию: git pull / tar -xzf / scp

export COMPOSER_MEMORY_LIMIT=-1
php8.3 ~/.local/bin/composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
php8.3 artisan filament:upgrade
php8.3 artisan optimize:clear
php8.3 artisan optimize
php8.3 artisan filament:optimize
```

Если меняется БД:

```bash
# сначала бэкап
mysqldump -h localhost -u <DB_USER> -p \
  --default-character-set=utf8mb4 \
  --single-transaction --quick --routines --triggers \
  --no-create-db --skip-comments \
  <DB_NAME> > ~/backups/<DB_NAME>-before-$(date +%F-%H%M).sql

php8.3 artisan migrate --force
php8.3 artisan optimize:clear
php8.3 artisan optimize
```

Если импортируется готовая БД — раздел 7B, **без сидов**.

---

## 12. Частые ошибки

| Ошибка | Причина | Решение |
|---|---|---|
| 500 на всех страницах | Нет `APP_KEY`, `vendor`, прав | `key:generate`, `composer install`, права. |
| `No application encryption key` | Пустой `APP_KEY` | `php8.3 artisan key:generate --force`. |
| `Class not found` | Нет autoload/OPcache | `composer install`, `dump-autoload -o`, сбросить OPcache. |
| `could not find driver` | Нет `pdo_mysql` | Включить расширение PHP. |
| 403 на главной | Неверный public root/права | Проверить `public_html` symlink/docroot. |
| 404 на CSS/картинки | Webroot не `public` | Исправить symlink/docroot. |
| 404 на `/storage/...` | Нет `public/storage` | `php8.3 artisan storage:link`. |
| Админка без стилей | Нет ассетов Filament | `filament:upgrade`/`filament:assets`. |
| 419 Page Expired | Сессии/APP_KEY/cookie | Проверить `sessions`, `APP_KEY`, HTTPS, `SESSION_SECURE_COOKIE`. |
| `Access denied` БД | Неверные `DB_*` | `<DB_USER>` = `<DB_NAME>`, проверить пароль. |
| `Unknown database` | Опечатка/БД не создана | Создать БД, проверить имя. |
| `Base table ... not found` | Нет миграций | `php8.3 artisan migrate --force`. |
| `Table already exists` при импорте | БД не пуста | Очистить/пересоздать БД. |
| `Unknown collation utf8mb4_0900_ai_ci` | MySQL 8 → MariaDB | Заменить collation в дампе. |
| `DEFINER`/`SUPER privilege` | Views/routines | Удалить `DEFINER=...`. |
| `MySQL server has gone away` | Большой дамп | `--max-allowed-packet`, SSH, импорт по частям. |
| Composer требует PHP >= 8.3 | Запущен не тот PHP | `php8.3 ~/.local/bin/composer ...`. |
| Composer 1 | Системный composer старый | Установить Composer 2 локально. |
| `proc_open` disabled | Запрещены процессы | `--no-scripts`, затем вручную `package:discover`/`filament:upgrade`. |
| Сайт в 503 | Maintenance mode | `php8.3 artisan up`. |
| `.env` доступен по URL | Проект загружен в `public_html` | Немедленно исправить public root, сменить секреты. |
