<?php
/**
 * 对象管理器
 * User: marico
 * Date: 2017/8/15
 * Time: 下午4:26
 */
namespace MSwoole;

class Object
{
    // 对象存储
    private static $controllerObject = [];

    /**
     * 实例化控制器
     * @param string $addons
     * @param string $controller
     * @return object | mixed
     */
    public static function findController($addons='Index', $controller='Index')
    {
        // 制作key
        $key = implode('.', [$addons, $controller]);
        // 判断对象是否已存在
        if (!isset(self::$controllerObject[$key]))
        {
            // 构建控制器路径
            $obj = '\\App\\'.$addons.'\\Controller\\'.$controller;
            // 实例化对象
            self::$controllerObject[$key] = new $obj();
        }
        // 返回对象
        return self::$controllerObject[$key];
    }

    /**
     * 挨个调用，指定控制器方法
     * @param array $keys
     * @param string $method
     * @param array $data
     */
    public static function callControllerMethod(Array $keys=[], $method='', Array $data=[])
    {
        // 需要调用的对象
        $object = [];
        // 判断keys是否为空，若为空，则整体调用
        if (empty($keys))
        {
            // 直接赋值
            $object = self::$controllerObject;
        }
        else
        {
            // 反转，获取指定控制器
            $keys = array_flip($keys);
            $object = array_intersect_key(self::$controllerObject, $keys);
        }
        // 循环，调用
        foreach ($object as $obj)
        {
            // 判断 $obj 是否存在方法，若存在则进行调用传参数
            method_exists($obj, $method) && call_user_func_array([$obj, $method], $data);
        }
    }
}