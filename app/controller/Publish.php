<?php
declare (strict_types=1);

namespace app\controller;

use api\index as Api;
use app\model\PublishFile as PublishFileModel;
use app\model\UserDept;
use \app\model\User as UserModel;
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
        $sortListOri = $request->param('sortList', [['id' => 'desc']]);//排序方式
        $sortList = [];
        foreach ($sortListOri as $item){
            if (is_array($item)){
                $sortList=array_merge($sortList,$utils->uncamelizeArrKeys($item));
            }elseif (is_string($item)){
                $sortList=array_merge($sortList,$utils->uncamelize($sortListOri));
            }
        }
        foreach ($sortList as $k => $v) {
            $sub = explode('.', $k);
            if (count($sub) > 1) {
                switch ($sub[0]) {
                    case 'user':
                        $sortListUser[$sub[1]] = $v;
                        unset($sortList[$k]);
                        break;
                    case 'file':
                        $sortListFile[$sub[1]] = $v;
                        unset($sortList[$k]);
                        break;
                    default:
                        unset($sortList[$k]);
                        break;
                }
            }
        }

        $searchListOri = $request->param('searchList', []);//搜索列表
        $searchList = [];
        $searchListUser = [];
        foreach ($searchListOri as $item){
            $tmpArray = explode('.',$item[0]);
            if ( count($tmpArray)<=1){
                $item[0] = $utils->uncamelize($item[0]);
                array_push($searchList,$item) ;
            }elseif($tmpArray[0]==='user'){
                $item[0] = $utils->uncamelize($tmpArray[1]);
                array_push($searchListUser,$item) ;
            }
        }


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
                if(count($searchListUser)>0){
                    //含有user的搜索
                    $userIds = UserModel::where($searchListUser)->column('id');
                    array_push($searchList,['user_id','in',$userIds]);
                }
                $count = PublishModel::where($searchList)->count();//总数据量
                $scoreNull = PublishModel::where($searchList)->where('score', '=','-1')->count();//未评数
                $resultScore =PublishModel::where($searchList)->where('score', '>=', 0);
                $scoreMin = $resultScore->min('score');//最低分
                $scoreMax = $resultScore->max('score');//最高分
                $scoreSum = $resultScore->sum('score');//总分
                $scoreAvg = $resultScore->avg('score');//平均分
                $list = PublishModel::where($searchList)->page($page, $limit)->order($sortList)->with([
                    'file', 'user' => function ($q) {
                        $q->hidden(['password']);
                    }])->select();
            } else {
                if(count($searchListUser)>0){
                    //含有user的搜索
                    $userIds = UserModel::where($searchListUser)->column('id');
                    array_push($searchList,['user_id','in',$userIds]);
                }
                $selectUserIds = UserDept::where('dept_id', 'in', $dataScopeArr)->column('user_id');
                $map3 = ['user_id', 'in', $selectUserIds];
                $count = PublishModel::where($searchList)->whereOr([$map1, $map2, $map3])->count();//总数据量
                $scoreNull = PublishModel::where($searchList)->whereOr([$map1, $map2, $map3])->where('score', '=','-1')->count();//未评数
                $resultScore =PublishModel::where($searchList)->whereOr([$map1, $map2, $map3])->where('score', '>=', 0);
                $scoreMin = $resultScore->min('score');//最低分
                $scoreMax = $resultScore->max('score');//最高分
                $scoreSum = $resultScore->sum('score');//总分
                $scoreAvg = $resultScore->avg('score');//平均分
                $list = PublishModel::whereOr([$map1, $map2, $map3])->where($searchList)->page($page, $limit)->order($sortList)->with(['file', 'user' => function ($q) {
                    $q->field(['id', 'nickname', 'avatar']);
                }])->select();
            }
        } else {
            //不带权限，公共数据
            if(count($searchListUser)>0){
                //含有user的搜索
                $userIds = UserModel::where($searchListUser)->column('id');
                array_push($searchList,['user_id','in',$userIds]);
            }
            $count = PublishModel::whereOr([$map1, $map2])->where($searchList)->count();//总数据量
            $scoreNull = PublishModel::where($searchList)->whereOr([$map1, $map2])->where('score', '=','-1')->count();//未评数
            $resultScore =PublishModel::where($searchList)->whereOr([$map1, $map2])->where('score', '>=', 0);
            $scoreMin = $resultScore->min('score');//最低分
            $scoreMax = $resultScore->max('score');//最高分
            $scoreSum = $resultScore->sum('score');//总分
            $scoreAvg = $resultScore->avg('score');//平均分
            $list = PublishModel::whereOr([$map1, $map2])->where($searchList)->page($page, $limit)->order($sortList)->with(['file', 'user' => function ($q) {
                $q->field(['id', 'nickname', 'avatar']);
            }])->select();
        }

        $data['data'] = null;
        $data['data']['score'] = null;
        $data['data']['score']['min'] = $scoreMin;
        $data['data']['score']['max'] = $scoreMax;
        $data['data']['score']['avg'] = $scoreAvg;
        $data['data']['score']['sum'] = $scoreSum;
        $data['data']['score']['none'] = $scoreNull;
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

    /*
     * 汇总用户分数：用户->发表
     *
     *
     * */
    public function collect(Request $request){
        $page = (int)$request->param('page', 1);//当前页
        $limit = (int)$request->param('limit', 10);//每页大小
        if ($limit === -1) $limit = 999999999;

        $utils = new utils();
        $sortListOri = $request->param('sortList', [['id' => 'desc']]);//排序方式
        $sortList = [];
        $sortListPublish = [];
        foreach ($sortListOri as $item){
            if (is_array($item)){
                $sortList=array_merge($sortList,$utils->uncamelizeArrKeys($item));
            }elseif (is_string($item)){
                $sortList=array_merge($sortList,$utils->uncamelize($sortListOri));
            }
        }
        foreach ($sortList as $k => $v) {
            $sub = explode('.', $k);
            if (count($sub) > 1) {
                switch ($sub[0]) {
                    case 'publish':
                        $sortListPublish[$sub[1]] = $v;
                        unset($sortList[$k]);
                        break;
                    default:
                        unset($sortList[$k]);
                        break;
                }
            }
        }

        $searchListOri = $request->param('searchList', []);//搜索列表
        $searchList = [];
        $searchListPublish = [];
        foreach ($searchListOri as $item){
            $tmpArray = explode('.',$item[0]);
            if ( count($tmpArray)<=1){
                $item[0] = $utils->uncamelize($item[0]);
                array_push($searchList,$item) ;
            }elseif($tmpArray[0]==='publish'){
                $item[0] = $utils->uncamelize($tmpArray[1]);
                array_push($searchListPublish,$item) ;
            }
        }

        $count = UserModel::where($searchList)->where('username','<>','root')->count();
        $users = UserModel::where($searchList)->where('username','<>','root')->hidden(['password','deleteTime','deleteId'])
            ->withSum(['publish'=>function($query, &$alias) use ($searchListPublish){
            $alias = 'score_sum';
            $query->field('id,user_id,score,create_time,update_time')->where($searchListPublish)->where([['score','>=',0]])->select();
        }],'score')->withMax(['publish'=>function($query, &$alias) use ($searchListPublish){
            $alias = 'score_max';
            $query->field('id,user_id,score,create_time,update_time')->where($searchListPublish)->where([['score','>=',0]])->select();
        }],'score')->withMin(['publish'=>function($query, &$alias) use ($searchListPublish){
            $alias = 'score_min';
            $query->field('id,user_id,score,create_time,update_time')->where($searchListPublish)->where([['score','>=',0]])->select();
        }],'score')->withAvg(['publish'=>function($query, &$alias) use ($searchListPublish){
            $alias = 'score_avg';
            $query->field('id,user_id,score,create_time,update_time')->where($searchListPublish)->where([['score','>=',0]])->select();
        }],'score')->withCount(['publish'=>function($query, &$alias) use ($searchListPublish){
            $alias = 'score_count';
            $query->field('id,user_id,score,create_time,update_time')->where($searchListPublish)->where([['score','>=',0]])->select();
        }])->withCount(['publish'=>function($query, &$alias) use ($searchListPublish){
            $alias = 'score_none';
            $query->field('id,user_id,score,create_time,update_time')->where($searchListPublish)->where([['score','=',-1]])->select();
        }])->withCount(['publish'=>function($query, &$alias) use ($searchListPublish){
            $alias = 'publish_count';
            $query->field('id,user_id,score,create_time,update_time')->where($searchListPublish)->select();
        }])->page($page, $limit)->order($sortList)->select();
        $list = $users;
        //筛选
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

    /*
     * 获取用户本人分数(日、周、月、年、总)
     *
     * */
    public function collectMe(Request $request){
        //筛选
        $userId = $request->userId;
        $score=[];
        if (!$userId){
            $status = 404.1;
            $data['msg'] = '未找到用户信息';
        }else{
            //总
            $count = PublishModel::where('id','=',$userId)->count();//总发表量
            $scoreNull = PublishModel::where('id','=',$userId)->where('score', '=','-1')->count();//未评数
            $resultScore =PublishModel::where('id','=',$userId)->where('score', '>=', 0);
            $score=[
                'all'=>['min'=>$resultScore->min('score'),'max'=>$resultScore->max('score'),'sum'=>$resultScore->sum('score'),'avg'=>$resultScore->avg('score')],
                'year'=>[
                    'min'=>$resultScore->whereYear('create_time')->min('score'),
                    'max'=>$resultScore->whereYear('create_time')->max('score'),
                    'sum'=>$resultScore->whereYear('create_time')->sum('score'),
                    'avg'=>$resultScore->whereYear('create_time')->avg('score')
                ],
                'month'=>[
                    'min'=>$resultScore->whereMonth('create_time')->min('score'),
                    'max'=>$resultScore->whereMonth('create_time')->max('score'),
                    'sum'=>$resultScore->whereMonth('create_time')->sum('score'),
                    'avg'=>$resultScore->whereMonth('create_time')->avg('score')
                ],
                'week'=>[
                    'min'=>$resultScore->whereWeek('create_time')->min('score'),
                    'max'=>$resultScore->whereWeek('create_time')->max('score'),
                    'sum'=>$resultScore->whereWeek('create_time')->sum('score'),
                    'avg'=>$resultScore->whereWeek('create_time')->avg('score')
                ],
                'day'=>[
                    'min'=>$resultScore->whereDay('create_time')->min('score'),
                    'max'=>$resultScore->whereDay('create_time')->max('score'),
                    'sum'=>$resultScore->whereDay('create_time')->sum('score'),
                    'avg'=>$resultScore->whereDay('create_time')->avg('score')
                ],
            ];
            $data['score'] = $score;
            $data['count'] = $count;
            $data['scoreNull'] = $scoreNull;
            $status = 200;
            $data['msg'] = '获取成功';
        }
        $data['status'] = $status;
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
        if ($request->has('title', 'post')) $item['title'] = $request->param('title');
        if ($request->has('content', 'post')) $item['content'] = $request->param('content');
        if ($request->has('location', 'post')) $item['location'] = $request->param('location');
        if ($request->has('locationRes', 'post')) {
            $locationRes = $request->param('locationRes');
            try {
                $locationResJson = json_decode($locationRes);
            } catch (\Exception $e) {
                $locationResJson = $locationRes;
            }
            $item['location_res'] = $locationResJson;

            try {
                $locationResAddr =
                    (isset($locationResJson->country) ? $locationResJson->country : '') .
                    (isset($locationResJson->province) ? $locationResJson->province : '') .
                    (isset($locationResJson->city) ? $locationResJson->city : '') .
                    (isset($locationResJson->district) ? $locationResJson->district : '') .
                    (isset($locationResJson->street) ? $locationResJson->street : '') .
                    (isset($locationResJson->streetNum) ? $locationResJson->streetNum : '') .
                    (isset($locationResJson->poiName) ? $locationResJson->poiName : '');
            } catch (\Exception $e) {
                $locationResAddr = $locationRes;
            }
            $item['location_res_addr'] = $locationResAddr;
        }
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
        $publish = PublishModel::where('id', $id)->findOrEmpty();
        if ($publish->isEmpty()) {
            $status = 401.1;
            $msg = '找不到此记录';
        } else {
            if (isset($request->authMenu->dataScope)) {
                //带权限
                $dataScope = $request->authMenu['data_scope'];
                $dataScopeArr = explode(',', $dataScope);
                $dataScopeArr = array_map(function ($v) {
                    return (int)$v;
                }, $dataScopeArr);
                if ($dataScopeArr[0] === 0) {
                    //允许操作所有CreateId
                    $hasPermission = true;
                } else {
                    $createId = $publish->createId;
                    $userDepts = UserDept::where('user_id', $createId)->select();
                    foreach ($userDepts as $userDept) {
                        if (in_array($userDept->dept_id, $dataScopeArr)) {
                            $hasPermission = true;
                            break;
                        }
                    }
                }
            }
            if ($hasPermission) {
                if ($request->has('score', 'post')) $publish['score'] = $request->param('score');
                if ($request->has('evaluate', 'post')) $publish['evaluate'] = $request->param('evaluate');
                $publish->save();
                $status = 200;
                $msg = '更新成功';
            } else {
                $status = 403.1;
                $msg = '权限不足，无法操作该记录';
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
     */
    public function delete($id = null)
    {
        //$id单个删除,ids批量删除
        if (!is_null($id)) {
            PublishModel::findOrEmpty($id)->delete();
        } elseif (request()->has('ids') && is_array(request()->param('ids'))) {
            PublishModel::where('id', 'in', request()->param('ids'))->select()->delete();
        }
        $status = 200;
        $data['status'] = $status;
        $data['msg'] = '删除成功';
        return json($data, intval($status));
    }

    /**
     * 批量下载图片生成压缩包
     * @Method get
     * @requestParam id/ids
     * @param null $id
     */
    public function downloadFiles($id = null)
    {
        //$id单个记录下载,ids批量下载
        $publishFile = null;
        $utils = new utils();
        $files = [];
        $zipFileName = '';
        if (!is_null($id)) {
            $publish = PublishModel::find($id);
            $publishFile = $publish->file;
            $username = $publish->user->username;
            $createTime = str_replace(":", "-", $publish->createTime);
            $location = $publish->location;
            $id = $publish->id;
            foreach ($publishFile as $item) {
                $src = $utils->dirPathFormat($item['src']);
                $fileName = $username . '_' . $createTime . '_' . $location . '_' . $id . '_' . $item['id'] . '.' . $item['suffix'];
                array_push($files, ['src' => $src, 'name' => $fileName]);
            }
            $zipFileName = $username . '_' . $createTime . '_' . $location . '_' . $id . '.zip';
        } elseif (request()->has('ids') && is_array(request()->param('ids'))) {
            $publishs = PublishModel::where('id', 'in', request()->param('ids'))->with('file')->select();
            foreach ($publishs as $publish) {
                $publishFile = $publish->file;
                $username = $publish->user->username;
                $createTime = str_replace(":", "-", $publish->createTime);
                $location = $publish->location;
                foreach ($publishFile as $item) {
                    $src = $utils->dirPathFormat($item['src']);
                    $fileName = $username . '_' . $createTime . '_' . $location . '_' . $id . '_' . $item['id'] . '.' . $item['suffix'];
                    array_push($files, ['src' => $src, 'name' => $fileName]);
                }
            }
            $zipFileName = 'pictureFiles_' . md5(json_encode(request()->param('ids'))) . '.zip';
        }
        $zip = new \ZipArchive;
        $fileRoot = config('filesystem')['disks']['tempDownload']['root'] . '/';
        $baseUrl = config('filesystem')['disks']['tempDownload']['url'] . '/';
        $tempZipSrc = $utils->dirPathFormat($fileRoot . $zipFileName);
        $tempZipPath = $utils->urlPathFormat($baseUrl . $zipFileName);
        if ($zip->open($tempZipSrc, \ZipArchive::CREATE | \ZipArchive::OVERWRITE)) {
            foreach ($files as $file) {
                if (!file_exists($file['src'])) {
                    continue;
                }
                $zip->addFile($file['src'], $file['name']);
            }
            $zip->close();
            $status = 200;
            $data['fileName'] = $zipFileName;
            $data['debug'] = $tempZipSrc;
            $data['status'] = $status;
            $data['msg'] = '文件打包成功';
            $data['path'] = $tempZipPath;
            return json($data, intval($status));
//            return download($tempZipSrc);
        } else {
            $status = 400;
            $data['status'] = $status;
            $data['msg'] = '文件打包失败';
            return json($data, intval($status));
        }
    }
}
