<?php


namespace app\controller;


class Error
{
    public function index()
    {
        $status = 501;
        $data['msg'] = '错误';
        $data['status'] = 501;
        return json($data,intval($status)) ;
    }

    public function notFound()
    {
        $data['msg'] = '未定义当前请求';
        $data['status'] = $status = 404;
        return json($data,intval($status));
    }

    public function forbid()
    {
        $data['msg'] = '权限不足，拒绝访问';
        $data['status'] = $status = 403;
        return json($data,intval($status));
    }

    public function expire()
    {
        $data['msg'] = '登陆状态已过期，请重新登录';
        $data['status'] = $status = 401;
        return json($data,intval($status));
    }

    public function methodError($allow='')
    {
        $data['msg'] = '不支持该请求方法';
        $data['allow'] = $allow;
        $data['status'] = $status = 405;
        return json($data,intval($status),['Allow' => $allow]);
    }

    public function jsonMethod($allow='')
    {
        if (request()->isJson()){
            $data['msg'] = '不支持该请求方法';
            $data['allow'] = $allow;
            $data['status'] = $status = 405;
            return json($data,intval($status),['Allow' => $allow]);
        }else{
            $data['msg'] = '请求方式异常';
            $data['accept'] = 'application/json';
            $data['status'] = $status = 405;
            return json($data,intval($status));
        }
    }

}
