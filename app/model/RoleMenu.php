<?php
declare (strict_types = 1);

namespace app\model;

use think\Model;
use think\model\Pivot;

/**
 * @mixin \think\Model
 */
class RoleMenu extends Pivot
{
    protected $autoWriteTimestamp = true;
}
