ARG PHP_TAG
FROM wodby/php:$PHP_TAG as php
USER root
# ncurses used for caUtils command
RUN apk add --update --no-cache \
    ncurses \
    python3 \
    wkhtmltopdf \
    poppler-utils \
    py3-pdfminer \
    libgcc \
    libstdc++ \
    musl \
    qt5-qtbase \
    qt5-qtbase-x11 \
    qt5-qtsvg \
    qt5-qtwebkit \
    ttf-freefont \
    ttf-dejavu \
    ttf-droid \
    ttf-liberation \
    ttf-ubuntu-font-family \
    fontconfig \
    graphicsmagick \
    graphicsmagick-dev \
    alpine-sdk \
    autoconf \
    libtool \
    parallel \
    exiftool \
    pv \
    ffmpeg \
    procps \
    npm \
    mediainfo \
    font-misc-misc \
    aws-cli \
    aws-cli-bash-completion \
    aws-cli-doc \
    freetype \
    freetype-dev \
    harfbuzz \
    ca-certificates \
    ttf-freefont \
    zip && \
    composer self-update

ENV APP_ROOT="/var/www/html" \
    COLLECTIVEACCESS_HOME="/var/www/html/providence"
# Install gmagick extension for better and faster media processing.
RUN yes|pecl -D with-gmagick=autodetect install -s channel://pecl.php.net/gmagick-2.0.6RC1 && \
    docker-php-ext-enable gmagick && \
# Run php -i at the end because this has been known to segfault
    php -i && \
    mkdir -p $APP_ROOT/bin && \
    chown wodby:wodby -R $APP_ROOT/bin

COPY --from=aantonw/alpine-wkhtmltopdf-patched-qt /bin/wkhtmltopdf /usr/bin/wkhtmltopdf

USER wodby
# Install composer dependencies
COPY bin/deploy ./bin/
COPY composer.* ./
RUN composer install
COPY . .
