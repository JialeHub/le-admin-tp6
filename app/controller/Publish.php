<?php
declare (strict_types=1);

namespace app\controller;

use api\index as Api;
use app\model\PublishFile as PublishFileModel;
use app\model\UserDept;
use \app\model\User;
use app\model\UserTokenAuth;
use think\Request;
use \app\model\Publish as PublishModel;
use utils\index as utils;

class Publish
{
    /**
     * 显示发布内容
     * 公开接口(无dataScope)：只显示公开(status===1;)及仅自己可见数据(create_id===userId;)
     * 权限接口(有dataScope)：显示全部数据(dataScope===0)，显示dataScope内
     * 公开接口及权限接口并联OR
     * 简化：若有权限
     *
     * @return \think\Response
     */
    public function index(Request $request)
    {
        $page = (int)$request->param('page', 1);//当前页
        $limit = (int)$request->param('limit', 10);//每页大小
        if ($limit === -1) $limit = 999999999;

        $utils = new utils();
        $sortListFile = [];
        $sortListUser = [];
        $sortListOri = $request->param('sortList', ['id'=>'desc']);//排序方式

        $sortList = $utils->uncamelizeArrKeys($sortListOri);
        foreach ($sortList as $k => $v) {
            $sub = explode('.', $k);
            if (count($sub) > 1) {
                switch ($sub[0]) {
                    case 'user':
                        $sortListUser[$sub[1]]=$v;
                        unset($sortList[$k]);
                        break;
                    case 'file':
                        $sortListFile[$sub[1]]=$v;
                        unset($sortList[$k]);
                        break;
                    default:
                        unset($sortList[$k]);
                        break;
                }
            }
        }

        $searchListOri = $request->param('searchList', []);//搜索列表
        $searchList = $utils->uncamelizeArrKeys($searchListOri);


        $map1 = ['status', '=', 1];
        $map2 = is_null(['user_id', $request->userId]) ? [] : ['user_id', '=', (int)$request->userId];
        //限制数据查询范围
        if (isset($request->authMenu->dataScope)) {
            //带权限
            $dataScope = $request->authMenu['data_scope'];
            $dataScopeArr = explode(',', $dataScope);
            $dataScopeArr = array_map(function ($v) {
                return (int)$v;
            }, $dataScopeArr);
            if ($dataScopeArr[0] === 0) {
                //全部数据域
                $count = PublishModel::where($searchList)->count();//总数据量
                $list = PublishModel::where($searchList)->page($page, $limit)->order($sortList)->with([
                    'file', 'user' => function ($q) {
                        $q->hidden(['password']);
                    }])->select();
            } else {
                $selectUserIds = UserDept::where('dept_id', 'in', $dataScopeArr)->column('user_id');
                $map3 = ['user_id', 'in', $selectUserIds];
                $count = PublishModel::where($searchList)->whereOr([$map1, $map2, $map3])->count();//总数据量
                $list = PublishModel::whereOr([$map1, $map2, $map3])->where($searchList)->page($page, $limit)->order($sortList)->with(['file', 'user' => function ($q) {
                    $q->field(['id', 'nickname', 'avatar']);
                }])->select();
            }
        } else {
            //不带权限，公共数据
            $count = PublishModel::whereOr([$map1, $map2])->where($searchList)->count();//总数据量
            $list = PublishModel::whereOr([$map1, $map2])->where($searchList)->page($page, $limit)->order($sortList)->with(['file', 'user' => function ($q) {
                $q->field(['id', 'nickname', 'avatar']);
            }])->select();
        }

        $data['data'] = null;
        $data['data']['list'] = $list;
        $data['data']['count'] = $count;
        $data['data']['page'] = $page;
        $data['data']['limit'] = $limit;
        $data['data']['sortList'] = $sortList;
        $data['data']['searchList'] = $searchList;
        $status = 200;
        $data['status'] = $status;
        $data['msg'] = '获取成功';
        return json($data, intval($status));
    }

