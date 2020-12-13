<?php


namespace app\model;
use think\Model;

class UserToken extends Model
{
    // 数据转换为驼峰命名
    protected $convertNameToCamel = true;

    public function auth()
    {
        return $this->hasMany(UserTokenAuth::class);
    }

    public static function onBeforeDelete($userToken)
    {
        UserTokenAuth::where('user_token_id', $userToken->id)->delete();
    }

}
