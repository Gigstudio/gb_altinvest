# AltInSight

**Автоматизированный сервис для анализа взаимосвязи альтернативных данных (вакансии, уровень зарплат и др.)  
с изменением котировок акций ведущих казахстанских компаний**

> Проект реализован в рамках дипломной работы по направлению “ИИ. Специалист”, 2025

---

## Описание проекта

**AltInSight** — контейнеризированная система для автоматического сбора, хранения, анализа и визуализации исторических котировок и альтернативных данных (вакансии, зарплаты и пр.) для компаний казахстанского фондового рынка (KASE, AIX).

**Цель:**  
Доказать или опровергнуть наличие корреляций между изменениями альтернативных данных (рынок труда, уровень зарплат, деловые события) и изменениями биржевых котировок акций ведущих компаний Казахстана.

**Основные задачи:**
- Автоматизация сбора исторических котировок по выбранным тикерам.
- Интеграция с HeadHunter API для получения вакансий и зарплат по отраслям.
- (Опционально) Интеграция с агрегаторами новостей и событий.
- Формирование унифицированного набора данных для дальнейшей аналитики.
- Визуализация результатов:  
    - На сайте (основной интерфейс, REST API для данных)
    - В аналитическом Google Colab-ноутбуке (глубокий анализ, графики, выводы)

---

## Стек технологий

- **Python 3.8+**  
    - pandas, numpy, plotly, seaborn, scikit-learn (и др. для аналитики)
- **PHP 8**  
    - Веб-сервер, контроллеры, API-клиенты (Tradernet, HeadHunter и др.)
- **Docker / Docker Compose**  
    - Изоляция сервисов, запуск всех контейнеров одной командой
- **Apache Airflow**  
    - Автоматизация ETL, расписание сбора и экспорта данных
- **Базы данных**  
    - MySQL (при необходимости хранения промежуточных данных)
- **Облачное хранилище**  
    - Интеграция с Dropbox через rclone для обмена данными с Google Colab
- **Внешние API**  
    - Tradernet (котировки, новости)
    - HeadHunter (вакансии, зарплаты)

---

## Структура проекта

- **/html/** — Исходный код PHP-приложения (MVC-структура, API, контроллеры, страницы)
- **/dags/** — DAG-файлы для Airflow (описание ETL-задач)
- **/data/quotes/** — Хранилище исторических котировок (JSON по каждому тикеру)
- **/data/vacancies/** — Хранилище данных по вакансиям и зарплатам (JSON)
- **/data/news/** — (зарезервировано) Для новостных данных (JSON)
- **/python/scripts/** — Python-скрипты для интеграции и анализа
- **/config/** — Конфигурационные файлы приложения (init.json и др.)
- **/bin/** — Dockerfile, служебные скрипты для билда контейнеров
- **gb_altinvest.txt** — Служебный файл со списком/описанием всех компонентов проекта

---

## Архитектура и схема работы

1. **ETL-процесс (Airflow):**
    - Автоматически, по расписанию, запускаются задачи экспорта котировок и вакансий через API (Tradernet, HeadHunter).
    - Данные сохраняются в `/data/quotes/`, `/data/vacancies/` в формате JSON (один файл — одна компания).

2. **Веб-интерфейс и API:**
    - Позволяет просматривать данные, делать выборку по компаниям, отраслям, проводить базовую аналитику и визуализацию.
    - REST API для получения данных в машиночитаемом виде.

3. **Передача данных в облако:**
    - С помощью rclone данные автоматически копируются в Dropbox (или другое облачное хранилище), откуда могут быть подгружены для аналитики в Google Colab.

4. **Аналитическая часть (Google Colab):**
    - Подробный анализ собранных данных, исследование корреляций, построение графиков и выводов.
    - [Ссылка на аналитический ноутбук](https://colab.research.google.com/drive/1fEvF03MjEi7t1J6GoHxfn_kPdtQmMhgN?usp=sharing)

---

## Быстрый старт (инструкция)

1. **Склонируйте репозиторий:**
   ```bash
   git clone https://github.com/yourusername/gb_altinvest.git
   cd gb_altinvest
   ```
2. **Скопируйте пример конфигурации и отредактируйте настройки:**

   ```bash
   cp config/init.json.example config/init.json
   # (Внесите ключи API, токены, параметры подключения к базам)
   ```
3. **Запустите все сервисы через Docker Compose:**

   ```bash
   docker-compose up --build
   ```
4. **Проверьте доступность сервиса:**
   - Веб-интерфейс (по умолчанию): http://localhost:8181/
   - Airflow: http://localhost:8081/
   - Все данные (quotes, vacancies) будут сохраняться в /data/, доступно из всех контейнеров.

5. **Для аналитики — перейдите в Google Colab и подключите Dropbox (или скачайте данные вручную)::**
   - [Ссылка на аналитический ноутбук](https://colab.research.google.com/drive/1fEvF03MjEi7t1J6GoHxfn_kPdtQmMhgN?usp=sharing)

---

## Пример получения котировок и вакансий через REST API
**Получить котировки компании:**

   ```bash
    POST /API/index.php
    {
      "module": "tradernet",
      "action": "getQuotes",
      "data": {
          "symbol": "KCEL.KZ",
          "date_from": "01.01.2023 00:00",
          "date_to": "01.06.2025 00:00"
      }
    }
   ```
**Получить вакансии по отрасли:**

   ```bash
    POST /API/index.php
    {
      "module": "headhunter",
      "action": "getVacancies",
      "data": {
          "industry_id": "9",
          "area": "40"
      }
    }
   ```
- Данные возвращаются в формате JSON.

## Ссылки
   - [Google Colab: Аналитическая часть диплома](https://colab.research.google.com/drive/1fEvF03MjEi7t1J6GoHxfn_kPdtQmMhgN?usp=sharing)

>Проект AltInSight разработан в рамках дипломной работы по направлению "ИИ. Специалист", 2025. Все материалы используются исключительно в учебных и исследовательских целях.
