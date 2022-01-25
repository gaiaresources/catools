ARG APACHE_TAG
FROM wodby/apache:$APACHE_TAG as apache
# TODO Pass this in from config to reduce duplication
RUN echo 'SetEnv COLLECTIVEACCESS_HOME /var/www/html/rwahs-providence' > /usr/local/apache2/conf/conf.d/setenv.conf
