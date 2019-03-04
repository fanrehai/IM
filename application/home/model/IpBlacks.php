<?php

namespace app\home\model;

use think\Model;
use traits\model\SoftDelete;

class IpBlacks extends Model
{
    use SoftDelete;
    protected $table = 'f_ip_black';
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'create_time';
    protected static $deleteTime = 'delete_time';

    // protected $type = [
    //     'end_time'    =>  'datetime'
    // ];
}
