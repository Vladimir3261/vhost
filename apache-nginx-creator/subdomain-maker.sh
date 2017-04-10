#!/usr/bin/env bash
#Apache 2 HTTP listen port
APACHE_PORT="8080"
# NGINX web server HTTP listen port
NGINX_PORT="80"
#Domain for subdomains creation
MAIN_HOST="{YOU_MAIN_DOMAIN_NAME}"
#Mysql clear database dump for new sub domain
SQL_DUMP="/var/www/subdomain-maker/dump.sql"
# PHP scripts path (This is the all of site files)
BACKUP_PATH="/var/www/subdomain-maker/site/*"
# Server public IP address
# Also we can know IP address using following function $(ip -4 route get 8.8.8.8 | awk {'print $7'} | tr -d '\n')
SERVER_IP="46.0.0.0"
# Digitalocean API key for create domain A records
# See more at https://developers.digitalocean.com/documentation/v2/#domain-records
API_KEY="{YOU_API_KEY_HERE}"
# Sub domain name
SUB=$1
# Full virtual host name using main doamin name and sub domain like dev.site.com
VIRTUAL_HOSTNAME=$SUB"."$MAIN_HOST
# Site public path
FILES_PATH=$2

# Apache 2 config
APACHE_CONFIG="<VirtualHost *:$APACHE_PORT>
     ServerName
     DocumentRoot $FILES_PATH
     SetEnv APPLICATION_ENV \"development\"
     <Directory $FILES_PATH>
         DirectoryIndex index.php
         AllowOverride All
          Require all granted
         Allow from all
     </Directory>
 </VirtualHost>"
 # Nginx config
 NGINX_CONFIG="server {
        listen *:$NGINX_PORT; ## listen for ipv4
        server_name $VIRTUAL_HOSTNAME;
        access_log /var/log/nginx/$VIRTUAL_HOSTNAME.log;
        # Proxy to back-end back-end
                location / {
                        proxy_pass http://$VIRTUAL_HOSTNAME:8080/;
                        proxy_set_header Host \$host;
                        proxy_set_header X-Real-IP \$remote_addr;
                        proxy_set_header X-Forwarded-For \$remote_addr;
                        proxy_connect_timeout 120;
                        proxy_send_timeout 120;
                        proxy_read_timeout 180;
                }
        # NGINX returns only static data
        location ~* \.(jpg|jpeg|gif|png|ico|css|bmp|swf|js|html|txt)$ {
                root $FILES_PATH;
        }
}"
# Create nginx configuration file for current sub domain
nginx_config_file="/etc/nginx/sites-available/"$VIRTUAL_HOSTNAME
# Create apache2 .conf file for current sub domain
apache_confg_file="/etc/apache2/sites-available/"$VIRTUAL_HOSTNAME".conf"
# Add record to hosts file. Direct subdomain to localhost. Nginx can't start without this record
echo "127.0.0.1 $VIRTUAL_HOSTNAME" >> /etc/hosts
# Write prepared config data to files
echo "$APACHE_CONFIG" >> $nginx_config_file
echo "$APACHE_CONFIG" >> $apache_confg_file
# Generate MySQL user and password for this domain
mysql_user="sub_$SUB"
mysql_password=$(openssl rand -base64 6)
mysql_database="sub_$SUB"
# @TODO fix mysql unsafe passwords warnings
# Create Mysql user
mysql -uroot -p123456 -e "CREATE USER $mysql_user@localhost IDENTIFIED BY '$mysql_password'"
# Add privileges for created user only to his own database
mysql -uroot -p123456 -e "GRANT ALL PRIVILEGES ON $mysql_database.* To $mysql_user@localhost"
# Create database
mysql -uroot -p123456 -e "CREATE DATABASE $mysql_database"
# FLUSH
mysql -uroot -p123456 -e "FLUSH PRIVILEGES"
# Load prepared (clear) SQL dump to created database
mysql -uroot  -p123456 $mysql_database < $SQL_DUMP
# Create PHP database configuration linked to created database
# This part of script works for php scripts that use ISV framework
# https://github.com/Vladimir3261/isvCore
new_db_config="<?php
    return [
        'db' => [
            'MYSQL' => [
                'host'     => '127.0.0.1',
                'driver'   => 'pdo_mysql',
                'user'     => '$mysql_user',
                'password' => '$mysql_password',
                'dbname'   => '$mysql_database',
                'charset'  => 'utf8',
            ],
        ]
    ];
"
# Create site path
mkdir -p $FILES_PATH
# Load site files to created path
cp -r $BACKUP_PATH $FILES_PATH
# Reload services
/etc/init.d/apache2 restart
/etc/init.d/nginx restart
# Update database connection for created site
echo "$new_db_config" > "$FILES_PATH/config/db.php";
# LOG All data
log="
--------- http://$VIRTUAL_HOSTNAME ----------
mysql_user: $mysql_user
mysql_password: $mysql_password
mysql_host: $SERVER_IP
mysql_port: 3306
Files path: $FILES_PATH
"
echo "$log" >> log.log
# Add domain "A" record (digitalocean API call)
curl -X POST -H "Content-Type: application/json" -H "Authorization: Bearer $API_KEY" -d '{"type":"A","name":"'$SUB'","data":"'$SERVER_IP'","priority":null,"port":null,"weight":null}' "https://api.digitalocean.com/v2/domains/$MAIN_HOST/records"
echo "OK"