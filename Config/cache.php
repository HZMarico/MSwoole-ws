<?php
/**
 * Created by PhpStorm.
 * User: marico
 * Date: 2017/8/15
 * Time: 下午12:27
 */
return [
    // 主缓存配置
    'master' => [
        'type' => 'redis',
        'host' => '127.0.0.1',
        'port' => 6379,
        //'password' => '',
        'timeout' => 3,
        'expire' => 600,
        'prefix' => 'Swoole_'
    ],
    // task任务，需额外配置，为生成新对象
    'task' => [
        'type' => 'redis',
        'host' => '127.0.0.1',
        'port' => 6379,
        //'password' => '',
        'timeout' => 3,
        'expire' => 600,
        'prefix' => 'Swoole_'
    ],
];