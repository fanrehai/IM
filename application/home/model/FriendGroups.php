<?php

namespace app\home\model;

use think\Model;
use traits\model\SoftDelete;

class FriendGroups extends Model
{
    use SoftDelete;

    protected $table = 'f_friend_group';
    protected $autoWriteTimestamp = 'datetime';

    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    protected static $deleteTime = 'delete_time';

    // public function user(){
        // $term['id'] = ['in',];
        // return $this->hasMany('Users','id','user_id');//->field('id,nickname,signature');
    // }
}