    /**
     * 保存新建的资源
     *
     * @param \think\Request $request
     * @return \think\Response
     */
    public function save(Request $request)
    {
        $item = [];
        $item['user_id'] = $request->userId;
        $item['create_id'] = $request->userId;
        $item['create_id'] = $request->userId;
        if ($request->has('title', 'post')) $item['title'] = $request->param('title');
        if ($request->has('content', 'post')) $item['content'] = $request->param('content');
        if ($request->has('location', 'post')) $item['location'] = $request->param('location');
        if ($request->has('locationRes', 'post')) $item['location_res'] = $request->param('locationRes');
        if ($request->has('status', 'post')) $item['status'] = $request->param('status');
        //获取IP信息
        $api = new Api();
        $server = $request->server();
        $ip = isset($server['HTTP_X_FORWARDED_FOR']) ? $server['HTTP_X_FORWARDED_FOR'] :
            (isset($server['REMOTE_ADDR']) ? $server['REMOTE_ADDR'] : '');
        $ipInfo = $api->getIPInfo($ip);
        $item['ip'] = $ip;
        if (!is_null($ipInfo)) $item['ip_info'] = $ipInfo;
        if (!is_null($ipInfo) && is_array($ipInfo) && array_key_exists('addr', $ipInfo)) $item['ip_addr'] = $ipInfo['addr'];
        $publishModel = new PublishModel;
        $publishModel->save($item);

        //记录上传文件
        if ($request->has('fileIds', 'post')) {
            //优先级：fileIds(逗号隔开,string) > fileIds[](array)
            if (is_string($request->param('fileIds'))) {
                $fileIdsArr = explode(",", $request->param('fileIds'));
            } elseif (is_array($request->param('fileIds'))) {
                $fileIdsArr = $request->param('fileIds');
            } else {
                $fileIdsArr = [];
            }
            $publishFiles = [];
            foreach ($fileIdsArr as $fileId) {
                if (preg_match("/^[1-9][0-9]*$/", $fileId)) {
                    array_push($publishFiles, [
                        'publish_id' => $publishModel->id,
                        'file_id' => $fileId
                    ]);
                }
            }

            if (count($publishFiles) > 0) {
                $publishFilesModel = new PublishFileModel;
                $publishFilesModel->saveAll($publishFiles);
            }

        }


        $status = 200;
        $data['ip'] = $ipInfo;
        $data['status'] = $status;
        $data['msg'] = '提交成功';
        return json($data, intval($status));
    }

    /**
     * 显示指定的资源
     *
     * @param int $id
     * @return \think\Response
     */
    public function read($id)
    {
        //
    }

    /**
     * 保存更新的资源
     *
     * @param \think\Request $request
     * @param int $id
     * @return \think\Response
     */
    public function update(Request $request, $id)
    {
        $hasPermission = false;
        $publish = PublishModel::where('id',$id)->findOrEmpty();
        if ($publish->isEmpty()){
            $status = 401.1;
            $msg='找不到此记录';
        }else{
            if (isset($request->authMenu->dataScope)) {
                //带权限
                $dataScope = $request->authMenu['data_scope'];
                $dataScopeArr = explode(',', $dataScope);
                $dataScopeArr = array_map(function ($v) {
                    return (int)$v;
                }, $dataScopeArr);
                if ($dataScopeArr[0] === 0) {
                    //允许操作所有CreateId
                    $hasPermission=true;
                }else{
                    $createId = $publish->createId;
                    $userDepts = UserDept::where('user_id',$createId)->select();
                    foreach ($userDepts as $userDept){
                        if (in_array($userDept->dept_id,$dataScopeArr)){
                            $hasPermission=true;
                            break;
                        }
                    }
                }
            }
            if ($hasPermission){
                if ($request->has('score', 'post')) $publish['score'] = $request->param('score');
                $publish->save();
                $status = 200;
                $msg='更新成功';
            }else{
                $status = 403.1;
                $msg='权限不足，无法操作该记录';
            }
        }

        $data['status'] = $status;
        $data['msg'] = $msg;
        return json($data, intval($status));
    }

    /**
     * 删除指定资源
     *
     * @param int $id
     * @return \think\Response
     */
    public function delete($id)
    {
        PublishModel::findOrEmpty($id)->delete();
        $status = 200;
        $data['status'] = $status;
        $data['msg'] = '删除成功';
        return json($data, intval($status));
    }
}
