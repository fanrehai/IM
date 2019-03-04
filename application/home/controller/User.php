<?php

namespace app\home\controller;

require_once '../extend/GatewayClient/Gateway.php';

use think\Controller;
use think\Request;
use think\Cookie;
use think\Image;
use GatewayClient\Gateway;

use app\home\model\Users;
use app\home\model\Friends;
use app\home\model\Chatlogs;
use app\home\model\FriendGroups;

use think\Queue;


Gateway::$registerAddress = '127.0.0.1:1236';

class User extends Base//Controller
{

    /**
     * 客户端ID绑定用户ID
     * @param
     */
    public function userBind(){
        $client_id = input('client_id');
        $user = Cookie::get('user');
        // 绑定客户端ID到用户账号ID
        Gateway::bindUid($client_id, $user['id']);
        // 获取当前发言人ID绑定的客户端ID
        $bind_list = Gateway::getClientIdByUid($user['id']);
        // 判断当前客户端ID是否在列表中
        if(in_array($client_id,$bind_list)){
            // 获取指定元素的下标
            $key = array_search($client_id,$bind_list);
            unset($bind_list[$key]);
            if(!empty($bind_list)){
                // 2018.12.20 新增：踢掉其他的客户端ID
                // 2018.12.21 修改：使用for循环来发送退号通知
                for($i = 0; $i < count($bind_list);$i++){
                    $message = [
                       'type'             => 'loginclash',
                       'from_client_id'   => '10086',
                       'from_client_name' => '系统',
                       'to_client_id'     => $bind_list[$i],
                       'content'          => "<b>当前账户已在其他地方登录，请确保账号密码没有泄露 </b>".date('Y-m-d H:i:s'),
                       'time'             => date('Y-m-d H:i:s'),
                   ];
                    Gateway::sendToClient($bind_list[$i],json_encode($message));
                    Gateway::closeClient($bind_list[$i]);
                }
            }
        }
        // 告知客户端 Client_id
        $client_message = [
           'type'      => 'login',
           'user_id'   => $user['id'],
           'client_id' => $client_id,
           'time'      => date('Y-m-d H:i:s'),
        ];
        Gateway::sendToClient($client_id,json_encode($client_message));

        $info = [
            'id'        => $user['id'],
            'name'      => $user['name'],
            'mobile'    => $user['mobile'],
            'avatar'    => $user['avatar'],
            'sign'      => $user['sign'],
            'client_id' => $client_id
        ];
        Cookie::set('user',$info);

        // 告知用户好友当前状态
        $mapa = [
            'user_id'           => ['eq',$user['id']],
            'friend_status'     => ['eq','0'],
            'from_black_status' => ['eq','0'],
        ];
        $user_friends = Friends::where($mapa)->limit(10)->column('to_user_id');

        foreach ($user_friends as &$val) {
            $to_client_id = Gateway::getClientIdByUid($val);
            if(!empty($to_client_id)){
                $friends_message = [
                    'type'           => 'friend_online',
                    'from_user_id'   => $user['id'],
                    'from_client_id' => $client_id,
                    'time'           => date('Y-m-d H:i:s'),
                ];
                Gateway::sendToClient($to_client_id[0],json_encode($friends_message));
            }
        }
        jsonReturn($user);
    }
    /**
     * 客户端Ping服务端
     * @param
     */
    public function userPing(){
        return;
    }
    /**
     * 客户端发送消息
     */
    public function userSay(){
        $type      = input('type');
        $client_id = Cookie::get('user')['client_id'];//input('client_id');

        $user_id   = Cookie::get('user')['id'];//input('user_id');
        $user_icon = Cookie::get('user')['avatar'];//input('user_icon');
        $content   = input('content');

        $to_user_id = input('to_user_id');

        // preg_match_all('/<img class="layim-chat-img" src=\"(.*?)\">/', $content, $img);
        // preg_match_all('/[\x{4e00}-\x{9fa5}]+/u', $content, $text);
        // preg_match_all('/face\[.*?\]+/', $content, $face);

        // dump($img);
        // dump($text);
        // dump($face);
        $mapb = [
            'user_id'    => $user_id,
            'to_user_id' => $to_user_id,
            'content'    => $content,
            'type'       => 'private',
            'needsend'   => '0'
        ];
        Chatlogs::create($mapb);
        // 毫秒级时间戳
		list($t1, $t2) = explode(' ', microtime());
		$milli_time    =  (float)sprintf('%.0f',(floatval($t1)+floatval($t2))*1000);

        $say_id = $user_id.'_'.$milli_time;
        // 判断是否好友关系与是否被拉黑
        $mapa = [
            'user_id'    => ['eq',$user_id],
            'to_user_id' => ['eq',$to_user_id],
        ];
        $relation = Friends::where($mapa)->find();
        // jsonReturn($relation);
        if($relation['friend_status'] != 0){
            $this->sayToClient($user_id,$user_icon,$client_id,'',$relation,$say_id,$to_user_id);
            jsonReturn($say_id,204,'你们已不是好友关系，无法发送消息');
        }
        if($relation['to_black_status'] === 1){
            $this->sayToClient($user_id,$user_icon,$client_id,'',$content,$say_id,$to_user_id);
            jsonReturn($say_id,205,'对方已将你拉黑，你发送的消息对方将不会收到');
        }
        // 获取好友客户端ID
        $to_client_id = Gateway::getClientIdByUid($to_user_id);
        if(empty($to_client_id)){
            $this->sayToClient($user_id,$user_icon,$client_id,'',$content,$say_id,$to_user_id);
            jsonReturn($say_id,203,'对方目前不在线，你发送的消息对方将不会收到');
        }

        $this->sayToClient($user_id,$user_icon,$client_id,$to_client_id[0],$content,$say_id,$to_user_id);
        jsonReturn();
    }
    /**
     * 客户端注册
     */
    public function userInit(){
        // Session::get('mobile');
    }
    /**
     * 获取用户好友信息
     */
    public function friendsList(){
        $validate = '';
        // $user_id  = input('user_id');
        $user = Cookie::get('user');
        // 获取用户好友关系表
        $mapa = [
            'user_id'           => ['eq',$user['id']],
            'friend_status'     => ['eq','0'],
            'from_black_status' => ['eq','0'],
        ];
        $user_friends = Friends::where($mapa)->limit(10)->column('to_user_id,remark');

        $mapb = [
            'id' => ['in',array_keys($user_friends)],
        ];
        $friends_info = Users::field('id,nickname,avatar_url,signature')->where($mapb)->select();
        for ($i = 0; $i < count($friends_info); $i++) {
            foreach ($user_friends as $key => $val) {
                if($key == $friends_info[$i]['id']){
                    $friends_info[$i]['remark'] = $val;
                }
                $to_client_id = Gateway::getClientIdByUid($friends_info[$i]['id']);
                if(!empty($to_client_id)){
                    $friends_info[$i]['online_state'] = 1;
                    $friends_info[$i]['client_id'] = $to_client_id[0];
                }else{
                    $friends_info[$i]['online_state'] = 0;
                    $friends_info[$i]['client_id'] = '';
                }
            }
        }
        // 在线的好友在前，离线的在后
        $online = [];
        $offline = [];
        foreach ($friends_info as &$val) {
            if($val['online_state'] == 1){
                $online[] = $val;
            }else{
                $offline[] = $val;
            }
        }
        $friends_list = array_merge($online,$offline);
        jsonReturn($friends_list);
    }
    /**
     * 获取用户好友组、群聊及历史会话
     */
    public function userInfo(){
        // $user_id  = input('user_id');
        $user = Cookie::get('user');
        // 好友列表
        $terma['user_id'] = ['eq',$user['id']];
        $friend = FriendGroups::field('id,name,group_friends')->where($terma)->select();
        // 此处需重构
        foreach($friend as &$val){
            $termb['id'] = ['in',$val['group_friends']];
            $val['list'] = Users::field('id,nickname,avatar_url,signature')->where($termb)->select();
            unset($val['group_friends']);
        }

        // 群组
        $group = [];

        $data = [
            'friend' => $friend,
            'group'  => $group
        ];
        jsonReturn($data);
    }
    /**
     * 用户下线(没用的)
     */
    public function userClose(){
        $user_id  = input('user_id');
        // 获取用户好友关系表
        $mapa = [
            'user_id'           => ['eq',$user_id],
            'friend_status'     => ['eq','0'],
            'from_black_status' => ['eq','0'],
        ];
        $user_friends = Friends::where($mapa)->limit(10)->column('to_user_id,remark');
        // 告知用户好友当前状态
        $mapa = [
            'user_id'           => ['eq',$user_id],
            'friend_status'     => ['eq','0'],
            'from_black_status' => ['eq','0'],
        ];
        $user_friends = Friends::where($mapa)->limit(10)->column('to_user_id');

        foreach ($user_friends as &$val) {
            $to_client_id = Gateway::getClientIdByUid($val);
            if(!empty($to_client_id)){
                $friends_message = [
                    'type'           => 'friend_offline',
                    'from_user_id'   => $user_id,
                    // 'from_client_id' => $client_id,
                    // 'to_client_id'   => $to_client_id[0],
                    'time'           => date('Y-m-d H:i:s'),
                ];
                Gateway::sendToClient($to_client_id[0],json_encode($friends_message));
            }
        }
        jsonReturn();
    }
    /**
     *
     */
    public function server(){
        $email_data = '1378411077@qq.com';
        // 消息队列
        $result = Queue::push('app\common\Queue@sendMAIL', $email_data, $queue = null);
        if($result){
            echo date('Y-m-d H:i:s') . '一个新的队列任务';
        }else{
            echo date('Y-m-d H:i:s') . '添加队列出错';
        }
        //  // 立即执行
        //  think\Queue::push($job, $data = '', $queue = null);
        //  // 在$delay秒后执行
        //  think\Queue::later($delay, $job, $data = '', $queue = null);
        //
        // echo "<pre/>";
        // var_dump($_SERVER);
    }
    /**
     * 发送至客户端
     */
    private function sayToClient($user_id = '',$user_icon,$client_id,$to_client_id,$content,$say_id,$to_user_id){
        $message = [
           'type'              => 'say',
           'say_id'            => $say_id,
           'from_user_id'      => $user_id,
           'from_user_icon'    => $user_icon,
           'from_client_id'    => $client_id,
           // 'from_client_name'  => '系统',
           // 'to_client_id'      => $to_client_id,
           'to_user_id'        => $to_user_id,
           'content'           => $content,
           'time'              => date('Y-m-d H:i:s'),
        ];
        Gateway::sendToClient($client_id,json_encode($message));
        if(!empty($to_client_id)){
            Gateway::sendToClient($to_client_id,json_encode($message));
        }
    }
    public function imgZq(){
        //过滤所有的img
        $url = "http://www.hzvis.com/case/";
        $str = file_get_contents($url);
        // $preg = '/<img[^>]*>/i';
        $preg = '/<img.+src=\"?(.+\.(jpg|gif|bmp|bnp|png))\">/i';
        preg_match_all($preg, $str, $matches);
        $matches = $matches[1];

        //文件保存地址
        $dir = 'C:/Users/fanre/Desktop/web/upfile/';
//         preg_match('/<img.+src=\"?(.+\.(jpg|gif|bmp|bnp|png))\"?.+>/i',$str,$match);
// echo $match[1];
        // dump($matches);
        $b = [];
        foreach($matches as $k => $v){
            $array = explode('" alt', $v);

            $a = explode('image/', $array[0]);

            if(count($a)>1){
                $b[] = $a;
            }
            // $b = array_merge($b);


            // $name = $dir . $k . '.jpg';
            // // 下载
            // $tmparr = explode('http',$array[0]);
            // //
            //  if(count($tmparr)>1){
            //     // self::download($name, $array[0]);
            //     echo $array[0];
            // // }else{
            //     // echo $array[0];
            // }
        }
        // $b = array_filter($b);
        foreach ($b as &$v) {
            $c = substr($v[1],8);
            $c = 'C:/Users/fanre/Desktop/web/upfile/'.$c;
            // dump($b);exit;
            // $tmparr = explode('http',$b);
            //
             // if(count($tmparr)>1){
                self::download($c,$v[0]."image/".$v[1]);
                // echo $c."||||||".$v[0]."image/".$v[1];
                echo "\r\n";
            // }else{
                // echo $b;
            // }
        }

    }
    public function download($name, $url){
        if(!is_dir(dirname($name))){
            mkdir(dirname($name));
        }
        $str = file_get_contents($url);
        file_put_contents($name, $str);
        //输出一些东西,要不窗口一直黑着,感觉怪怪的
        echo strlen($name);
        echo "\n";
    }
}
