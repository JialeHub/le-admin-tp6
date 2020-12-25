<?php
declare (strict_types=1);

namespace app\controller;

use think\Request;
use \think\facade\Filesystem;
use utils\index as utils;
use \app\model\File as FileModel;
use \SplFileInfo;

class File
{
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
        //
    }

    /**
     * 显示创建资源表单页.
     *
     * @return \think\Response
     */
    public function create()
    {
        //
    }

    /**
     * 保存新建的资源
     *
     * @param \think\Request $request
     * @return \think\Response
     */
    public function save(Request $request)
    {
        $fileList = $request->file('file');
        if (is_object($fileList)) {
            //单文件上传 file
            $files = [$fileList];
        } elseif (is_array($fileList)) {
            //多文件上传 file[]
            $files = $fileList;
        } else {
            $status = 400.1;
            $data['msg'] = '上传失败';
            return json($data, intval($status));
        }
        $utils = new utils();
        $fileRoot = config('filesystem')['disks']['public']['root'].'/';
        $baseUrl = config('filesystem')['disks']['public']['url'].'/';
        $list = [];
        foreach ($files as $file) {
            $savePath = Filesystem::disk('public')->putFile('file', $file);
            // 服务器绝对路径
            $src = $utils->dirPathFormat($fileRoot . $savePath);
            // URL路径
            $path = $utils->urlPathFormat($baseUrl . $savePath);

            $item = [
                'name' => (is_string($request->name)&&strlen($request->name)>0)?$request->name: $file->getOriginalName(),
                'original_name' => $file->getOriginalName(),
                'mime_type' => $file->getOriginalMime(),
                'size' => $file->getSize(),
                'suffix' => $file->extension(),
                'src' => $src,
                'path' => $path,
                'create_id' => $request->userId,
            ];
            //尝试读取图片信息等所有信息
            $fileInfo = new \fileInfo\index($file);
            if (count($fileInfo->getAll())>0) $item['info'] = $fileInfo->getAll();
            array_push($list, $item);
        }
        $fileModel = new FileModel;
        $res = $fileModel->saveAll($list)->visible(['id','src','path']);
        $status = 200;
        $data['status'] = $status;
        $data['res'] = $res;
        $data['msg'] = '上传成功';
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
        $file = FileModel::findOrEmpty($id);
        $info = $file->hidden(['password']);
        if (!$file->isEmpty()) {
            $status = 200;
            $data['info'] = $info;
            $data['msg'] = '获取成功';
        } else {
            $status = 404.1;
            $data['msg'] = '未找到该文件信息';
        }
        return json($data, intval($status));
    }

    /**
     * 显示编辑资源表单页.
     *
     * @param int $id
     * @return \think\Response
     */
    public function edit($id)
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
        //
    }

    /**
     * 删除指定资源
     *
     * @param int $id
     * @return \think\Response
     */
    public function delete($id)
    {
        //
    }
}



