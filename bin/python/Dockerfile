FROM python:3.10-slim

WORKDIR /data/python

RUN pip install pandas numpy matplotlib plotly requests

COPY ./requirements.txt ./requirements.txt
RUN pip install --no-cache-dir -r requirements.txt

# COPY scripts/ ./scripts/