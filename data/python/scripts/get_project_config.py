import requests

API_URL = "http://localhost:8181/API/index.php"

def get_config(key=None):
    payload = {
        "module": "config_api",
    }
    if key:
        payload["key"] = key
    r = requests.post(API_URL, json=payload)
    r.raise_for_status()
    resp = r.json()
    if resp.get("status") == "success":
        return resp["result"]
    else:
        raise Exception(f"Ошибка API: {resp}")

# Получить все тикеры
tickers = get_config('tickers')
print("Тикеры:", tickers)

# Получить всю конфигурацию
# full_conf = get_config()
