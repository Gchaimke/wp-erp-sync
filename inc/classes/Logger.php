<?php

namespace WpErpSync;

class Logger
{

    public function __construct()
    {

    }

    static function log_message($msg='',$kind=0){
        $logPath = BASE_PATH.'logs/'.date('d-m-Y').'.log';
        $kind_str = '[notice]';
        if($kind==1){
            $kind_str = '[error]';
        }
        $time = date('d-m-Y H:i:s');
        file_put_contents($logPath,$time.' '.$kind_str.' '.$msg.PHP_EOL,FILE_APPEND);
    }

}
