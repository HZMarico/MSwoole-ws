<?php
/**
 * Created by PhpStorm.
 * User: marico
 * Date: 2017/8/15
 * Time: 下午2:32
 */

namespace App\Live\Controller;

use MSwoole\App;
use App\Live\Server\Name;

class User extends Common
{

    /**
     * 用户加入
     * @param App $app
     * @param array $data
     * @return mixed | null
     */
    public function join(App $app, Array $data=[])
    {
        // 仅允许微信用户连入
        if ($data['auth']['type'] != 'wechatUser')
        {
            $app->sendMessage($data['fd'], 403, 'mustBeWechatUser', '仅允许微信用户连入');
        }
        // 判断是否存在code
        empty($data['code'])
            && $app->sendMessage($data['fd'], 999, 'codeIsEmpty', 'code不能为空');
        // 获取redis对象
        $redis = \Cache::getRedis();
        // 组合key值
        $key = Name::getUserRoomKey($data['code']);
        // 存入队伍
        $redis->sAdd($key, $data['fd']);
        // 增加当前用户断开时需要销毁的键
        // $app->fdAddCloseKey($data['fd'], $key);
        // 增加用户离开时需要通知的控制器
        $app->fdAddController($data['fd'], 'Live.User');
        // 返回加入成功
        $app->sendSuccessMessage($data['fd'], 'userJoinSuccess', '加入房间成功');
    }

    /**
     * 用户退出
     * @param App $app
     * @param array $data
     * @return mixed | null
     */
    public function out(App $app, Array $data=[])
    {
        // 判断是否存在code
        empty($data['code'])
            && $app->sendMessage($data['fd'], 999, 'codeIsEmpty', 'code不能为空');
        // 获取redis对象
        $redis = \Cache::getRedis();
        // 组合key值
        $key = Name::getUserRoomKey($data['code']);
        // 退出队伍
        $redis->sRem($key, $data['fd']);
        // 删除当前用户断开时需要销毁的键
        // $app->fdRemoveCloseKey($data['fd'], $key);
        // 删除用户离开时需要通知的控制器
        $app->fdRemoveController($data['fd'], 'Live.User');
        // 返回退出成功
        $app->sendSuccessMessage($data['fd'], 'userOutSuccess', '退出房间成功');
    }

    /**
     * 用户加入
     * @param App $app
     * @param array $data
     * @return mixed | null
     */
    public function say(App $app, Array $data=[])
    {
        // 仅允许微信用户连入
        if ($data['auth']['type'] != 'wechatUser')
        {
            $app->sendMessage($data['fd'], 403, 'mustBeWechatUser', '仅允许微信用户连入');
        }
        // 获取redis对象
        $redis = \Cache::getRedis();
        // 组合key值
        $key = Name::getUserMessageKey($data['code']);
        // 生成消息唯一标识
        $id = md5($data['say'].uniqid($data['fd']));
        // 消息数据
        $message = [
            'id' => $id,
            'nickname' => $data['auth']['nickname'],
            'say' => $data['say']
        ];
        // 存入待审核队列
        $redis->hSet($key, $id, serialize($message));
        // 获取大屏幕key
        $key = Name::getWallRoomKey($data['code']);
        // 通知大屏幕
        $app->sendSuccessMessage($key, 'userSayNeedCheck', '收到用户广播内容', $message);
        // 回复当前用户
        $app->sendSuccessMessage($data['fd'], 'userSaySuccess', '发布聊天内容成功，等待审核', $message['id']);
    }

    /**
     * 查询历史信息
     * @param App $app
     * @param array $data
     */
    public function history(App $app, Array $data=[])
    {
        // 规整数据
        isset($data['page']) || $data['page'] = 1;
        isset($data['count']) || $data['count'] = 10;
        $data['count'] = min($data['count'], 20);
        // 查询条件
        $where = ['status' => 1];
        // 查询数据库
        $list = WallModel::pageList($where, $data['page'], $data['count'], '`id` DESC');
        // 返回给大屏幕数据
        $app->sendSuccessMessage($data['fd'], 'wallSayHistory', '获取历史成功', $list);
    }

    /**
     * 用户中途退出操作
     * @param App $app
     * @param Array $data
     * @return null | mixed
     */
    public function onClose(App $app, Array $data=[])
    {
        // 获取redis对象
        $redis = \Cache::getRedis();
        // 组合key值
        $key = Name::getUserRoomKey($data['code']);
        // 退出队伍
        $redis->sRem($key, $data['fd']);
    }
}