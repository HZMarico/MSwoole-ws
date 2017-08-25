<?php
/**
 * Created by PhpStorm.
 * User: marico
 * Date: 2017/8/15
 * Time: 下午5:04
 */

namespace App\Live\Controller;


class Common
{
    // 存储REDIS键前缀，用户群体
    protected $userRoom = 'LIVE_USER_ROOM_';
    // 存储REDIS键前缀，后台群体
    protected $wallRoom = 'LIVE_WALL_ROOM_';
    // 存储REDIS键前缀，用户消息缓存
    protected $userMessage = 'LIVE_USER_MESSAGE_';
    // 存储REDIS键前缀，用户消息缓存
    protected $WallMessage = 'LIVE_WALL_MESSAGE_';
}