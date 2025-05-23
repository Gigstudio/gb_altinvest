import os
import json
import pandas as pd
import matplotlib.pyplot as plt

VACANCY_DIR = '/data/python/vacancies'
RESULTS_DIR = '/results/vacancies'

os.makedirs(RESULTS_DIR, exist_ok=True)

for filename in os.listdir(VACANCY_DIR):
    if filename.endswith('.json'):
        symbol = filename.replace('.json', '')
        filepath = os.path.join(VACANCY_DIR, filename)
        with open(filepath, 'r', encoding='utf-8') as f:
            data = json.load(f)

        # Преобразуем в DataFrame
        df = pd.DataFrame(data)
        if df.empty:
            print(f"No data for {symbol}")
            continue

        # Пример: топ-10 городов
        top_cities = df['city'].value_counts().head(10)
        plt.figure(figsize=(10,4))
        top_cities.plot(kind='bar')
        plt.title(f"Top 10 cities by vacancies — {symbol}")
        plt.ylabel('Количество вакансий')
        plt.tight_layout()
        plt.savefig(os.path.join(RESULTS_DIR, f"{symbol}_top_cities.png"))
        plt.close()
        print(f"Saved chart for {symbol}")

        # Аналогично — топ работодателей, распределение по зарплате и дате публикации
        # ...

print("Done.")
