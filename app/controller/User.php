<?php
declare (strict_types=1);

namespace app\controller;

use app\BaseController;
use app\model\Dept;
use app\model\UserToken;
use app\model\UserTokenAuth;
use cryptology\RSA;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\Request;
use \app\model\User as UserModel;
use think\Response;
use utils\index as utils;

class User extends BaseController
{
    /**
     * 显示资源列表
     *
     * @param Request $request
     * @return Response
     */
    public function index(Request $request)
    {
//        $list = UserModel::page(1, 10)->select()->hidden(['password']);
//        $data['list'] = $list;
        $data['middleMsg'] = $request->middleMsg;
        $status = 200;
        $data['msg'] = $request;
        $data['status'] = $status;
        return json($data, intval($status));
    }

    /**
     * 保存新建的资源
     *
     * @param Request $request
     * @return Response
     */
    public function save(Request $request)
    {
        //
    }

    /**
     * 显示指定的资源
     *
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function read(Request $request, $id)
    {
        $userId = $request->userId;
        $info = null;
        if (!$userId) {
            $status = 404.1;
            $data['msg'] = '未找到该用户';
        } elseif ($id) {
            $user = UserModel::findOrEmpty($id);
            $info = $user->hidden(['password']);
            if (!$user->isEmpty()) {
                $status = 200;
                $data['msg'] = '获取成功';
            } else {
                $status = 404.1;
                $data['msg'] = '未找到该用户';
            }
        } else {
            $status = 400;
            $data['msg'] = '参数错误';
        }
        $data = [
            'info' => $info,
            'status' => $status,
        ];
        return json($data, intval($status));
    }

    /**
     * 获取个人信息
     *
     * @param Request $request
     * @return Response
     */
    public function info(Request $request)
    {
        $data = [];
        $user = UserModel::findOrEmpty($request->userId);
        if (!$user->isEmpty()) {
            $info = $user->hidden(['password', 'delete_time', 'delete_id']);
            $data['info'] = $info;
            $data['info']['depts'] = $user->depts->hidden(['pivot'])->visible(['id', 'name']);
            $data['info']['roles'] = $user->roles->hidden(['pivot'])->visible(['id', 'name', 'level', 'data_scope_model']);
            $status = 200;
            $data['msg'] = '获取成功';
        } else {
            $status = 404.1;
            $data['msg'] = '获取失败，无法找到该用户';
        }
        $data['status'] = $status;
        return json($data, intval($status));
    }

    /**
     * 登录
     *
     * @param Request $request
     * @return Response
     */
    public function login(Request $request)
    {
        $status = $request->status;
        $data['status'] = $status;

        $username = $request->param('username');
        $password = $request->param('password');
        $rememberMe = $request->param('rememberMe');
        $rememberMeB = $rememberMe === 'true' || $rememberMe == 1;

        $rsa = new RSA();
        $userByLogin = UserModel::where('username', $username)->findOrEmpty();
        $userByEmail = UserModel::where('email', $username)->findOrEmpty();
        $passwordDecrypt = hash('sha3-512', $rsa->decrypt($password)); //des->sha3-512解密

        // 检验账号密码(加入id二次ha3-512解密解密)
        $usernameLogin = !$userByLogin->isEmpty() && $userByLogin->password === hash('sha3-512', ($userByLogin->id) . $passwordDecrypt);
        $emailLogin = !$userByEmail->isEmpty() && $userByEmail->password === hash('sha3-512', ($userByEmail->id) . $passwordDecrypt);
        if ($usernameLogin || $emailLogin) {
            $user = $usernameLogin ? $userByLogin : $userByEmail;
            $status = 200;
            $data['token'] = hash('sha3-512', (microtime(true) . $user->id . $user->password));
            $data['info'] = $user->hidden(['password', 'delete_time', 'delete_id']);
            $data['info']['depts'] = $user->depts->hidden(['pivot'])->visible(['id', 'name']);
            $data['info']['roles'] = $user->roles->hidden(['pivot'])->visible(['id', 'name', 'level', 'data_scope_model']);
            $data['rememberMe'] = $rememberMeB;
            $data['status'] = $status;
            $data['msg'] = $usernameLogin ? '用户登陆成功' : '邮箱登陆成功';
            // 1.判断用户当前设备是否已有token,有则删除
            $cookiesSessionId = cookie('session_id');
            if (is_string($cookiesSessionId) && strlen($cookiesSessionId) > 0) UserToken::where('session_id', $cookiesSessionId)->delete();
            // 2.存进数据库
            $query = [
                'user_id' => $user->id,
                'remember_me' => $rememberMeB,
                'token' => $data['token'],
                'expires' => $rememberMeB ? (60 * 60 * 24 * 14) : (60 * 60 * 24 * 1),
                'x_forwarded_for' => isset($request->server()['HTTP_X_FORWARDED_FOR']) ? $request->server()['HTTP_X_FORWARDED_FOR'] : '',// 多重转发的地址(真实地址)
                'remote_address' => isset($request->server()['REMOTE_ADDR']) ? $request->server()['REMOTE_ADDR'] : '',// 请求/代理地址
                'request_user_agent' => isset($request->server()['HTTP_USER_AGENT']) ? $request->server()['HTTP_USER_AGENT'] : '',// 当前请求的设备信息
                'session_id' => $request->session_id
            ];
            UserToken::create($query);
            return json($data, intval($status));
        } elseif ($userByLogin->isEmpty() && $userByEmail->isEmpty()) {
            $status = 401.1;
            $data['status'] = $status;
            $data['msg'] = '账号不存在，请检查用户名或电子邮箱';
            return json($data, intval($status));
        } else {
            $status = 401.1;
            $data['status'] = $status;
            $data['msg'] = '账号或密码错误';
            return json($data, intval($status));
        }
    }

