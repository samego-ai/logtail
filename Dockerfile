FROM swooleinc/swoole

COPY . /var/www/

EXPOSE 1280

CMD ["/usr/local/bin/php","/var/www/app/logtail.php"]
