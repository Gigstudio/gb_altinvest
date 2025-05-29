FROM apache/airflow:2.7.0

USER root

RUN apt-get update && \
    apt-get install -y curl unzip && \
    curl https://rclone.org/install.sh | bash

USER airflow