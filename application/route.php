<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
use think\Route;

// Route::group('user',function(){
//     Route::any('friends','home/user/friendslist');
//     Route::any(':name','blog/read',[],['name'=>'\w+']);
// },['method'=>'get']);

return [
    // 路由别名
    // '__alias__' =>  [
    //     'user'  =>  ['home/user'],
    // ],
    '__pattern__' => [
        'name' => '\w+',
    ],
    '[user]'     => [
        'bind'      => ['home/user/userBind',['method' => 'post']],
        'say'       => ['home/user/userSay',['method' => 'post']],
        'friends'   => ['home/user/friendslist', ['method' => 'get']],
        'close'     => ['home/user/userClose', ['method' => 'get']],
        'info'      => ['home/user/userInfo', ['method' => 'get']],
        'init'      => ['home/user/userInit', ['method' => 'get']],
    ],
    '[friend]'   => [
        'info'      => ['home/friend/friendInfo', ['methob' => 'get']], 
    ],

];
