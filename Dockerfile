FROM paimpozhil/magento-docker

# Allow environment variables to be loaded from a file
RUN sed -i -e '/variables_order = \"GPCS\"/s/GPCS/EGPCS/' /etc/php.ini
RUN sed -i -e '/location ~ \\\.php\$/ a\        include        \/usr\/local\/etc\/yellow\/magento.nginx.conf;' \
	/etc/nginx/conf.d/default.conf
RUN echo "" >> /var/www/.htaccess
RUN echo "############################################" >> /var/www/.htaccess
RUN echo "## Nginx proxy HTTPS fix for Magento 1.6.2.0" >> /var/www/.htaccess
RUN echo "SetEnvIf X-Forwarded-Proto https HTTPS=on" >> /var/www/.htaccess
RUN echo "" >> /var/www/.htaccess

COPY app /var/www/app/
COPY skin /var/www/skin/
RUN chmod o+w /var/www/app/etc
RUN mkdir -p /var/www/var/connect
RUN chmod o+w /var/www/var/connect
COPY connect/Yellow_Pay.xml /var/www/var/connect/
RUN chmod o+w /var/www/var/connect/Yellow_Pay.xml