    /**
     * 拉取初始化菜单
     *
     * @param Request $request
     * @return Response
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function initMenu(Request $request)
    {
        $data = [];
        $user = UserModel::findOrEmpty($request->userId);
        if (!$user->isEmpty()) {
            $roles = $user->roles;
            $utils = new Utils();
            $menuList = [];
            $DSM1 = [];//本级数据范围（每个角色都一样）
            // 列出用户所有角色，计算出每个角色的路由信息、权限、数据范围
            foreach ($roles as $role) {
                $menus = $role->menus;
                $DSM2 = [];//自定义数据范围（角色里每个菜单都一样）
                foreach ($menus as $menu) {
                    $menuListIds = array_map(function ($v) {
                        return ($v['id']);
                    }, $menuList);
                    $exists = array_search($menu['id'], $menuListIds);
                    //若还没添加
                    if ($exists === false) {
                        $RDSM = $role->data_scope_model;
                        $menu->dataScopeModel = [$RDSM];
                        $menu->dataScope = [];
                        if ($RDSM === 0) {
                            $menu->dataScope = [0];
                            array_push($menuList, $menu);
                        } elseif ($RDSM === 1) {
                            if (count($DSM1) > 0) {
                                $menu->dataScope = $DSM1;
                                array_push($menuList, $menu);
                            } else {
                                $res = [];
                                $depts = $user->depts;//获取用户所属部门
                                $allDepts = Dept::select();
                                foreach ($depts as $dept) {
                                    $subDept = $utils->array2level($allDepts, $dept['pid']);//递归获取下属部门
                                    $subDeptIds = array_map(function ($v) {
                                        return $v['id'];
                                    }, $subDept);
                                    $res = array_merge($res, $subDeptIds);//合并
                                }
                                $res = array_unique($res);//去重
                                $menu->dataScope = $DSM1 = $res;
                                array_push($menuList, $menu);
                            }
                        } elseif ($RDSM === 2) {
                            if (count($DSM2) > 0) {
                                $menu->dataScope = $DSM2;
                                array_push($menuList, $menu);
                            } else {
                                $res = [];
                                $depts = $role->depts;//获取部门
                                foreach ($depts as $dept) {
                                    array_push($res, $dept->id);
                                }
                                $res = array_unique($res);//去重
                                $menu->dataScope = $DSM2 = $res;
                                array_push($menuList, $menu);
                            }
                        }
                    } else {
                        //若已经添加
                        $lastMenu = $menuList[$exists];//已存在的菜单
                        $lastDataScopeModel = $lastMenu->dataScopeModel;
                        $RDSM = $role->data_scope_model;
                        if (in_array(0, $lastDataScopeModel) || (in_array(1, $lastDataScopeModel) && $RDSM === 1)) {
                            break;
                        } else {
                            $lastMenu['dataScopeModel'] = array_unique(array_merge($lastMenu['dataScopeModel'], [$RDSM]));
                            if ($RDSM === 1) {
                                if (count($DSM1) > 0) {
                                    $lastMenu['dataScope'] = array_unique(array_merge($DSM1, $lastMenu['dataScope']));
                                } else {
                                    $res = [];
                                    $depts = $user->depts;//获取用户所属部门
                                    $allDepts = Dept::select();
                                    foreach ($depts as $dept) {
                                        $subDept = $utils->array2level($allDepts, $dept['pid']);//递归获取下属部门
                                        $subDeptIds = array_map(function ($v) {
                                            return $v['id'];
                                        }, $subDept);
                                        $res = array_merge($res, $subDeptIds);//合并
                                    }
                                    $res = array_unique($res);//去重
                                    $DSM1 = $res;
                                    $lastMenu['dataScope'] = array_unique(array_merge($res, $lastMenu['dataScope']));
                                }
                            } elseif ($RDSM === 2) {
                                if (count($DSM2) > 0) {
                                    $lastMenu['dataScope'] = array_unique(array_merge($DSM2, $lastMenu['dataScope']));
                                } else {
                                    $res = [];
                                    $depts = $role->depts;//获取部门
                                    foreach ($depts as $dept) {
                                        array_push($res, $dept->id);
                                    }
                                    $res = array_unique($res);//去重
                                    $DSM2 = $res;
                                    $lastMenu['dataScope'] = array_unique(array_merge($res, $lastMenu['dataScope']));
                                }
                            }
                        }
                    }
                }
            }
            //批量删除权限
            UserTokenAuth::where('user_token_id', $request->userToken->id)->delete();
            //缓存权限
            $userTokenAuth = new UserTokenAuth();
            $list = [];
            foreach ($menuList as $menu) {
                $res = [
                    'user_token_id' => $request->userToken->id,
                    'auth' => $menu['permission'],
                    'data_scope' => implode(',', $menu['dataScope']),
                    'data_scope_model' => implode(',', $menu['dataScopeModel'])
                ];
                array_push($list, $res);
            }
            $userTokenAuth->saveAll($list);
            //输出路由菜单树
            $tree = $utils->arr2tree($menuList);
            $data['menu'] = $tree;
            $status = 200;
            $data['msg'] = '获取成功';
        } else {
            $status = 404.1;
            $data['msg'] = '获取失败，无法找到该用户';
        }
        $data['status'] = $status;
        return json($data, intval($status));
    }

    /**
     * 注销登录
     *
     * @request  id userToken的ID
     * @param Request $request
     * @return Response
     */
    public function logout(Request $request)
    {
        if (!$request->param('id')) {
            $data['userToken'] = $request->userToken;
            $request->userToken->delete();
            $status = 200;
            $data['msg'] = '您已退出登录';
        } elseif ($request->param('id')) {
            $flag = UserToken::where('id', $request->param('id'))->delete();
            if ($flag) {
                $data['msg'] = '该设备已成功退出';
                $status = 200;
            } else {
                $data['msg'] = '未找到登录信息';
                $status = 404.1;
            }
        } else {
            $status = 404.1;
            $data['msg'] = '未找到登录信息';
        }
        $data['status'] = $status;
        return json($data, intval($status));
    }

    /**
     * 保存更新的资源
     *
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function update(Request $request, $id)
    {
        $status = 200;
        $data['msg'] = '编辑成功';
        return json($data, intval($status));
    }

    /**
     * 删除指定资源
     *
     * @return Response
     */
    public function delete(Request $request)
    {
        if (!$request->param('id')) {
            UserModel::where($request->param('id'))->delete();
            $status = 200;
            $data['msg'] = '删除成功';
        } else {
            $status = 404.1;
            $data['msg'] = '未找到该用户';
        }
        return json($data, intval($status));
    }
}
