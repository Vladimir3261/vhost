<?php
/**
 * Created by PhpStorm.
 * User: vladimir
 * Date: 27.10.16
 * Time: 11:52
 */
define('LOG', 1);
define('DELETE_FROM_SERVER', true);
define('LOG_FILE', '/home/vladimir/sites/release.local/ftp.log');

$ftp_server = 'you-ftp-server.com';
$ftp_user_name = 'root';
$ftp_user_pass = '123456';
$local_save_path = '/home/username/backups';
$server_path = '/var/www/backups';

function logger($data){
    if(LOG) {
        if(is_string($data) || is_int($data))
            file_put_contents('['.date('d-m-Y H:i:s').'] '.$data."\r\n", LOG_FILE, FILE_APPEND);
        else
            file_put_contents('['.date('d-m-Y H:i:s').'] '.var_export($data, true)."\r\n", LOG_FILE, FILE_APPEND);
    }
}

// Connect to ftp server
$conn_id = ftp_connect($ftp_server);
if($conn_id)
{
    logger('user '.$ftp_user_name.' Success connected to server'.$ftp_server);
}
else
{
    logger("cant connect to to FTP server $ftp_server using login $ftp_user_name and password $ftp_user_pass");
}
// Log in
ftp_login($conn_id, $ftp_user_name, $ftp_user_pass);
// get all server files
$serverFiles = ftp_nlist($conn_id, $server_path);

foreach($serverFiles as $server_file)
{
    $fileArray = explode('/', $server_file);
    $fileName = end($fileArray);
    if (ftp_get($conn_id, $local_save_path.'/'.$fileName, $server_file, FTP_BINARY))
    {
        $log_file .= date("Y-m-d H:i:s")." File $server_file  success downloaded .\r\n";
        logger("File $server_file  success downloaded.");
        if(DELETE_FROM_SERVER)
        {
            if (ftp_delete($conn_id, $server_file))
            {
                logger("File success $server_file deleted from server.");
            }
            else
            {
                logger("Can't delete $server_file. Permission denied");
            }
        }
    }
    else
    {
        logger("Can't download file $server_file");
    }
}
// close connection
ftp_close($conn_id);
logger('FTP CLOSED');