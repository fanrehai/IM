<?php

namespace app\home\model;

use think\Model;

class Users extends Model
{
    // protected $table = 'f_user';
    protected $pk = 'id';

    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    // public function group(){
    //     return $this->belongsTo('friendGroups');
    // }
}
