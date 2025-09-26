FROM php:8.2-fpm-alpine

# 必要なPHP拡張機能をインストール
RUN docker-php-ext-install pdo_mysql opcache \
    apt-get update && \
    apt-get install -y nodejs npm
    
WORKDIR /var/www/html 

# Composerをインストール
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer