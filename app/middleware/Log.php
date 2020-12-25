<?php
declare (strict_types=1);

namespace app\middleware;

use api\index as Api;
use \app\model\Log as LogModel;
use app\model\UserToken;

class Log
{
    /**
     * 处理请求
     *
     * @param \think\Request $request
     * @param \Closure $next
     * @return \think\response\Json
     */
    public function handle($request, \Closure $next)
    {
        //请求拦截，前置中间件
        $t1 = microtime(true); //获取请求开始时间

        $request->status = 501; //设置默认状态码
        $request->middleMsg = 'Log';

        $server = $request->server();
        $query = [
            'domain' => $request->domain(),//包含协议的请求域名
            'url' => $request->url(),//当前请求完整URL
            'method' => $request->method(),//请求方法
            'type' => $request->type(),//当前请求的资源类型
            'time' => date('Y-m-d H:i:s', $request->time()),//发起请求的时间
            'protocol' => $request->protocol(),// 当前请求的SERVER_PROTOCOL
            'remote_address' => isset($server['REMOTE_ADDR']) ? $server['REMOTE_ADDR'] : '',// 请求/代理地址
            'x_forwarded_for' => isset($server['HTTP_X_FORWARDED_FOR']) ? $server['HTTP_X_FORWARDED_FOR'] : '',// 多重转发的地址(真实地址)
            'request_accept' => isset($server['HTTP_ACCEPT']) ? $server['HTTP_ACCEPT'] : '',// 当前请求的HTTP_ACCEPT
            'request_accept_encoding' => isset($server['HTTP_ACCEPT_ENCODING']) ? $server['HTTP_ACCEPT_ENCODING'] : '',// 编码方式
            'request_accept_language' => isset($server['HTTP_ACCEPT_LANGUAGE']) ? $server['HTTP_ACCEPT_LANGUAGE'] : '',// 当前请求的语言
            'request_connection' => isset($server['HTTP_CONNECTION']) ? $server['HTTP_CONNECTION'] : '',// 连接
            'request_content_length' => isset($server['CONTENT_LENGTH']) ? $server['CONTENT_LENGTH'] : '',// 长度
            'request_referer' => isset($server['HTTP_REFERER']) ? $server['HTTP_REFERER'] : '',// 客户端当前访问前端页面URL
            'request_user_agent' => isset($server['HTTP_USER_AGENT']) ? $server['HTTP_USER_AGENT'] : '',// 当前请求的设备信息
            'request_content_type' => $request->contentType(),// 当前请求的CONTENT_TYPE
        ];

        $res = $next($request);  //分界
        //后置中间件

        //保存日志
        $logModel = new LogModel();
        $query['status'] = isset(((array)$res)["\0*\0data"]['status']) ? ((array)$res)["\0*\0data"]['status'] : null;// 状态码
        $query['session_id'] = $request->session_id;
        $query['authorization'] = $request->token;
        $query['user_id'] = $request->userId;

        $t2 = microtime(true); //获取请求结束时间
        $query['duration'] = $t2 - $t1;
        //获取IP信息
        $ip = isset($server['HTTP_X_FORWARDED_FOR']) ? $server['HTTP_X_FORWARDED_FOR'] :
            (isset($server['REMOTE_ADDR']) ? $server['REMOTE_ADDR'] : '');
        $api = new Api();
        $ipInfo = $api->getIPInfo($ip);
        if (!is_null($ipInfo)) $query['ip_info'] = $ipInfo;
        if (!is_null($ipInfo) && is_array($ipInfo) && array_key_exists('addr',$ipInfo)) $query['ip_addr'] = $ipInfo['addr'];
        $logModel->save($query);

        return $res;
    }

    public function end(\think\Response $response)
    {
        // 回调行为
    }
}
