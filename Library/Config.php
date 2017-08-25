<?php
/**
 * Created by PhpStorm.
 * User: Marico
 * Date: 16/6/21
 * Time: 16:28
 */
class Config
{
    /**
     * 获取配置文件信息
     * @param string $kind
     * @param string $param
     * @return null/array
     */
    public static function get($kind='' , $param='master')
    {
        switch ($kind)
        {
            case 'db':
            case 'database' :
                return self::dateBase($param);
            case 'cache' :
                return self::cache($param);
        }
        return [];
    }

    /**
     * 获取数据库配置信息
     * @param string $dbName
     * @param none
     * @return array
     */
    public static function dateBase($dbName='master')
    {
        // 获取配置文件
        $db = include_once APP_PATH.'/Config/db.php';
        // 返回数据
        return $db[$dbName];
    }

    /**
     * 获取数据库配置信息
     * @param string $dbName
     * @param none
     * @return array
     */
    public static function db($dbName='master')
    {
        // 获取配置文件
        $db = include_once APP_PATH.'/Config/db.php';
        // 返回数据
        return $db[$dbName];
    }

    /**
     * 获取缓存cache配置信息
     * @param $name
     * @param none
     * @return array
     */
    public static function cache($name='master')
    {
        // 获取配置文件
        $cache = include_once APP_PATH.'/Config/cache.php';
        // 返回数据
        return $cache[$name];
    }
}