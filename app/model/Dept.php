<?php
declare (strict_types = 1);

namespace app\model;

use app\controller\userExpire;
use think\Model;
use think\model\concern\SoftDelete;

/**
 * @mixin \think\Model
 */
class Dept extends Model
{
    // 数据转换为驼峰命名
    protected $convertNameToCamel = true;
    use SoftDelete;

    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    public static function onAfterInsert($dept)
    {
        $userExpire = new UserExpire();
        $userExpire->deptChange($dept);
    }

    public static function onAfterWrite($dept)
    {
        $userExpire = new UserExpire();
        $userExpire->deptChange($dept);
    }

    public static function onBeforeUpdate($dept)
    {
        $userExpire = new UserExpire();
        $userExpire->deptChange($dept);
    }

    public static function onBeforeDelete($dept)
    {
        $userExpire = new UserExpire();
        $userExpire->deptChange($dept);
    }

}
