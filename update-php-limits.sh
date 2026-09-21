sudo sed -i "s/post_max_size = 8M/post_max_size = 50M/g" /etc/php/8.1/cli/php.ini
sudo sed -i "s/upload_max_filesize = 2M/upload_max_filesize = 50M/g" /etc/php/8.1/cli/php.ini

sudo sed -i "s/post_max_size = 8M/post_max_size = 50M/g" /etc/php/8.1/fpm/php.ini
sudo sed -i "s/upload_max_filesize = 2M/upload_max_filesize = 50M/g" /etc/php/8.1/fpm/php.ini

sudo sed -i "s/post_max_size = 8M/post_max_size = 50M/g" /etc/php/8.1/apache2/php.ini
sudo sed -i "s/upload_max_filesize = 2M/upload_max_filesize = 50M/g" /etc/php/8.1/apache2/php.ini

sudo systemctl restart apache2 || true
sudo systemctl restart php8.1-fpm || true
