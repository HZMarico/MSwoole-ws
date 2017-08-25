<?php
/**
 * Created by PhpStorm.
 * User: marico
 * Date: 2017/8/17
 * Time: 上午10:38
 */
use Swoole\Http\Client;
// 用户加密信息
$auth = '';
// 处理url
$url = '/?auth='.$auth;
// 请求参数
$data = [
    'method' => 'live.wall.history',
    'type' => 'wall',
    'page' => 1,
    'count' => 10,
];
// 客户端
$client = new Client('127.0.0.1', 20000, false);
// 监听消息
$client->on('message', function (Client $client, $frame) {
    var_dump(json_decode($frame->data, true));
    echo $frame->data.PHP_EOL;
    // 收到消息后关闭
    // $client->close();
});
// 升级为websocket连接，并发送数据
$client->upgrade($url, function (Client $client) use ($data) {
    if ($client->isConnected()) {
        $client->push(json_encode($data));
    } else {
        echo '连接服务失败'.PHP_EOL;
    }
});