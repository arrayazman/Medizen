FROM jodogne/orthanc-plugins:latest

USER root

RUN apt-get update && \
    apt-get install -y dcmtk && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/*

USER orthanc