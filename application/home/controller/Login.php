<?php

namespace app\home\controller;

use think\DB;
use think\Cache;
use think\Cookie;
use think\Controller;

use app\home\model\Users;

class Login extends Controller
{
    /**
     * 登录
     *
     */
    public function login()
    {
        $mobile = input('mobile');
        $pwd    = input('pwd');
        // if(){
        //
        // }
        $pwd = sha1('as*an&!@#_a+'.md5($pwd));
        // if(){
        //
        // }
        // echo $pwd;
        // exit();
        $user = Users::where('mobile',$mobile)->find();
        if($user['pwd'] != $pwd){
            return json_encode(['code'=>'204','data'=>'null','msg'=>'账号密码错误']);
        }

        // 保存用户信息
        Cache::set($mobile,$pwd);

        // Session::set('uid',$user['id']);
        // $_SESSION['uid'] = $user['id'];
        $info = [
            'id'     => $user['id'],
            'name'   => $user['nickname'],
            'mobile' => $mobile,
            'avatar' =>  $user['avatar_url'],
            'sign'   => $user['signature']
        ];
        Cookie::set('user',$info);
        return json_encode(['code'=>'200','data'=>'','msg'=>'登录成功']);
    }

    /**
     * 注册
     *
     */
    public function regist()
    {
        //
    }

    /**
     * 退出
     *
     */
    public function logout()
    {
        //
    }
}
