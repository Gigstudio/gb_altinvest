import os
import json
from php_bridge import call_php_api

OUTPUT_DIR = "/data/news"

def main():
    os.makedirs(OUTPUT_DIR, exist_ok=True)
    tickers = call_php_api("config_api", "get", {"key": "tickers"})
    print("TICKERS STRUCTURE:", tickers)

    for symbol in tickers.keys():
        try:
            # Параметры для получения новостей для каждого символа
            news = call_php_api("tradernet", "getNews", {"symbol": symbol})
            filename = os.path.join(OUTPUT_DIR, f"{symbol}.json")
            with open(filename, "w", encoding="utf-8") as f:
                json.dump(news, f, ensure_ascii=False, indent=2)
            print(f"Saved: {filename}")
        except Exception as e:
            print(f"Error for {symbol}: {e}")

if __name__ == "__main__":
    main()
