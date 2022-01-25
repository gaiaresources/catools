ARG PHP_TAG
FROM wodby/php:$PHP_TAG as php
USER root
## ncurses used for caUtils command
#RUN apk add --update --no-cache \
#  ncurses

USER wodby