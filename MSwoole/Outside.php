<?php
/**
 * Created by PhpStorm.
 * User: marico
 * Date: 2017/8/15
 * Time: 下午3:02
 */

namespace MSwoole;

use Swoole\Websocket\Server;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Websocket\Frame;

class Outside
{
    public $FdInfo = 'WEBSOCKET_FDINFO';
    public $UidToFd = 'WEBSOCKET_UIDTOFD';
    // fd需要通知的控制器
    public $FdController = 'WEBSOCKET_FdController_';
    // fd突然离开时需要处理的集合数据
    public $FdCloseSetKey = 'WEBSOCKET_FdCloseSetKey_';
    // 对外对象
    private $app = null;

    /**
     * 实例化，依赖注入
     * @param App $app
     */
    public function __construct(App $app)
    {
        $this->app = $app;
    }

    /**
     * 自定义握手规则
     * @param Request $request
     * @param Response $response
     * @param $object
     * @return bool
     */
    public function onHandshake(Request $request, Response $response)
    {
        // 检查用户合法性
        $user = $this->checkRequest($request);
        // 判断解码是否成功,用户合法性(不成功则返回404)
        if (empty($user))
        {
            $response->header('message', 'error auth');
            $response->status(403);
            $response->end();
            return false;
        }
        // 自定定握手规则，没有设置则用系统内置的（只支持version:13的）
        if (!isset($request->header['sec-websocket-key']))
        {
            $response->end();
            return false;
        }
        if (0 === preg_match('#^[+/0-9A-Za-z]{21}[AQgw]==$#', $request->header['sec-websocket-key'])
            || 16 !== strlen(base64_decode($request->header['sec-websocket-key']))
        )
        {
            $response->status(403);
            $response->end();
            return false;
        }
        // 设置返回HEADER头
        $key = base64_encode(sha1($request->header['sec-websocket-key'].'258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));
        $headers = [
            'Upgrade' => 'websocket',
            'Connection'  => 'Upgrade',
            'Sec-WebSocket-Accept' => $key,
            'Sec-WebSocket-Version' => '13',
            'KeepAlive' => 'off',
        ];
        foreach ($headers as $key => $val)
        {
            $response->header($key, $val);
        }
        $response->status(101);
        $response->end();
        return true;
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
     * @param $server
     * @param $frame
     * @param object $object
     * @return bool
     */
    public function onMessage(Server $server, Frame $frame)
    {
        // 连接编号
        $fd = $frame->fd;
        // 保持连接使用
        if ($frame->data == '2')
        {
            return $server->push($fd, '3');
        }
        // json格式解码
        $data = json_decode($frame->data, true);
        // 若解码失败，则关闭连接
        if (!is_array($data))
        {
            return $server->close($fd);
        }
        // 回复给客户端，转而进行处理
        $this->app->sendSuccessMessage($fd,  'receiveMessage', '服务端已收到您的请求内容');
        // 获取redis对象
        $redis = \Cache::getRedis();
        // 获取当前FD的数据
        $user = $redis->hget($this->FdInfo, $fd);
        // 判断是否获取成功
        if (!empty($user))
        {
            // 解码数据
            $user = unserialize($user);
            $data['code'] = $user['code'];
            $data['auth'] = $user;
        }
        // 将FD传入内部
        $data['fd'] = $fd;
        try
        {
            // 将数据交由路由中心处理
            $this->router($data);
        }
        catch (\Exception $e)
        {
            echo date('[Y-m-d H:i:s] ERROR ').$e->getMessage().PHP_EOL;
            $this->app->sendMessage($fd,  500, 'controllerError', '程序错误');
        }
    }

    /**
     * 关闭连接处理
     * @param Server $server
     * @param $fd
     * @param $object
     * @return bool
     */
    public function onClose(Server $server, $fd)
    {
        // 获取redis对象
        $redis = \Cache::getRedis();
        // 获取当前FD的数据
        $data = $redis->hget($this->FdInfo, $fd);
        // 解码信息
        $data = empty($data)? [] : unserialize($data);
        // 日志查看数据
        // \Log::record($data);
        // 获取当前fd
        $data['fd'] = $fd;
        // 结束时通知各控制器
        $this->callControllerOnClose($redis, $data);
        // 断开连接，挨个删除数据
        $this->delFdAllData($redis, $data);
    }

    /**
     * http请求处理
     * @param Request $request
     * @param Response $response
     * @return none
     */
    public function onHttp(Request $request, Response $response)
    {
        $response->end("<h1>Hi guy!</h1>");
    }

    /**
     * 通知各个需要通知的控制器，fd连接关闭
     * @param $redis
     * @param array $data
     */
    private function callControllerOnClose($redis, Array $data=[])
    {
        // 获取当前fd意外断开需要通知的控制器集合
        $key = $this->FdController.$data['fd'];
        // redis查询
        $controller = $redis->sMembers($key);
        // 若不为空，则通知Object对象管理，进行onClose调用
        empty($controller) || Object::callControllerMethod($controller, 'onClose', [$this->app, $data]);
        // 调用完成，删除指定存储内容
        $redis->del($key);
    }

    /**
     * 删除Fd所有需要删除的数据，fd断开连接时调用
     * @param $redis
     * @param array $data
     */
    private function delFdAllData($redis, Array $data=[])
    {
        \Log::record($data);
        // 获取当前fd意外断开需要处理的key集合
        $key = $this->FdCloseSetKey.$data['fd'];
        // redis查询
        $keys = $redis->sMembers($key);
        // 若结果为array，则循环删除fd
        if (is_array($keys))
        {
            foreach ($keys as $v)
            {
                // 循环删除
                $redis->sRem($v, $data['fd']);
            }
        }
        // 调用完成，删除指定存储内容
        $redis->del($key);
        // 删除用户数据
        $redis->hDel($this->UidToFd, $data['id']);
        $redis->hDel($this->FdInfo, $data['fd']);
    }

    /**
     * 判断用户合法性,并建立用户对应关系
     * @param Request $request
     * @param none
     * @return none
     */
    private function checkRequest(Request $request)
    {
        $user = [];
        // 判断是否有请求参数
        if (isset($request->get))
        {
            // 请求连接时所提交的参数
            $param = $request->get;
            // 解码AUTH判断合法性，可设置有效期
            isset($param['auth']) && $user = $this->checkAtuh($param['auth']);
        }
        // 判断解码是否成功,用户合法性(不成功则关闭连接)
        if (empty($user))
        {
            return false;
        }
        // 增加FD
        $user['fd'] = $request->fd;
        // 获取redis对象
        $redis = \Cache::getRedis();
        // 当用户为微信用户时,判断是否已经有一个连接（暂不处理）

        // 保存用户信息 FD INFO
        $redis->hset($this->FdInfo, $user['fd'], $user['auth']);
        // 仅微信用户，建立uid to fd 的对应关系
        if ($user['type'] == 'wechatUser')
        {
            $redis->hset($this->UidToFd, $user['id'], $user['fd']);
        }
        // 返回用户信息
        return $user;
    }

    /**
     * 解码用户信息，查看用户是否合法
     * @param string $auth 身份加密串
     * @param int $outTime 身份过期时间
     * @return array
     */
    private function checkAtuh($auth='', $outTime=0)
    {
        if (empty($auth)) {return false;}
        // 解码,判断解码是否成功
        $data = \Encrypt::undes($auth, 'marico-websocket');
        if (empty($data)) {return false;}
        $data = json_decode($data, true);
//        // 判断是否为微信用户,非微信用户则制作唯一标示
//        if ($data['type'] != 'wechatUser')
//        {
//            $data['id'] = uniqid($data['type'].'_'.$data['id']).mt_rand(0, 10000);
//        }
        // 生成唯一标识并返回
        unset($data['ctime']);
        $data['auth'] = serialize($data);
        return $data;
    }

    /**
     * 路由处理,将请求投递给相应的控制器
     * @param mixed $data
     * @return mixed
     */
    private function router($data)
    {
        // 设置默认返回值
        $result = '服务端无返回值';
        // 进行数据过滤
        $this->dataFilter($data);
        // 准备空对象,默认数据
        $object = [];
        $addons = 'Index';
        $controller = 'Index';
        $method = 'index';
        // 进行数据解析
        isset($data['method']) || $data['method'] = 'index';
        $data['method'] = trim($data['method'], '.');
        $url = explode('.', $data['method']);
        unset($data['method']);
        // 判断URL请求层次
        switch (count($url))
        {
            case 1:
                $method = array_shift($url);
                break;
            case 2:
                $controller = array_shift($url);
                $method = array_shift($url);
                break;
            case 3:
                $addons = array_shift($url);
                $controller = array_shift($url);
                $method = array_shift($url);
                break;
            default:;
        }
        // 规整数据
        $addons = ucfirst(strtolower($addons));
        $controller = ucfirst(strtolower($controller));
        $method = strtolower($method);
        // 根据模块，控制器名称，获取控制器对象
        $object = Object::findController($addons, $controller);
        // 检查对象情况
        if (!is_object($object))
        {
            $this->app->sendMessage($data['fd'], 404, 'controllerNotFound', '您请求的控制器不存在');
        }
        // 检查对象情况，处理method，自动调用
        if (method_exists($object, $method))
        {
            call_user_func_array([$object, $method], [$this->app, $data]);
        }
        else if(method_exists($object, 'onMessage'))
        {
            call_user_func_array([$object, 'onMessage'], [$this->app, $data]);
        }
        else
        {
            $this->app->sendMessage($data['fd'], 405, 'methodNotFound', '您请求的method方法不存在');
        }
        return true;
    }

    /**
     * 请求参数安全过滤
     * @param $data
     */
    private function dataFilter(&$data)
    {
        // 判断是否为字符串
        if (is_string($data))
        {
            $data = htmlspecialchars($data);
        }
        // 判断是否为数组
        if (is_array($data))
        {
            foreach ($data as $key => &$value)
            {
                $this->dataFilter($value);
            }
        }
    }
}