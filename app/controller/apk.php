<?php


namespace app\controller;


use app\BaseController;
use fileDownload\index as fileDownload;
use think\Request;
use utils\index as utils;

class apk extends BaseController
{
    public $latest = "1.0.1";

    public function index(Request $request){
        $latest = $this->latest;
        $data=[];
        $url = 'http://' . $_SERVER['HTTP_HOST'] . "/apk/download";
        $appidCheck = "__UNI__1127D4B";
        $note =
            "* 1.0.1版本更新内容：
            1.新增发表回复、总分统计;
            2.修复其他已知的bug;";

        $data["code"] = 0;
        if ($request->has('appid') && $request->has('version')) {
            $appid =  $request->param('appid');
            $version = $request->param('version'); //客户端版本号
            if ($appid === $appidCheck) { //校验appid
                if ($version !== $latest) { //这里是示例代码，真实业务上，最新版本号及relase notes可以存储在数据库或文件中
                    $data["code"] = 1;
                }
            }
        }
        $status = 200;
        $data["latest"] = $latest;
        $data["note"] = $note;
        $data["url"] = $url; //应用升级包下载地址
        $data['status'] = $status;
        $data['msg'] = '获取成功';
        return json($data, intval($status));
    }

    public function download(){
        $FileDownload=new FileDownload();
        $latest = $this->latest;
        //$file = $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'].'.apk';
        $utils = new utils();
        $fileRoot = config('filesystem')['disks']['public']['root'].'/';
        $file = $utils->dirPathFormat($fileRoot . 'apk/GDOUPG_'.$latest.'.apk');
        $name = '';

        $flag = $FileDownload->download($file, $name, true);

        if (!$flag) {
            abort(404, '文件不存在或已被删除');
        }

    }
}
