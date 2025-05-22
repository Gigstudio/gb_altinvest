# gb\_altinvest — Дипломный проект «Специалист по ИИ»

## Описание проекта

Автоматизированный сервис для анализа взаимосвязи альтернативных данных (вакансии, новости и др.) с изменением котировок акций ведущих казахстанских компаний. Проект реализован на Python, PHP, Docker, с использованием ETL и элементами web-интерфейса.

---

## Структура проекта

- `.env`, `.env.example` — переменные окружения для Docker Compose и сервисов (реальные значения в .env, шаблон — .env.example)
- `.gitignore` — список игнорируемых файлов и папок для git
- `bin/` — Dockerfile и зависимости для сервисов:
  - `mysql8/`, `php83/`, `python/`, `telegram-bot/`
- `config/` — служебные настройки, виртуальные хосты Apache, php.ini, initdb
- `dags/` — скрипты DAG для Airflow
- `data/` — рабочие и обменные папки для MySQL, Python-аналитики, Telegram-бота
- `docker-compose.yml` — основной файл описания инфраструктуры
- `history.md` — журнал этапов и решений
- `html/` — корневая веб-папка проекта:
  - `API/` — контроллеры и хендлеры для api-запросов
  - `app/` — структура MVC ядра (Core, Domain, Infrastructure, Presentation)
  - `bootstrap.php` — стартовый загрузчик
  - `config/` — настройки БД, роутинг, меню
  - `Nt/` — клиенты внешних API
  - `robots.txt` — инструкции для поисковиков
  - `siteroot/` — публичные ассеты (css, js, images, webfonts), index.php, .htaccess
  - `storage/` — логи событий
- `logs/` — логи MySQL и Apache2
- `README.md` — документация по проекту
- `temp/` — временные или промежуточные файлы

Все ключевые рабочие директории содержат служебные файлы `.gitkeep` для поддержки структуры в git-репозитории.

---

## Docker-инфраструктура

Проект развёрнут с использованием Docker Compose и состоит из следующих сервисов:

* **web**: PHP/Apache, backend и frontend (папка html/)
* **db**: MySQL, хранение данных и логов
* **phpmyadmin**: Веб-интерфейс для администрирования MySQL
* **python-analysis**: Автоматизация сбора и анализа данных (Python, интеграция с веб-сервисом для получения данных с Tradernet)
* **airflow**: (опционально) Планировщик задач, автоматизация ETL (скрипты — в папке dags/)
* **telegram-bot**: (опционально) Отправка аналитики через Telegram

### Запуск и остановка сервисов

* Сборка и запуск всех сервисов:

  ```bash
  docker-compose up --build
  ```
* Остановка всех сервисов:

  ```bash
  docker-compose down
  ```
* Просмотр логов:

  ```bash
  docker-compose logs -f
  ```

---

## Переменные окружения (.env)

Пример структуры файла `.env`:

```
COMPOSE_PROJECT_NAME=gb_
PHPVERSION=php83
DATABASE=mysql8
DOCUMENT_ROOT=./html
APACHE_DOCUMENT_ROOT=/var/www/html
VHOSTS_DIR=./config/vhosts
APACHE_LOG_DIR=./logs/apache2
PHP_INI=./config/php/php.ini
MYSQL_INITDB_DIR=./config/initdb
MYSQL_DATA_DIR=./data/mysql
MYSQL_LOG_DIR=./logs/mysql
HOST_MACHINE_UNSECURE_HOST_PORT=8181
HOST_MACHINE_MYSQL_PORT=3308
HOST_MACHINE_PMA_PORT=8686
MYSQL_ROOT_PASSWORD=your_root_password
MYSQL_USER=admin
MYSQL_PASSWORD=p0skudA
MYSQL_DATABASE=acms
MYSQL_DB_DUMP=./data/dump/init.sql
# TRADERNET_PUBLIC_KEY=your_public_key
# TRADERNET_SECRET_KEY=your_secret_key
```

Все необходимые папки для volumes должны быть созданы заранее и содержать служебный файл `.gitkeep` (или аналогичный), чтобы структура репозитория сохранялась при клонировании.

---

## Основные настройки сервисов

### PHP и Apache

* memory\_limit = 256M
* post\_max\_size = 100M
* upload\_max\_filesize = 100M
* date.timezone = "Asia/Almaty"
* display\_errors = On (для разработки)
* log\_errors = On
* error\_reporting = E\_ALL

### .htaccess (html/ и siteroot/)

* Отключён листинг директорий
* Защищён доступ к служебным папкам и критическим файлам
* Все неявные запросы маршрутизируются через index.php (Front Controller)
* Перенаправление статики работает только для реально существующих файлов

### Airflow

* DAG-файлы размещаются в папке `dags/` (служебный файл `.gitkeep` для поддержки структуры репозитория).

---

## Работа с python-analysis

* Данные, получаемые и анализируемые python-сервисом, хранятся в папке `data/python/`.
* Скрипты и зависимости располагаются в папке `bin/python/scripts/` и в файле `bin/python/requirements.txt`.
* Интеграция с Tradernet API реализована через внутренний PHP-веб-сервис.

---

## Telegram-бот

* Контейнер telegram-bot настроен как заглушка, команда запуска временно закомментирована.
* В будущем запуск бота осуществляется после размещения и настройки скрипта `bot.py` и соответствующего Dockerfile.

---

## Логирование и хранение данных

* Все логи работы сервисов пишутся в папку `logs/`.
* Для каждой рабочей директории (data, logs, dags и т.д.) используется служебный файл `.gitkeep` для поддержки структуры в репозитории.

---

## Конфигурация веб-сервиса (config/init.json)

Для работы приложения требуется конфигурационный файл `config/init.json` с основными настройками проекта, параметрами базы данных, интеграции с внешними сервисами и правилами безопасности.

**В репозитории хранится только файл-шаблон `init.json.example`.**  
Реальный файл `init.json` должен быть создан на его основе и НЕ добавляется в git (указан в `.gitignore`).

**Ключевые разделы:**
- `app`: название, автор, версия, email для связи, кодировка, часовой пояс, иконки
- `debug_error`: режим вывода ошибок (debug/deploy)
- `events`: шкала типов событий для логирования (info, message, warning, error, fatal)
- `common`: списки CSS/JS для фронтенда по умолчанию
- `database`: параметры подключения к базе данных (имя хоста, порт, пользователь, пароль, имя БД, версия схемы)
- `tradernet`: ключи для интеграции с Tradernet API
- `security`: шаблоны и параметры безопасности (паттерны для паролей, email, телефонов, настройка шифрования, таймауты, количество попыток входа и т.д.)

**Путь:**  
`config/init.json`  
Файл используется для чтения настроек приложением и при инициализации сервисов.  
Изменения в этом файле требуют перезапуска сервиса или фронтенда.

### Шаги для запуска:

1. Скопируйте файл-шаблон:
   ```bash
   cp config/init.json.example config/init.json
2. Заполните все необходимые параметры (ключи, пароли, email и т.д.) в config/init.json перед первым запуском сервиса.

---

## Быстрый старт: переменные окружения

Перед запуском проекта скопируйте `.env.example` в `.env`, `config/init.json.example` в `config/init.json` и укажите свои значения переменных:

```bash
cp .env.example .env
cp config/init.json.example config/init.json

---

## Лицензия

(Указать тип лицензии или оставить раздел пустым до финальной версии.)

---

## История изменений и ключевые этапы

См. файл `history.md` в корне репозитория.

