<?php
/**
 * Created by PhpStorm.
 * User: marico
 * Date: 2017/8/15
 * Time: 上午11:42
 */
namespace MSwoole;
use Swoole\Http\Client;

class Console
{
    // 通讯地址
    private $host = '';
    // 通讯端口
    private $port = 0;
    // 可操作的指令
    private $method = ['start', 'stop', 'reload', 'status', 'say'];

    /**
     * console constructor. 实例化对象
     * @param array $data
     */
    public function __construct(Array $data=[])
    {
        // 若传入h、p参数，则进行替换
        isset($data['h']) && $this->host = $data['h'];
        isset($data['p']) && $this->port = $data['p'];
        // 判断s参数，操作内容
        isset($data['s']) || die("缺少s指令，s指令用于指明具体操作\n");
        in_array($data['s'], $this->method) || die("s指令仅可用start|stop|reload\n");
        // 调用当前对象对应function
        call_user_func([$this, $data['s']], $data);
    }

    /**
     * 服务启动
     * @param none
     * @param none
     * @return none
     */
    public function start()
    {
        // 导入初始化文件
        $this->includeFiles();
        // 注册自动加载规则
        spl_autoload_register(['\MSwoole\Handler', 'autoload']);
        // 注册错误
        set_error_handler(['\MSwoole\Handler', 'appError']);
        // 注册throw
        set_exception_handler(['\MSwoole\Handler', 'appException']);
        // 注册意外终止
        // register_shutdown_function();
        // 获取服务配置文件
        $data = include APP_PATH.'/Config/app.php';
        // 获取APP单例模型
        App::getInstance($data);
    }

    /**
     * 服务热重启
     * @param none
     * @param none
     * @return none
     */
    public function reload()
    {
        // 准备参数
        $data = [
            'method' => 'reload'
        ];
        // 提交请求
        $this->sendMessage($data);
    }

    /**
     * 服务停止
     * @param none
     * @param none
     * @return none
     */
    public function stop()
    {
        // 准备参数
        $data = [
            'method' => 'stop'
        ];
        // 提交请求
        $this->sendMessage($data);
    }

    /**
     * 获取状态
     * @param none
     * @param none
     * @return none
     */
    public function status()
    {
        // 准备参数
        $data = [
            'method' => 'status'
        ];
        // 提交请求
        $this->sendMessage($data);
    }

    /**
     * 广播内容
     * @param array $param
     * @param none
     * @return none
     */
    public function say($param=[])
    {
        // 检查参数
        isset($param['i']) || die('缺少i参数'.PHP_EOL);
        // 准备参数
        $data = [
            'method' => 'say',
            'say' => $param['i']
        ];
        // 提交请求
        $this->sendMessage($data);
    }

    /**
     * 像websocket服务发送消息
     * @param array $data
     * @param none
     * @return none
     */
    private function sendMessage(Array $data=[])
    {
        // 判断是否为空，目标地址和端口
        if (empty($this->host) || empty($this->port))
        {
            // 读取配置文件
            $config = include APP_PATH.'/Config/app.php';
            // 进行赋值
            empty($this->host) && $this->host = $config['manager']['host'];
            // 进行赋值
            empty($this->port) && $this->port = $config['manager']['port'];
        }
        // 实例化客户端
        $client = new Client($this->host, $this->port, false);
        // 绑定消息监听
        $client->on('message', function (Client $client, $frame) {
            echo $frame->data.PHP_EOL;
            $client->close();
        });
        // 升级为websocket连接
        $client->upgrade('/', function (Client $client) use ($data) {
            if ($client->isConnected()) {
                $client->push(json_encode($data));
            } else {
                echo '连接服务失败'.PHP_EOL;
            }
        });
    }

    /**
     * 获取初始化所需的所有文件
     * @param none
     * @param none
     * @return none
     */
    private function includeFiles()
    {
        // 导入Handler.php
        include 'Handler.php';
    }
}