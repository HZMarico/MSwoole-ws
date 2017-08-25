<?php

/**
 * Created by PhpStorm.
 * User: marico
 * Date: 2017/8/15
 * Time: 下午12:14
 */
namespace MSwoole;

use Swoole\Websocket\Server;

class App
{
    // server对象
    public $server = null;
    // 单例对象
    private static $instance = null;
    // fd突然离开时需要通知的控制器
    public $FdController = 'WEBSOCKET_FdController_';
    // fd突然离开时需要处理的集合数据
    public $FdCloseSetKey = 'WEBSOCKET_FdCloseSetKey_';

    /**
     * 实例化 ，单例
     * @param array $data
     * @return App|null
     */
    public static function getInstance(Array $data=[])
    {
        self::$instance instanceof self || self::$instance = new self($data);
        return self::$instance;
    }
    /**
     * App constructor.私有化实例函数
     * @param Array $data
     * @param none
     */
    private function __construct(Array $data=[])
    {
        // 初始化服务（对内管理）
        $this->initManager($data['manager'], $data['setting']);
        // 注册task任务处理
        $this->initTask();
        // webscoket 对外服务
        $this->initOutside($data['outside']);
        // 启动swoole服务
        $this->server->start();
    }

    /**
     * 初始化服务及对内管理
     * @param array $data
     * @param array $setting
     */
    private function initManager(Array $data=[], Array $setting=[])
    {
        // 实例化管理对象
        $Manager = new Manager($this);
        // Server对象
        $this->server = new Server($data['host'], $data['port']);
        // 服务配置
        $this->server->set($setting);
        // 监听建立连接
        $this->server->on('open', [$Manager, 'onOpen']);
        // 监听消息处理
        $this->server->on('message', [$Manager, 'onManager']);
        // 监听关闭
        $this->server->on('close', [$Manager, 'onClose']);
    }

    /**
     * 初始化任务监听
     * @param none
     * @param none
     * @return none
     */
    private function initTask()
    {
        $this->server->on('task', [$this, 'onTask']); // 任务监听
        $this->server->on('finish', [$this, 'onFinish']); // 任务结束监听
    }

    /**
     * 初始化对外服务
     * @param array $data
     * @param none
     */
    private function initOutside(Array $data=[])
    {
        // 获取对外服务对象
        $Outside = new Outside($this);
        // 额外服务对象
        $other = $this->server->listen($data['host'], $data['port'], SWOOLE_SOCK_TCP);
        // 统一握手
        $other->on('handshake', [$Outside, 'onHandshake']);
        // 统一开启时操作
        $other->on('open', [$Outside, 'onOpen']);
        // 统一消息来源入口
        $other->on('message', [$Outside, 'onMessage']);
        // 统一监听关闭
        $other->on('close', [$Outside, 'onClose']);
        // 监听HTTP请求
        $other->on('request', [$Outside, 'onHttp']);
    }

    /**
     * task任务，向所有连接投递消息
     * @param $server
     * @param $worker_id
     * @param $task_id
     * @param $data
     * @return string
     */
    public function onTask(Server $server, $worker_id=0, $task_id=0, $data)
    {
        // 获取redis对象，需与外部对象隔离（新对象）
        $redis = \Cache::getRedis('task');
        // 处理data
        $data = unserialize($data);
        $redis_key = $data['REDIS_KEY'];
        unset($data['REDIS_KEY']);
        // 处理users
        $users = $redis->sgetmembers($redis_key);
        empty($users) && $users = [];
        // 循环所有，挨个通知
        foreach ($users as $fd)
        {
            if ($server->exist($fd)) // 判断当前用户是否存在
            {
                $server->push($fd, json_encode($data));
            }
            else
            {
                $redis->sRemove($redis_key, $fd);
            }
        }
        return $task_id;
    }

    /**
     * task任务结束
     * @param $server
     * @param $task_id
     * @param $result
     * @return bool
     */
    public function onFinish(Server $server, $task_id, $result)
    {
        return $task_id;
    }

    /**
     * 增加fd离开时需要处理的集合
     * @param int $fd
     * @param string $controller
     */
    public function fdAddController($fd=0, $controller='')
    {
        // 获取redis对象
        $redis = \Cache::getRedis();
        // 制作KEY
        $key = $this->FdController.$fd;
        // 存入集合
        $redis->sAdd($key, $controller);
    }

    /**
     * 减少fd离开时需要处理的集合
     * @param int $fd
     * @param string $controller
     */
    public function fdRemoveController($fd=0, $controller='')
    {
        // 获取redis对象
        $redis = \Cache::getRedis();
        // 制作KEY
        $key = $this->FdController.$fd;
        // 存入集合
        $redis->sRem($key, $controller);
    }

    /**
     * 增加fd离开时需要处理的集合
     * @param int $fd
     * @param string $key
     */
    public function fdAddCloseKey($fd=0, $key='')
    {
        // 获取redis对象
        $redis = \Cache::getRedis();
        // 制作KEY
        $key = $this->FdCloseSetKey.$fd;
        // 存入集合
        $redis->sAdd($key, $key);
    }

    /**
     * 减少fd离开时需要处理的集合
     * @param int $fd
     * @param string $key
     */
    public function fdRemoveCloseKey($fd=0, $key='')
    {
        // 获取redis对象
        $redis = \Cache::getRedis();
        // 制作KEY
        $key = $this->FdCloseSetKey.$fd;
        // 存入集合
        $redis->sRem($key, $key);
    }

    /**
     * 进行消息发送
     * @param int $fd
     * @param string $event
     * @param string $info
     * @param mixed $param
     * @return mixed
     */
    public function sendSuccessMessage($fd=0, $event='', $info='', $param=[])
    {
        $this->sendMessage($fd, 200, $event, $info, $param);
    }

    /**
     * 进行消息发送
     * @param int $fd
     * @param int $status
     * @param string $event
     * @param string $info
     * @param mixed $param
     * @return mixed
     */
    public function sendMessage($fd=0, $status=200, $event='', $info='', $param=[])
    {
        // 若为空，则不处理
        if (empty($fd)){return false;}
        // 准备json数据
        $data = [
            'status' => $status,
            'event' => $event,
            'info' => $info,
        ];
        empty($param) || $data['param'] = $param;
        // 判断是否为纯数字，纯数字则视为向一个用户发送消息
        if (is_numeric($fd))
        {
            $this->server->push($fd, json_encode($data));
        }
        else
        {
            $data['REDIS_KEY'] = $fd;
            $this->server->task(serialize($data));
        }
    }
}