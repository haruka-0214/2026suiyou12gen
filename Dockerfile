FROM php:8.4-fpm-alpine AS php // 実行環境

RUN docker-php-ext-install pdo_mysql // MySQL

RUN install -o www-data -g www-data -d /var/www/upload/image/ //画像保存

RUN echo "upload_max_filesize=5M" > /usr/local/etc/php/conf.d/uploads.ini　//画像の最大アップロードサイズ5MB
RUN echo "post_max_size=5M" >> /usr/local/etc/php/conf.d/uploads.ini //POSTリクエスト全体の最大サイズを5MBに設定
