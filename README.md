# Websocket服务
---

# 整体概述
```text
Websocket模式与Log平台模式略有不同
服务配置文件位于Config/app.php
其中setting内容为Swoole服务配置
manager配置管理端口
outside配置对外服务端口
......
整体服务由Nginx根据进行数据转发，80转发至服务对外端口
```

## 框架源码
```text
project                     应用部署目录
├─App                       控制器目录
│  ├─Common                 公共模块
│  ├─Index                  默认模块
|  |  ├─Controller          模块控制器
|  |  ├─Model               模块数据模型
|  |  └─Server              模块服务层
│  ├─Live                   直播模块
│  └─...                    其余模块
├─Config                    配置文件目录
│  ├─db.php                 数据库配置文件
│  ├─app.php                swoole服务配置文件
│  └─cache.php              redis配置文件
├─Library                   基础类目录
│  ├─DB                     数据库基础类库
│  ├─Cache.php              缓存处理对象
│  ├─Config.php             配置文件读取对象
│  ├─DB.php                 DB数据库对象
│  ├─Encrypt.php            加密解密对象
│  ├─Log.php                日志处理对象
│  ├─Model.php              Model数据对象
|  └─...                    其他类库
├─MSwoole                   框架核心内容
│  ├─App.php                框架核心对象，server存入属性
│  ├─Console.php            控制台文件，负责启动热重启类消息通知
│  ├─Handler.php            负责框架自动加载和错误处理
│  ├─Manager.php            负责处理Server Manager端口消息处理
│  ├─Object.php             负责处理控制器对象，统一通知控制器
|  └─Outside.php            负责对外消息、handshake握手处理
├─Public
│  └─console.php            控制台脚本，支持start|reload|stop|status
├─Runtime                   日志、缓存文件存放处
```
## Websocket建立连接

js客户端代码示例：

```javascript
var server = 'ws://socket.kfw001.com/live?auth=xxx';
var websocket = new WebSocket(server);
// 服务连接事件
websocket.onopen = function (evt) {
	// 处理连接建立后的事件
};
// 监听服务端推送信息
websocket.onmessage = function (evt) {
	// 服务端返回JSON格式数据
    // 数据示例：
    // {"status":"状态码", "info":"提示信息", "param":"数据", "event":"触发事件"}
};
// 监听连接错误信息
websocket.onerror = function (evt, e) {
};
// 监听关闭信息
websocket.onclose = function (evt) {
	// 断线重连
};
/**data-json格式**/
var data = {
	"method" : "操作",
    // …… 其他键值
};
// 向服务端发送数据
websocket.send(JSON.stringify(data));
```

## 服务端返回码

| 返回键 | 类型 | 说明 | 备注 |
| -------- | -----:  | :----:  | :----:  |
| status | Int | 状态 | 状态码 |
| info | String | 提示信息 |  |
| param | Mixed | 参数 |  |
| event | String | 事件 |  |

## Auth规范

> auth内包含id用户标识，type用户类型，code房间编码，nickname用户昵称，ctime加密串时间戳；使用**\Encrypt::undes($auth, 'kfw001-websocket')**解码Auth。
> 微信用户类型为：wechatUser
> 大屏幕用户为：wall

## 注意事项
> Websocket建立TCP连接，保持连接需在每分钟内进行信息交互，否则服务端会主动切断Websocket连接。客户端连入服务端，需要携带Auth加密参数，否则返回403（用户权限不足）错误。
> 因各项原因，客户端断开概率较高，需有效处理客户端断开事件，App对象提供两种解决方案:
> 1. 通过触发顶层onClose时，删除fd所在的redis集合。
> 2. 通过触发顶层onClose时，触发对应控制器onClose方法，单独进行处理。
> 代码样例

```php
// 组合key值
$key = Name::getUserRoomKey($data['code']);
// 增加当前用户断开时需要销毁的键
$app->fdAddCloseKey($data['fd'], $key);
// 增加用户离开时需要通知的控制器
$app->fdAddController($data['fd'], 'Live.User');
----
// 删除当前用户断开时需要销毁的键
$app->fdRemoveCloseKey($data['fd'], $key);
// 删除用户离开时需要通知的控制器
$app->fdRemoveController($data['fd'], 'Live.User');
```

---

# 样例

## 控制器样例
> 样例内容为Index.Index.Index

```php
namespace App\Index\Controller;
use MSwoole\App;

class Index
{
    /**
     * 默认控制器，默认方法
     * @param App $app
     * @param array $data
     * @return none
     */
    public function index(App $app, Array $data=[])
    {
        // 返回数据
        $app->sendSuccessMessage($data['fd'], 'success', '执行完成');
    }
}
```

## 测试样例
> 测试脚本
```php
use Swoole\Http\Client;
// 用户加密信息
$auth = ';
// 处理url
$url = '/?auth='.$auth;
// 请求参数
$data = [
    'method' => 'index.index.index',
    'name' => 'hello'
];
// 客户端
$client = new Client('127.0.0.1', 20000, false);
// 监听消息
$client->on('message', function (Client $client, $frame) {
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
```

---

## Nginx 配置
```
server {
        listen       80;
        server_name  server_name.com;

        location / {
            proxy_pass http://127.0.0.1:20000;
            proxy_redirect off;
            proxy_http_version 1.1;
            proxy_set_header Upgrade $http_upgrade;
            proxy_set_header Connection "upgrade";
            proxy_set_header X-real-ip $remote_addr;
            proxy_set_header APP_ID MSwoole;
        }
}
```


## 服务管理
```
控制台文件
Public/console.php

启动服务:php console.php -s start
热重启:php console.php -s reload
停止服务:php console.php -s stop
服务状态:php console.php -s status
```

## License

Apache License Version 2.0 see http://www.apache.org/licenses/LICENSE-2.0.html