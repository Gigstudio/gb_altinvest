from airflow import DAG
from airflow.operators.bash import BashOperator
from datetime import datetime, timedelta

default_args = {
    'owner': 'simajor',
    'retries': 1,
    'retry_delay': timedelta(minutes=5),
}

with DAG(
    dag_id='export_quotes',
    default_args=default_args,
    description='Экспорт котировок в JSON для Python-аналитики',
    schedule_interval='0 20 * * *',  # Каждый день в 20:00 (пример)
    start_date=datetime(2025, 5, 21),
    catchup=False,
) as dag:

    export_task = BashOperator(
        task_id='run_get_all_quotes',
        bash_command='python3 /data/python/scripts/get_all_quotes.py',
        cwd='/data/python/scripts'
    )
