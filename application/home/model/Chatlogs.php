<?php

namespace app\home\model;

use think\Model;
use traits\model\SoftDelete;

class Chatlogs extends Model
{
    protected $table = 'f_chatlog';

    use SoftDelete;
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'create_time';
    protected $updateTime = false;
    protected static $deleteTime = 'delete_time';
}
