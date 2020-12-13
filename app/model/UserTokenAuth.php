<?php
declare (strict_types = 1);

namespace app\model;

use think\Model;

/**
 * @mixin \think\Model
 */
class UserTokenAuth extends Model
{
    // 数据转换为驼峰命名
    protected $convertNameToCamel = true;
}
