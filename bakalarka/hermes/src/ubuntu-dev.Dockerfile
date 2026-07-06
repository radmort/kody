# ./ubuntu-dev.Dockerfile
FROM ubuntu:22.04
ENV DEBIAN_FRONTEND=noninteractive

# Build tools + git + MariaDB klient + hlavičky (mysql/mysql.h cez compat)
RUN apt-get update \
 && apt-get install -y --no-install-recommends \
      build-essential git ca-certificates pkg-config cmake \
      gdb curl wget vim \
      mariadb-client libmariadb-dev libmariadb-dev-compat \
 && rm -rf /var/lib/apt/lists/*

# (Optional) non-root user
RUN useradd -m -u 1000 dev
USER dev
WORKDIR /work
