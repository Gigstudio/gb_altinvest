import requests
import os
import json
from datetime import datetime, timedelta

API_URL = "http://web/API/index.php"  # URL до PHP API
OUTPUT_DIR = "/data/python/quotes"               # Каталог для сохранения выгруженных файлов

# Период выгрузки — последние 730 дней (2 года)
DAYS_BACK = 730

date_to = datetime.now()
date_from = date_to - timedelta(days=DAYS_BACK)

# Переводим в формат dd.mm.yyyy HH:MM
date_to_str = date_to.strftime("%d.%m.%Y %H:%M")
date_from_str = date_from.strftime("%d.%m.%Y %H:%M")

def get_config(key=None):
    """
    Делает REST-запрос к PHP API (config handler) для получения настроек проекта.
    :param key: Если указан, возвращается только этот параметр (например, 'tickers'), иначе — весь конфиг.
    :return: Значение параметра или полный конфиг (dict)
    """
    payload = {
        "module": "config",
        "action": "get"
    }
    if key:
        payload["key"] = key
    r = requests.post(API_URL, json=payload, timeout=15)
    r.raise_for_status()
    resp = r.json()
    if resp.get("status") == "success":
        return resp["result"]
    else:
        raise Exception(f"Ошибка API: {resp}")

def get_quotes(symbol, date_from, date_to):
    """
    Делает REST-запрос к PHP API (tradernet handler) для выгрузки котировок по тикеру.
    :param symbol: Тикер (например, 'KCEL')
    :param date_from: Начальная дата периода (строка)
    :param date_to: Конечная дата периода (строка)
    :return: Массив котировок (list of dicts)
    """
    payload = {
        "module": "tradernet",
        "action": "getQuotes",
        "data": {
            "symbol": symbol,
            "date_from": date_from,
            "date_to": date_to
        }
    }
    r = requests.post(API_URL, json=payload, timeout=30)
    print(f"Ответ от сервера для {symbol}:\n{r.text}")
    r.raise_for_status()
    resp = r.json()
    if resp.get("status") == "success" and resp.get("result", {}).get("quotes"):
        return resp["result"]["quotes"]
    else:
        raise Exception(f"Ошибка API для {symbol}: {resp.get('message') or resp}")

def main():
    os.makedirs(OUTPUT_DIR, exist_ok=True)   # Гарантируем, что папка для выгрузки существует
    tickers = get_config('tickers')          # Получаем список тикеров из PHP-конфига
    print(tickers)

    for symbol in tickers.keys():            # Перебираем тикеры (KCEL, HSBK, KAP, ...)
        try:
            quotes = get_quotes(symbol, date_from_str, date_to_str)
            filename = os.path.join(OUTPUT_DIR, f"{symbol}.json")
            with open(filename, "w", encoding="utf-8") as f:
                json.dump(quotes, f, ensure_ascii=False, indent=2)
            print(f"Saved: {filename}")      # Выводим, что файл сохранён успешно
        except Exception as e:
            print(f"Error for {symbol}: {e}")  # Если ошибка — пишем в консоль

if __name__ == "__main__":
    main()
