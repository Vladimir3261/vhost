#VIRTUAL HOST CREATOR
This script create virtual host for web server apache+nginx
#Requirments
 **PHP interpreter version 5.4+**
 
 **Unix based OS**
For run this application:
    
    sudo chmod +x apache-nginx-creator.php
    
    sudo ./apache-nginx-creator
    
 First you must enter the name of your virtual host, for example, test.local
 further need to enter the path to your project files (the default /var/www/VIRTUAL_HOST_NAME/).
 If such a path does not exist it will be created by script.
 Next, enter the name of the main file (the entry point of the application) 
 the default is index.php.
 
 Wait until the script execution and open the link in a browser `http://test.local`
 
 NOTE: assumes that your nginx server is listening on port 80 and apache on port 8080
 if you servers configured on on other ports you need change defines in script
 `APACHE_PORT` and `NGINX_PORT`
 
Tested on: 
nginx  version: nginx/1.6.2
apache Apache/2.4.10
OS: Debian 8 Jessie
 

