import os
import json
from datetime import datetime, timedelta
from php_bridge import call_php_api

OUTPUT_DIR = "/data/quotes"

def main():
    os.makedirs(OUTPUT_DIR, exist_ok=True)
    # Новый вызов: ключ 'key' теперь в data!
    tickers = call_php_api("config_api", "get", {"key": "tickers"})
    # print("TICKERS STRUCTURE:", tickers)

    # Даты выгрузки (пример: последние 2 года)
    date_to = datetime.now()
    date_from = date_to - timedelta(days=730)
    date_to_str = date_to.strftime("%d.%m.%Y %H:%M")
    date_from_str = date_from.strftime("%d.%m.%Y %H:%M")

    # Перебираем только тикеры — это dict
    for symbol in tickers.keys():
        try:
            # Все параметры внутри одного dict
            quotes = call_php_api("tradernet", "getQuotes", {
                "symbol": symbol,
                "date_from": date_from_str,
                "date_to": date_to_str
            })
            filename = os.path.join(OUTPUT_DIR, f"{symbol}.json")
            with open(filename, "w", encoding="utf-8") as f:
                json.dump(quotes, f, ensure_ascii=False, indent=2)
            print(f"Saved: {filename}")
        except Exception as e:
            print(f"Error for {symbol}: {e}")

if __name__ == "__main__":
    main()
