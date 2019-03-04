<?php

namespace app\home\model;

use think\Model;
use traits\model\SoftDelete;

class Friends extends Model
{
    use SoftDelete;

    protected $table = 'f_friend_nexus';
    protected $autoWriteTimestamp = 'datetime';

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    protected static $deleteTime = 'delete_time';
}
