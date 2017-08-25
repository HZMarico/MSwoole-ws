<?php
/**
 * Created by PhpStorm.
 * User: marico
 * Date: 2017/8/15
 * Time: 下午12:26
 */
return [
    'setting' => [
        'log_file' => '/data/web/logs/swoole_websocket.log',
        'worker_num' => 1,
        'task_worker_num' => 1,
        'task_ipc_mode' => 2,
        'daemonize' => false, // 是否守护进程
    ],
    'manager' => [
        'host' => '127.0.0.1',
        'port' => '20001',
    ],
    'outside' => [
        'host' => '127.0.0.1',
        'port' => '20000',
    ]
];