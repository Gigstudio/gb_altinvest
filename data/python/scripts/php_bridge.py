import requests

API_URL = "http://web/API/index.php"

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
    r = requests.post(API_URL, json=payload)
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
    r = requests.post(API_URL, json=payload)
    r.raise_for_status()
    resp = r.json()
    if resp.get("status") == "success" and resp.get("result", {}).get("quotes"):
        return resp["result"]["quotes"]
    else:
        raise Exception(f"Ошибка API: {resp}")

def get_vacancies(industry_id, area="40"):
    """
    Делает REST-запрос к PHP API (headhunter handler) для выгрузки вакансий по тикеру.
    :param symbol: Тикер (например, 'KCEL')
    :param area: Код региона (по умолчанию - '40') 
    //для дальнейшего масштабирования нужно реализовать выбор региона из справочника и получение справочника от API https://api.hh.ru/areas/countries
    :return: Массив вакансий (overall) и данные поверхностной аналитики со стороны PHP
    """
    payload = {
        "module": "headhunter",
        "action": "getVacancies",
        "data": {
            "industry_id": industry_id,
            "area": area
        }
    }
    r = requests.post(API_URL, json=payload)
    r.raise_for_status()
    resp = r.json()
    if resp.get("status") == "success" and resp.get("result"):
        return resp["result"]
    else:
        raise Exception(f"Ошибка API: {resp}")
