<?php
/**
 * Created by PhpStorm.
 * User: marico
 * Date: 2017/3/17
 * Time: 下午6:36
 */
class Cache
{
    /**
     * 操作句柄
     * @var object
     * @access protected
     */
    protected static $handler;

    /**
     * 获取Redis实例
     * @param string $name
     * @param none
     * @return object
     */
    public static function getRedis($name='master')
    {
        // 判断是否实例并连接
        if (!isset(self::$handler[$name]))
        {
            // 读取配置文件
            $config = \Config::cache($name);
            // 实例化对象
            self::$handler[$name] = new redis();
            // 建立连接
            self::$handler[$name]->connect($config['host'], $config['port']);
            // 若配置auth，则进行身份验证
            isset($config['password']) && self::$handler[$name]->auth($config['password']);
        }
        // 返回对应对象
        return self::$handler[$name];
    }

    /**
     *
     */
    public static function get()
    {

    }

    /**
     *
     */
    public static function set()
    {

    }

    /**
     * 关闭所有连接
     * @param none
     * @param none
     * @return none
     */
    public static function closeAll()
    {
        foreach (self::$handler as $v)
        {
            $v->close();
        }
    }
}