<?php
namespace App\Live\Model;
/**
 * Created by PhpStorm.
 * User: marico
 * Date: 2017/8/15
 * Time: 下午1:35
 */
class Wall extends \Model
{
    // 数据库主键
    protected $pk = 'id';
    // 数据表名称(不含前缀)
    protected $name = 'wall_message';
    // 数据表前缀
    protected $prefix = 'live_';
    // 开启自动写入时间
    protected $autoWriteTimestamp = true;
}