from airflow import DAG
from airflow.operators.bash import BashOperator
from datetime import datetime, timedelta

default_args = {
    'owner': 'simajor',
    'retries': 1,
    'retry_delay': timedelta(minutes=5),
}

with DAG(
    dag_id='export_vacancies_to_gdrive',
    default_args=default_args,
    description='Экспорт вакансий и загрузка в Google Drive',
    schedule_interval='15 20 * * *',
    start_date=datetime(2025, 5, 27),
    catchup=False,
) as dag:

    export_vacancies = BashOperator(
        task_id='run_get_all_vacancies',
        bash_command='python3 /data/python/scripts/get_all_vacancies.py',
        cwd='/data/python/scripts'
    )

    upload_to_dropbox = BashOperator(
        task_id='upload_vacancies_to_dropbox',
        bash_command=(
            "rclone --config=/home/airflow/.config/rclone/rclone.conf "
            "copy /data/vacancies dropbox:AltInvest/Data/vacancies --create-empty-src-dirs"
        )
    )

    export_vacancies >> upload_to_dropbox
