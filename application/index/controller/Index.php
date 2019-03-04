<?php

namespace app\index\controller;

use think\Controller;
use think\Request;
use think\Cache;

class Index extends Controller
{
    public function getData(){
        for($i = 0;$i < 105;$i++){
            $url='http://worker.bmf.com/index.php/home/user/friendslist';
            $data = file_get_contents($url);
            $data = json_decode($data,true);
            if($data['code'] == '200'){
                echo $i.'true'."|||".$data['data']['0']['nickname'].'<br/>';
            }else{
                echo $i.'false'.$data['msg']."|||".$data['code'].'<br/>';
            }
        }
    }
    public function getDel(){
        // throw new \think\exception\HttpException(404, '异常消息', null, ['参数']);
        Cache::clear();
    }
}
