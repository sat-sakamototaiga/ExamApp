FROM php:8.2-fpm-alpine

# Node.js と npm をインストール
RUN apk add --update nodejs npm

# 必要なPHP拡張機能をインストール
RUN docker-php-ext-install pdo_mysql opcache 
WORKDIR /var/www/html 

# Composerをインストール
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer