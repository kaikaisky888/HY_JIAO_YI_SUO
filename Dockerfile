FROM php:7.4-fpm-alpine

# 安装系统依赖
RUN apk add --no-cache \
    nginx \
    supervisor \
    ca-certificates \
    freetype-dev \
    libjpeg-turbo-dev \
    libpng-dev \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    curl-dev \
    libxml2-dev \
    openssl-dev \
    linux-headers

# 安装 PHP 扩展
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        mysqli \
        gd \
        zip \
        bcmath \
        pcntl \
        posix \
        sockets \
        intl \
        mbstring \
        curl \
        xml \
        fileinfo \
        opcache

# 安装 Redis 扩展
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS libevent-dev openssl-dev \
    && pecl install redis-5.3.7 \
    && pecl install event-3.0.8 \
    && docker-php-ext-enable redis event \
    && mv /usr/local/etc/php/conf.d/docker-php-ext-event.ini /usr/local/etc/php/conf.d/docker-php-ext-zz-event.ini \
    && apk del .build-deps \
    && apk add --no-cache libevent

# 配置 PHP
RUN cp /usr/local/etc/php/php.ini-production /usr/local/etc/php/php.ini
COPY docker/php.ini /usr/local/etc/php/conf.d/custom.ini

# 让 PHP-FPM 保留环境变量 + 增加进程数
RUN sed -i 's/;clear_env = no/clear_env = no/' /usr/local/etc/php-fpm.d/www.conf \
    && echo 'clear_env = no' >> /usr/local/etc/php-fpm.d/www.conf \
    && sed -i 's/pm.max_children = 5/pm.max_children = 20/' /usr/local/etc/php-fpm.d/www.conf \
    && sed -i 's/pm.start_servers = 2/pm.start_servers = 4/' /usr/local/etc/php-fpm.d/www.conf \
    && sed -i 's/pm.min_spare_servers = 1/pm.min_spare_servers = 2/' /usr/local/etc/php-fpm.d/www.conf \
    && sed -i 's/pm.max_spare_servers = 3/pm.max_spare_servers = 8/' /usr/local/etc/php-fpm.d/www.conf

# 安装 envsubst (gettext)
RUN apk add --no-cache gettext

# 配置 Nginx
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/site.conf /etc/nginx/http.d/default.conf.template

# 配置 Supervisord
COPY docker/supervisord.conf /etc/supervisord.conf

# 设置工作目录
WORKDIR /var/www/html

# 复制项目文件
COPY . /var/www/html

# 复制 entrypoint 脚本
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# 创建必要目录并设置权限
RUN mkdir -p runtime/admin runtime/cache runtime/index runtime/log runtime/mobile runtime/session public/upload \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 777 runtime public/upload \
    && touch /tmp/workerman.pid /tmp/workerman.log \
    && chown www-data:www-data /tmp/workerman.pid /tmp/workerman.log

# 暴露端口: 80(HTTP), 2348(WebSocket)
EXPOSE 80 2348

# 使用 entrypoint 在启动前生成 .env 文件
ENTRYPOINT ["/entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
