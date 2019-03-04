<?php

namespace app\home\controller;

use think\Controller;
use think\Request;
// 引入我们的主项目工厂类。
use EasyWeChat\Factory;

class Wechat extends Controller
{
    public function index(){
        // 一些配置
        $config = [
            // 自己的微信公众号
            'app_id'        => 'wx82ba1865c9b66b4d',
            'secret'        => '2027e5bcf051c69c75abed771665f938',
            'token'         => 'TestToken',
            'response_type' => 'array',
            'aes_key'       => 'o2PvtRV5dHYu9WRw9MslE5ADgBh8mTpx7JAjhELjs4t',
        //...
        ];
        // 使用配置来初始化一个公众号应用实例。
        $app = Factory::officialAccount($config);
        // 注册一个消息处理器
        $app->server->push(function ($message) {
            return "您好！欢迎使用 EasyWeChat!";
        });
        // echo $app->server->serve();

        $response = $app->server->serve();
        // 将响应输出
        $response->send();
    }
}
