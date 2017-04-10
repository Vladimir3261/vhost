#!/usr/bin/php
<?php
/**
 * Virtual host creator (http://php-server.xyz/)
 * @link https://github.com/Vladimir3261/vhost for the canonical source repository
 * @copyright Copyright free-soft
 */
define('APACHE_PORT', 8080);
define('NGINX_PORT', 80);
/**
 * Create virtual host configuration .conf for apache2 web server
 * @param $host string
 * @param $dir string
 * @param $index string
 */
function createApacheHost($host, $dir, $index) {
    $hostStr = '
<VirtualHost *:'.APACHE_PORT.'>
     ServerName {PHP_HOSTNAME}
     DocumentRoot {PHP_DIR}
     SetEnv APPLICATION_ENV "development"
     <Directory {PHP_DIR}>
         DirectoryIndex {PHP_INDEX}
         AllowOverride All
          Require all granted
         Allow from all
     </Directory>
 </VirtualHost>';
    $filename = '/etc/apache2/sites-available/'.$host.'.conf';
    touch($filename, 0775);
    file_put_contents($filename, str_replace(
        ['{PHP_HOSTNAME}', '{PHP_DIR}', '{PHP_INDEX}'],
        [$host, $dir, $index],
        $hostStr
    ));
}

/**
 * Creating nginx virtual host configuration file
 * @param $host string
 * @param $dir string
 */
function CreateNginxHost($host, $dir) {
    $hostStr = 'server {
        listen *:'.NGINX_PORT.'; ## listen for ipv4
        server_name {PHP_HOSTNAME};
        access_log /var/log/nginx/{PHP_HOSTNAME}.log;
        # Proxy to back-end back-end
                location / {
                        proxy_pass http://{PHP_HOSTNAME}:8080/;
                        proxy_set_header Host $host;
                        proxy_set_header X-Real-IP $remote_addr;
                        proxy_set_header X-Forwarded-For $remote_addr;
                        proxy_connect_timeout 120;
                        proxy_send_timeout 120;
                        proxy_read_timeout 180;
                }
        # NGINX returns only static data
        location ~* \.(jpg|jpeg|gif|png|ico|css|bmp|swf|js|html|txt)$ {
                root {PHP_DIR};
        }
}';
    $filename = '/etc/nginx/sites-available/'.$host;
    touch($filename, 0775);
    file_put_contents($filename, str_replace(
        ['{PHP_HOSTNAME}', '{PHP_DIR}'],
        [$host, $dir], $hostStr
    ));
}

/**
 * Add record to /etc/hosts file. I'm sure not know why,
 * but nginx can't start without this =). Actually i see this problem just on my local PC
 * may be this require a specific nginx configuration
 * @param $host string
 * @return string string
 */
function updateHosts($host) {
    try
    {
        if( file_put_contents('/etc/hosts', "\r\n 127.0.0.1    ".$host."\r\n", FILE_APPEND) )
            return "\e[1m Updating hosts file... [\e[32mOK] \e[0m"."\r\n";
        else
            throw new Exception('permissions denied');
    }catch (Exception $e){
        return "\e[1m can\t update hosts hosts file. message: ".$e->getMessage()."... [\e[32mOK] \e[0m"."\r\n";
    }
}

/**
 * Enable site and reload web servers
 * @param $host string
 */
function reloadServices($host) {
    // Enable apache2 Host
    system("cd /etc/apache2/sites-available && a2ensite ".$host.".conf");
    // Enable nginx host
    system("ln -s /etc/nginx/sites-available/".$host." /etc/nginx/sites-enabled/".$host);
    // Reload Apache
    system("/etc/init.d/apache2 restart");
    // Reload nginx
    system("/etc/init.d/nginx restart");
}

/**
 * This function just create working directory
 * and index file for virtual host working demonstration
 * @param $path string
 * @param $index string
 * @return string
 */
