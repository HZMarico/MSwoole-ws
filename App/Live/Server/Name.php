<?php
/**
 * Created by PhpStorm.
 * User: marico
 * Date: 2017/8/19
 * Time: 下午3:06
 */

namespace App\Live\Server;


class Name
{
    // 存储REDIS键前缀，用户群体
    protected static $userRoom = 'LIVE_USER_ROOM_';
    // 存储REDIS键前缀，后台群体
    protected static $wallRoom = 'LIVE_WALL_ROOM_';
    // 存储REDIS键前缀，用户消息缓存
    protected static $userMessage = 'LIVE_USER_MESSAGE_';
    // 存储REDIS键前缀，大屏幕消息缓存
    protected static $WallMessage = 'LIVE_WALL_MESSAGE_';

    /**
     * 获取用户房间key
     * @param String $code
     * @param none
     * @return String
     */
    public static function getUserRoomKey($code='')
    {
        return self::$userRoom.$code;
    }

    /**
     * 获取大屏幕房间Key
     * @param String $code
     * @param none
     * @return String
     */
    public static function getWallRoomKey($code='')
    {
        return self::$userRoom.$code;
    }

    /**
     * 获取用户消息Key
     * @param String $code
     * @param none
     * @return String
     */
    public static function getUserMessageKey($code='')
    {
        return self::$userRoom.$code;
    }
}