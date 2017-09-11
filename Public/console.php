<?php
/**
 * Created by PhpStorm.
 * User: marico
 * Date: 2017/8/15
 * Time: 上午11:42
 */
// 获取设置参数
$data = getopt('s:h:p:i:');

isset($data['s']) || die("缺少s指令，s指令用于指明具体操作\n");

in_array($data['s'], ['start', 'stop', 'reload', 'status', 'say'])
    || die("s指令仅可用start|stop|reload|status|say".PHP_EOL);

// 定义常量
ini_set('date.timezone','Asia/Shanghai');
define("APP_PATH", realpath(dirname(__FILE__) . '/../'));
define("APP_DEBUG", true);
define("APP_AUTH_CHECK", false);

// 获取console.对象
include APP_PATH."/MSwoole/Console.php";

// 实例化console,并传参数
new \MSwoole\Console($data);