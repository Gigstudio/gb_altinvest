import os
import json
from php_bridge import get_config, get_vacancies

OUTPUT_DIR = "/data/vacancies"

def main():
    os.makedirs(OUTPUT_DIR, exist_ok=True)
    tickers = get_config('tickers')

    for symbol, info in tickers.items():
        try:
            industry_id = info.get('industry_id')
            if not industry_id:
                print(f"Нет industry_id для {symbol}, пропущено.")
                continue
            # Можно добавить выбор area по нужной логике, сейчас '40' — Казахстан
            vacancies = get_vacancies(industry_id, area='40')
            filename = os.path.join(OUTPUT_DIR, f"{symbol}.json")
            with open(filename, "w", encoding="utf-8") as f:
                json.dump(vacancies, f, ensure_ascii=False, indent=2)
            print(f"Saved: {filename}")
        except Exception as e:
            print(f"Error for {symbol}: {e}")

if __name__ == "__main__":
    main()
