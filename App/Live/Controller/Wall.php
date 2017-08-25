<?php
/**
 * Created by PhpStorm.
 * User: marico
 * Date: 2017/8/15
 * Time: 下午2:32
 */

namespace App\Live\Controller;

use MSwoole\App;
use App\Live\Model\Wall as WallModel;
use App\Live\Server\Name;

class Wall extends Common
{
    /**
     * 大屏幕加入
     * @param App $app
     * @param array $data
     * @return mixed | null
     */
    public function join(App $app, Array $data=[])
    {
        // 仅允许微信用户连入
        if ($data['auth']['type'] != 'wall')
        {
            $app->sendMessage($data['fd'], 403, 'mustBeWall', '仅允许大屏幕用户连入');
        }
        // 判断是否存在code
        empty($data['code'])
            && $app->sendMessage($data['fd'], 999, 'codeIsEmpty', 'code不能为空');
        // 获取redis对象
        $redis = \Cache::getRedis();
        // 组合key值
        $key = Name::getWallRoomKey($data['code']);
        // 存入队伍
        $redis->sAdd($key, $data['fd']);
        // 增加当前用户断开时需要销毁的键
        $app->fdAddCloseKey($data['fd'], $key);
        // 增加用户离开时需要通知的控制器
        // $app->fdAddController($data['fd'], 'Live.Wall');
        // 返回加入成功
        $app->sendSuccessMessage($data['fd'], 'wallJoinSuccess', '加入房间成功');
    }

    /**
     * 大屏幕退出
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
        $key = Name::getWallRoomKey($data['code']);
        // 退出队伍
        $redis->sRem($key, $data['fd']);
        // 删除当前用户断开时需要销毁的键
        $app->fdRemoveCloseKey($data['fd'], $key);
        // 删除用户离开时需要通知的控制器
        // $app->fdRemoveController($data['fd'], 'Live.Wall');
        // 返回退出成功
        $app->sendSuccessMessage($data['fd'], 'wallOutSuccess', '退出房间成功');
    }

    /**
     * 大屏幕广播内容
     * @param App $app
     * @param array $data
     * @return mixed | null
     */
    public function say(App $app, Array $data=[])
    {
        // 准备存入数据库数据
        $message = [
            'code' => $data['code'],
            'nickname' => $data['auth']['nickname'],
            'say' => $data['say'],
            'user_id' => $data['auth']['id'],
        ];
        // 将说话内容保存至数据库
        $wall = WallModel::create($message);
        // 判断保存结果，保存失败，返回数据
        if (!is_object($wall))
        {
            return $app->sendMessage($data['fd'], 501, 'insertDbError', '保存数据至数据库失败');
        }
        // 提取id
        $message['id'] = $wall->id;
        // 释放对象
        unset($wall);
        // 返回给大屏幕数据及标识
        $app->sendSuccessMessage($data['fd'], 'wallSaySuccess', '广播成功', $message);
        // 获取客户端的redis集合Key
        $key = Name::getUserRoomKey($data['code']);
        // 通知所有连入的客户端
        $app->sendSuccessMessage($key, 'wallSayBroadcast', '收到大屏幕广播内容', $message);
    }

    /**
     * 大屏幕撤回内容
     * @param App $app
     * @param array $data
     * @return mixed | null
     */
    public function recall(App $app, Array $data=[])
    {
        // 修改数据库状态
        $result = WallModel::update([
            'status' => 0
        ], [
            'id' => $data['id'],
            'code' => $data['code'],
        ]);
        // 判断修改结果
        $result === false && $app->sendMessage($data['fd'], 403, 'wallRecallFail', '撤回广播失败');
        // 获取客户端的redis集合Key
        $key = Name::getUserRoomKey($data['code']);
        // 进行广播，撤回
        $app->sendSuccessMessage($key, 'wallSayRecall', '撤回一条大屏幕消息', $data['id']);
        // 通知大屏幕完成撤回
        $app->sendSuccessMessage($data['fd'], 'wallRecallSuccess', '撤回广播成功', $data['id']);
    }

    /**
     * 大屏幕审核弹幕内容
     * @param App $app
     * @param array $data
     * @return mixed | null
     */
    public function check(App $app, Array $data=[])
    {
        // 获取redis对象
        $redis = \Cache::getRedis();
        // 组合key
        $key = Name::getUserMessageKey($data['code']);
        // 判断是否为删除
        if (empty($data['status']))
        {
            // 清除哈希结果
            $redis->hDel($key, $data['id']);
        }
        else
        {
            // 查询内容
            $message = $redis->hGet($key, $data['id']);
            // 判断查询结果
            if (!empty($message))
            {
                // 清除哈希结果
                $redis->hDel($key, $data['id']);
                // 解码数据
                $message = unserialize($message);
                // 获取客户端redis集合key
                $key = Name::getUserRoomKey($data['code']);
                // 进行广播
                $app->sendSuccessMessage($key, 'userSayBroadcast', '用户消息审核通过', $message);
            }
        }
        // 返回数据
        $app->sendSuccessMessage($data['fd'], 'wallCheckSuccess', '审核消息成功', $data['id']);
    }

    /**
     * 大屏幕清除弹幕队列
     * @param App $app
     * @param array $data
     * @return mixed | null
     */
    public function clean(App $app, Array $data=[])
    {
        // 获取redis对象
        $redis = \Cache::getRedis();
        // 组合key值
        $key = Name::getUserMessageKey($data['code']);
        // 清除数据
        $redis->del($key);
    }

    /**
     * 查询历史信息
     * @param App $app
     * @param array $data
     */
    public function history(App $app, Array $data=[])
    {
        // 判断获取类型
        if ($data['type'] == 'wall')
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
        else if($data['type'] == 'user')
        {
            // 获取redis 对象
            $redis = \Cache::getRedis();
            // 组合key值
            $key = Name::getUserMessageKey($data['code']);
            // 查询内容
            $list = $redis->hGetAll($key);
            // 规整数据
            empty($list) && $list = [];
            // 循环解码
            foreach ($list as &$v)
            {
                $v = unserialize($v);
            }
            // 返回给大屏幕
            $app->sendSuccessMessage($data['fd'], 'userSayHistory', '获取消息历史成功', $list);
        }
        else
        {
            // 返回给大屏幕数据
            $app->sendMessage($data['fd'], 401, 'typeError', '类型错误');
        }

    }
}