function createSite($path, $index) {
    if(!is_dir($path))
    {
        try
        {
            // Create site directory
            if(!system("sudo mkdir -p ".$path)){
                throw new Exception('Cant create site working directory '.$path);
            }
            // Create index file
            if(!touch($path.'/'.$index, 0777)){
                throw new Exception('Cant create index file '.$index);
            }
            // Put data to index File
            if(!file_put_contents($path.'/'.$index, '<h1>Welcome to virtual host. Current server time is <?=date("d-m-y H:i:s")?></h1>')){
                throw new Exception('Can\'t write to index file '.$index.' Permission denied');
            }
            return "\e[1m Creating working directory and  index file... [\e[32mOK] \e[0m"."\r\n";
        } catch (Exception $e) {
            return "\e[1m Creating working directory and index file error with message ".$e->getMessage()." [\e[31mFAIL] \e[0m"."\r\n";
        }
    }elseif(!is_file($path.'/'.$index)){
        try {
            // Create index file
            if(!touch($path.'/'.$index, 0777)){
                throw new Exception('Cant create index file '.$index);
            }
            // Put data to index File
            if(!file_put_contents($path.'/'.$index, '<h1>Welcome to virtual host. Current server time is <?=date("d-m-y H:i:s")?></h1>')){
                throw new Exception('Can\'t write to index file '.$index.' Permission denied');
            }
            return "\e[1m Creating working directory index file... [\e[32mOK] \e[0m"."\r\n";
        }catch (Exception $e){
            return "\e[1m Creating working directory index file error with message ".$e->getMessage()." [\e[31mFAIL] \e[0m"."\r\n";
        }
    }else{
        return "\e[1m Directory index file already exists... [\e[32mOK] \e[0m"."\r\n";
    }
}



// Virtual host name like test.local
$virtualHostName = readline('Enter virtual host name: ');
// Files path like /var/www/html
$filesPath = readline('Put files path (/var/www/YOU_HOST_NAME): ');
// index file like index.php
$index = readline('Enter index file name (index.php): ');

if(!$virtualHostName){
    echo "\e[1m Host name is required......[\e[31mFAIL] \e[0m"."\r\n";exit(1);
}
if(!$filesPath)
    $filesPath = '/var/www/'.$virtualHostName;
if(!$index)
    $index = 'index.php';

usleep(500);
echo updateHosts($virtualHostName);
/**
 * APACHE
 */
echo "\e[1m Starting virtual Host creating... \e[0m"."\r\n";
usleep(500);
echo "\e[1m Starting Apache host creating on port ".APACHE_PORT."... \e[0m"."\r\n";
try{
    createApacheHost($virtualHostName, $filesPath,$index);
}catch (Exception $e){
    echo "\e[1m Apache virtual hos creating (permission denied)......[\e[31mFAIL] \e[0m"."\r\n";exit(1);
}
echo "\e[1m Apache virtual hos creating... [\e[32mOK] \e[0m"."\r\n";


/**
 * NGINX
 */
usleep(500);
echo "\e[1m Starting Nginx host creating on port ".NGINX_PORT."... \e[0m"."\r\n";
usleep(500);
try{
    CreateNginxHost($virtualHostName, $filesPath);
}catch (Exception $e){
    echo "\e[1m Nginx virtual hos creating (permission denied)......[\e[31mFAIL] \e[0m"."\r\n";exit(1);
}
echo "\e[1m Nginx virtual  host creating ...... [OK] \e[0m"."\r\n";

/**
 * Reload and enable services
 */
usleep(5);
echo "\e[1m Enabling services \e[0m"."\r\n";
try{
    reloadServices($virtualHostName);
}catch (Exception $e){
    echo "\e[1m Reload services......[\e[31mFAIL] \e[0m"."\r\n";exit(1);
}
echo "\e[1m Reload services ...... [OK] \e[0m"."\r\n";
usleep(500);
echo createSite($filesPath, $index);