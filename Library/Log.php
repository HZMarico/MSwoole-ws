<?php
/**
 * Created by PhpStorm.
 * User: Marico
 * Date: 16/6/20
 * Time: 18:39
 */
class Log
{
    // 日志文件命名格式
    private static $file_name = 'Y-m-d';

    /**
     * 记录日志
     * @param string $string
     * @return null
     */
    public static function record($string = '')
    {
        if (APP_DEBUG)
        {
            $folder = APP_PATH.'/Runtime/';

            $file = $folder . date(self::$file_name).'.log';

            is_string($string) || $string = var_export($string, true);

            $string = date('Y-m-d H:i:s').' -> '. $string .PHP_EOL;

            // echo $string;

            file_put_contents($file, $string, FILE_APPEND);
        }
        return null;
    }

    /**
     * 日志调试输出
     * @param string $string
     * @return null
     */
    public static function debug($string = '')
    {
        $folder = APP_PATH.'/Runtime/';

        $file = $folder . 'debug.log';

        // file_put_contents($file, $string);

        // file_put_contents($file, $string, FILE_APPEND);

        return null;
    }
}