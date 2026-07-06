FROM php:8.2-cli
RUN docker-php-ext-install pdo pdo_mysql
WORKDIR /app
COPY index.php .
EXPOSE 80
CMD ["php", "-S", "0.0.0.0:80", "index.php"]
