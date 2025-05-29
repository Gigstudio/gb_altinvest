import os
import json
from php_bridge import call_php_api

OUTPUT_DIR = "/data/vacancies"

def main():
    os.makedirs(OUTPUT_DIR, exist_ok=True)
    # Запрашиваем только тикеры (dict)
    tickers = call_php_api("config_api", "get", {"key": "tickers"})
    # print("TICKERS STRUCTURE:", tickers)

    for symbol in tickers.keys():
        area = "40"  # Казахстан, либо бери из config, если будет нужно
        try:
            result = call_php_api("headhunter", "getVacancies", {
                "symbol": symbol,
                "area": area
            })
            # Сохраняем весь результат (можно ['vacancies'], если только список нужен)
            filename = os.path.join(OUTPUT_DIR, f"{symbol}.json")
            with open(filename, "w", encoding="utf-8") as f:
                json.dump(result, f, ensure_ascii=False, indent=2)
            print(f"Saved: {filename}")
        except Exception as e:
            print(f"Error for {symbol}: {e}")

if __name__ == "__main__":
    main()
