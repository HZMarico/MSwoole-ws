<?php
/**
 * Created by PhpStorm.
 * User: marico
 * Date: 2017/8/17
 * Time: 上午8:53
 */

namespace App\Index\Model;


class Test extends \Model
{
    // 数据库配置
    protected $connection = [];
    // 数据库主键
    protected $pk = 'id';
    // 数据表名称(不含前缀)
    protected $name = 'user_list';
    // 数据表前缀
    protected $prefix = 'test_';
    // 开启自动写入时间
    protected $autoWriteTimestamp = false;
}