FROM paimpozhil/magento-docker

COPY app /var/www/app/
COPY skin /var/www/skin/
RUN chmod o+w /var/www/app/etc

