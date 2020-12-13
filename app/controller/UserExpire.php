<?php


namespace app\controller;


use app\model\RoleDept;
use app\model\RoleMenu;
use app\model\UserDept;
use app\model\UserRole;
use app\model\UserToken;

class UserExpire
{
    public function deptChange($dept){
        $roles = RoleDept::where('dept_id',$dept->id )->column('role_id');
        $roles = array_merge(array_diff(array_keys(array_flip($roles)), [0])) ;//去重、去0
        $userIdsByRole = UserRole::where('role_id','in',$roles )->column('user_id');
        $userIdsByUser = UserDept::where('dept_id',$dept->id )->column('user_id');
        $usersIds = array_merge($userIdsByUser,$userIdsByRole);
        $usersIds = array_merge(array_diff(array_keys(array_flip($usersIds)), [0])) ;//去重、去0

        $userToken = new UserToken();
        $userTokensIds = UserToken::where('user_id','in',$usersIds)->visible(['id'])->select();
        $list = [];
        foreach ($userTokensIds as $userTokensId){
            array_push($list,['id'=>$userTokensId->id,'expires'=>1]);
        }
        $userToken->saveAll($list);
    }

    public function menuChange($menu){
        $rolesIds = RoleMenu::where('menu_id',$menu->id)->column('role_id');
        $rolesIds = array_merge(array_diff(array_keys(array_flip($rolesIds)), [0])) ;//去重、去0
        $usersIds = UserRole::where('role_id','in',$rolesIds )->column('user_id');
        $usersIds = array_merge(array_diff(array_keys(array_flip($usersIds)), [0])) ;//去重、去0
        $userToken = new UserToken();
        $userTokensIds = UserToken::where('user_id','in',$usersIds)->visible(['id'])->select();
        $list = [];
        foreach ($userTokensIds as $userTokensId){
            array_push($list,['id'=>$userTokensId->id,'expires'=>1]);
        }
        $userToken->saveAll($list);
    }

    public function roleChange($role){
        $usersIds = UserRole::where('role_id',$role->id )->column('user_id');
        $usersIds = array_merge(array_diff(array_keys(array_flip($usersIds)), [0])) ;//去重、去0
        $userToken = new UserToken();
        $userTokensIds = UserToken::where('user_id','in',$usersIds)->visible(['id'])->select();
        $list = [];
        foreach ($userTokensIds as $userTokensId){
            array_push($list,['id'=>$userTokensId->id,'expires'=>1]);
        }
        $userToken->saveAll($list);
    }

    public function userChange($user){
        $userToken = new UserToken();
        $userTokensIds = UserToken::where('user_id',$user->id)->visible(['id'])->select();
        $list = [];
        foreach ($userTokensIds as $userTokensId){
            array_push($list,['id'=>$userTokensId->id,'expires'=>1]);
        }
        $userToken->saveAll($list);
    }
}
