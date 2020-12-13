<?php

namespace app\controller;

use app\BaseController;
use app\model\Log;
use app\model\Menu;

class Index extends BaseController
{
    public function index()
    {
        \app\model\Dept::find(1)->delete();
        $status = request()->status === 501 ? 200 : request()->status;
        $data['middle'] = request()->middleMsg;
        $data['status'] = $status;
        return json($data,intval($status)) ;
    }

    public function hello($name = 'ThinkPHP6')
    {
        return 'hello,' . $name;
    }

    public function log()
    {
        return json(Log::page(1,10)->select());
    }

}
