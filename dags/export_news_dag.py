from airflow import DAG
from airflow.operators.bash import BashOperator
from datetime import datetime, timedelta

default_args = {
    'owner': 'simajor',
    'retries': 1,
    'retry_delay': timedelta(minutes=5),
}

with DAG(
    dag_id='export_news_to_gdrive',
    default_args=default_args,
    description='Экспорт новостей в JSON для аналитики',
    schedule_interval='0 21 * * *',  # Каждый день в 21:00 (пример)
    start_date=datetime(2025, 5, 21),
    catchup=False,
) as dag:

    run_get_all_news = BashOperator(
        task_id='run_get_all_news',
        bash_command='python3 /data/python/scripts/get_all_news.py',
        cwd='/data/python/scripts'
    )

    upload_to_dropbox = BashOperator(
        task_id='upload_news_to_gdrive',
        bash_command='rclone --config=/home/airflow/.config/rclone/rclone.conf copy /data/news dropbox:AltInvest/Data/news --create-empty-src-dirs'
    )

    run_get_all_news >> upload_to_dropbox
