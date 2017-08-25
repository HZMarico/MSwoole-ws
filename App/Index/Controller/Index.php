<?php
/**
 * Created by PhpStorm.
 * User: marico
 * Date: 2017/8/16
 * Time: 上午11:59
 */

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