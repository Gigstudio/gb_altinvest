from airflow import DAG
from airflow.operators.bash import BashOperator
from datetime import datetime, timedelta

default_args = {
    'owner': 'simajor',
    'retries': 1,
    'retry_delay': timedelta(minutes=5),
}

with DAG(
    dag_id='export_vacancies',
    default_args=default_args,
    description='Экспорт вакансий HH в JSON для Python-аналитики',
    schedule_interval='15 20 * * *',  # Каждый день в 20:15
    start_date=datetime(2025, 5, 21),
    catchup=False,
) as dag:

    export_vacancies_task = BashOperator(
        task_id='run_get_all_vacancies',
        bash_command='python3 /data/python/scripts/get_all_vacancies.py',
        cwd='/data/python/scripts'
    )
