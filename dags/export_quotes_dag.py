from airflow import DAG
from airflow.operators.bash import BashOperator
from datetime import datetime, timedelta

default_args = {
    'owner': 'simajor',
    'retries': 1,
    'retry_delay': timedelta(minutes=5),
}

with DAG(
    dag_id='export_quotes_to_gdrive',
    default_args=default_args,
    description='Экспорт котировок и загрузка в Google Drive',
    schedule_interval='0 20 * * *',
    start_date=datetime(2025, 5, 27),
    catchup=False,
) as dag:

    export_quotes = BashOperator(
        task_id='run_get_all_quotes',
        bash_command='python3 /data/python/scripts/get_all_quotes.py',
        cwd='/data/python/scripts'
    )

    upload_to_dropbox = BashOperator(
        task_id='upload_quotes_to_dropbox',
        bash_command=(
            "rclone --config=/home/airflow/.config/rclone/rclone.conf "
            "copy /data/quotes dropbox:AltInvest/Data/quotes --create-empty-src-dirs"
        )
    )

    export_quotes >> upload_to_dropbox
