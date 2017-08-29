<?php

/**
 * Created by PhpStorm.
 * User: marico
 * Date: 2017/8/15
 * Time: 下午2:13
 */
namespace MSwoole;

use Swoole\Mysql\Exception;

class Handler
{
    // 文件后缀
    public static $ext = '.php';

    /**
     * 自动加载规则
     * @param string $class
     * @param none
     * @return mixed | null
     * @throws \Exception
     */
    public static function autoLoad($class='')
    {
        // 组合Library目录下
        $file = str_replace('\\', '/', $class);
        $file = APP_PATH.'/Library/'.$file.self::$ext;
        // 判断文件是否存在
        if (file_exists($file))
        {
            return include $file;
        }

        // 组合APP_PATH下路径
        $file = str_replace('\\', '/', $class);
        $file = APP_PATH.'/'.$file.self::$ext;
        // 判断文件是否存在
        if (file_exists($file))
        {
            return include $file;
        }
        // 抛出异常
        throw new \Exception('Class Not Found :'.$class, '404');
    }

    /**
     * 错误处理
     * @param $errno
     * @param $errstr
     * @param $errfile
     * @param $errline
     */
    public static function appError($errno, $errstr, $errfile, $errline)
    {
        // 根据错误等级显示
        switch ($errno)
        {
            case E_ERROR:
            case E_USER_ERROR:
                $errorStr = "错误：[$errno] $errstr " . basename($errfile) . " 第 $errline 行." . PHP_EOL;
                break;
            case E_STRICT:
            case E_USER_WARNING:
            case E_USER_NOTICE:
            default:
                $errorStr = "注意：[$errno] $errstr " . basename($errfile) . " 第 $errline 行." . PHP_EOL;
                break;
        }
        // 判断是否为debug状态
        if (APP_DEBUG)
        {
            echo $errorStr;
        }
    }

    /**
     * 异常处理
     * @param $e
     * @param
     * @return none
     */
    public static function appException($e)
    {
        // 判断是否为debug状态
        if (APP_DEBUG && is_object($e))
        {
            echo $e->getMessage().PHP_EOL;
        }
    }
}