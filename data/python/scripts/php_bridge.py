import requests

API_URL = "http://web/API/index.php"

def call_php_api(module, action, data=None):
    """
    Универсальный вызов PHP-API.
    :param module: Название модуля ('config_api', 'tradernet', 'headhunter', 'news')
    :param action: Действие ('get', 'getQuotes', ...)
    :param data: dict — параметры для действия (например, {'key': 'tickers'})
    :return: dict/array
    """
    payload = {
        "module": module,
        "action": action,
        "data": data or {}
    }
    r = requests.post(API_URL, json=payload, timeout=30)
    r.raise_for_status()
    resp = r.json()
    if resp.get("status") == "success":
        return resp.get("result", resp)
    else:
        raise Exception(f"Ошибка API: {resp}")
