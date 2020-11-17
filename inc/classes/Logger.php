<?php

namespace WpErpSync;

class Logger
{

    public function __construct()
    {
    }

    static function log_message($msg = '', $kind = 0)
    {
        $logPath = BASE_PATH . 'logs/' . date('d-m-Y') . '.log';
        $kind_str = '[notice]';
        if ($kind == 1) {
            $kind_str = '[error]';
        }
        $time = date('d-m-Y H:i:s');

        if (!file_exists(BASE_PATH . 'logs')) {
            mkdir(BASE_PATH . 'logs', 0700);
            file_put_contents(BASE_PATH . 'logs/index.php',"<?php // Silence is golden.");
        }
        file_put_contents($logPath, $time . ' ' . $kind_str . ' ' . $msg . PHP_EOL, FILE_APPEND);
    }
}
