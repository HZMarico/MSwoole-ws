<?php
/**
 * Created by PhpStorm.
 * User: marico
 * Date: 2017/8/15
 * Time: 下午3:00
 */

namespace MSwoole;

use Swoole\Websocket\Server;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Websocket\Frame;

class Manager
{
    /**
     * 实例化，依赖注入
     * @param App $app
     */
    public function __construct(App $app)
    {
        $this->app = $app;
    }

    /**
     * 当链接打开时
     * @param Server $server
     * @param Request $request
     * @return none
     */
    public function onOpen(Server $server, Request $request)
    {
    }

    /**
     * 消息处理
     * @param Server $server
     * @param Frame $frame
     * @return bool
     */
    public function onManager(Server $server, Frame $frame)
    {
        // json格式解码
        $data = json_decode($frame->data, true);
        if (empty($data) || !isset($data['method']))
        {
            $server->push($frame->fd, '格式不符合规范');
            $server->close($frame->fd);
            return false;
        }
        switch ($data['method'])
        {
            // 服务重启
            case 'reload':
                // 回复控制台
                $server->push($frame->fd, '服务即将重启');
                // 通知所有已实例化的控制器对象
                Object::callControllerMethod([], 'onReload', [$data]);
                // 服务重启
                $server->reload();
                break;
            // 停止服务
            case 'stop' :
                // 回复控制台
                $server->push($frame->fd, '服务即将关闭');
                // 通知所有已实例化的控制器对象
                Object::callControllerMethod([], 'onShutdown', [$data]);
                // 最终关闭服务
                return $server->shutdown();
                break;
            // 获取server状态
            case 'status' :
                $string = '当前服务器共有 '.count($server->connections). ' 个连接'.PHP_EOL;
                $string .= '主进程PID: '.$server->master_pid.PHP_EOL;
                $string .= '内存占用量: '.$this->getMemoryUsage().PHP_EOL;
                $server->push($frame->fd, $string);
                break;
            // 管理员通知
            case 'say':
                // 遍历循环通知
                foreach ($server->connections as $fd)
                {
                    $server->push($frame->fd, json_encode([
                        'status' => 200,
                        'event' => 'serverBroadcast',
                        'info' => '收到服务广播内容',
                        'param' => $data['say']
                    ]));
                }
                break;
            default :
                $server->push($frame->fd, '指令无效');
        }
        // 关闭当前管理连接
        $server->close($frame->fd);
    }

    /**
     * 关闭连接处理
     * @param Server $server
     * @param $fd
     * @param $object
     * @return bool
     */
    public function onClose(Server $server, $fd, $object)
    {
        // 不做处理
    }

    /**
     * 获取内存用量
     * @param none
     * @param none
     * @return string
     */
    private function getMemoryUsage()
    {
        $memory = 0;

        if (function_exists('memory_get_usage'))
        {
            $memory = round(memory_get_usage()/1024/1024, 2).'MB';
        }

        return $memory;
    }
}