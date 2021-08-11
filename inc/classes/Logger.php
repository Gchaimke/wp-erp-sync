<?php

namespace WpErpSync;

class Logger
{
    public static $log_dir = BASE_PATH . 'logs/';
    public function __construct()
    {
    }

    static function log_message($msg = '', $kind = 0)
    {
        $log_file_path =  self::$log_dir . date('d-m-Y') . '.log';
        $kind_str = '[info]';
        if ($kind == 1) {
            $kind_str = '[error]';
        }
        $time = date('d-m-Y H:i:s');

        if (!file_exists(self::$log_dir)) {
            mkdir(self::$log_dir, 0700);
            file_put_contents(self::$log_dir . 'index.php', "<?php // Silence is golden.");
        }
        file_put_contents($log_file_path, $time . ' ' . $kind_str . ' ' . $msg . PHP_EOL, FILE_APPEND);
    }

    static function getFileList()
    {
        $dir = self::$log_dir;
        $files = glob($dir . "*.log");
        usort($files, function ($a,$b) {return filemtime($a) - filemtime($b);});
        return array_reverse($files);
    }

    static function getlogContent($log_date)
    {
        $dir = self::$log_dir;
        $log_file_path = $dir . $log_date . ".log";
        if (file_exists($log_file_path)) {
            $log = file_get_contents($log_file_path);
            return $log;
        }

        return "No log for " . $log_date . ' date';
    }

    static function clearLogs()
    {
        $dir = self::$log_dir;
        $files = glob($dir . "*.log");
        foreach ($files as $file) { // iterate files
            $log = explode('/', $file);
            $log = end($log);
            $log_date = substr($log, 0, -4);
            if (is_file($file) && $log_date != date('d-m-Y')) {
                unlink($file); // delete file
            }
        }
    }
}
