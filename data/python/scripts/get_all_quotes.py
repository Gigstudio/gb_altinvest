import os
import json
from datetime import datetime, timedelta
from php_bridge import get_config, get_quotes

OUTPUT_DIR = "/data/quotes"
DAYS_BACK = 730

date_to = datetime.now()
date_from = date_to - timedelta(days=DAYS_BACK)
date_to_str = date_to.strftime("%d.%m.%Y %H:%M")
date_from_str = date_from.strftime("%d.%m.%Y %H:%M")

def main():
    os.makedirs(OUTPUT_DIR, exist_ok=True)
    tickers = get_config('tickers')

    for symbol in tickers.keys():
        try:
            quotes = get_quotes(symbol, date_from_str, date_to_str)
            filename = os.path.join(OUTPUT_DIR, f"{symbol}.json")
            with open(filename, "w", encoding="utf-8") as f:
                json.dump(quotes, f, ensure_ascii=False, indent=2)
            print(f"Saved: {filename}")
        except Exception as e:
            print(f"Error for {symbol}: {e}")

if __name__ == "__main__":
    main()
