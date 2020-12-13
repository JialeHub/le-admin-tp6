<?php


namespace app\model;
use app\controller\UserExpire;
use think\Model;
use think\model\concern\SoftDelete;

class User extends Model
{
    // 数据转换为驼峰命名
    protected $convertNameToCamel = true;
    use SoftDelete;

    public function roles()
    {
        return $this->belongsToMany(Role::class)->order('data_scope_model');
    }

    public function depts()
    {
        return $this->belongsToMany(Dept::class);
    }

    public function userTokens()
    {
        return $this->hasMany(UserToken::class);
    }

    public static function onAfterInsert($user)
    {
        $userExpire = new UserExpire();
        $userExpire->userChange($user);
    }

    public static function onAfterWrite($user)
    {
        $userExpire = new UserExpire();
        $userExpire->userChange($user);
    }

    public static function onBeforeUpdate($user)
    {
        $userExpire = new UserExpire();
        $userExpire->userChange($user);
    }

    public static function onBeforeDelete($user)
    {
        $userExpire = new UserExpire();
        $userExpire->userChange($user);
    }
}
