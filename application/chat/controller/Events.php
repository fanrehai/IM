<?php
/**
 * This file is part of workerman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link http://www.workerman.net/
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 */

/**
 * 用于检测业务代码死循环或者长时间阻塞等问题
 * 如果发现业务卡死，可以将下面declare打开（去掉//注释），并执行php start.php reload
 * 然后观察一段时间workerman.log看是否有process_timeout异常
 */
//declare(ticks=1);
// namespace app\chat\controller;
/**
 * 聊天主逻辑
 * 主要是处理 onMessage onClose
 */
use \GatewayWorker\Lib\Gateway;
use \GatewayWorker\Lib\Db;

class Events
{
    /**
     * 当客户端连接上来时
     */
    public static function onConnect($client_id){
        $message = [
            'type'              => 'init',
            'client_id'         => $client_id
            // 'from_client_id'    => '10086',
            // 'from_client_name'  => '系统',
            // 'to_client_id'      => $client_id,
            // 'content'           => "<b>系统对你说: 欢迎登录</b>".$client_id,
            // 'time'              => date('Y-m-d H:i:s'),
        ];
        return Gateway::sendToCurrentClient(json_encode($message));
    }
   /**
    * 有消息时
    * @param int $client_id
    * @param mixed $message
    */
   public static function onMessage($client_id, $message)
   {
        // debug
        echo "client:{$_SERVER['REMOTE_ADDR']}:{$_SERVER['REMOTE_PORT']} gateway:{$_SERVER['GATEWAY_ADDR']}:{$_SERVER['GATEWAY_PORT']}  client_id:$client_id session:".json_encode($_SESSION)." onMessage:".$message."\n";

        // 客户端传递的是json数据
        $message_data = json_decode($message, true);
        if(!$message_data)
        {
            return ;
        }

        // 判断登录
        // if(){
        //
        // }
        // 根据类型执行不同的业务
        switch($message_data['type'])
        {
            // 客户端回应服务端的心跳
            case 'pong':
                return;
            // case 'user'
            //     $new_message = array('type'=>'login', 'client_id'=>$client_id, 'client_name'=>htmlspecialchars($client_name), 'time'=>date('Y-m-d H:i:s'));
            //     Gateway::sendToGroup($room_id, json_encode($new_message));
            //     Gateway::joinGroup($client_id, $room_id);
            //     return;
            // 客户端登录 message格式: {type:login, name:xx, room_id:1} ，添加到客户端，广播给所有客户端xx进入聊天室
            case 'login':
                // 绑定客户端ID到用户账号ID
                Gateway::bindUid($client_id, $message_data['user_id']);
                // 获取当前发言人ID绑定的客户端ID
                $bind_list = Gateway::getClientIdByUid($message_data['user_id']);
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
                // 获取好友的状态
                // $toClientState = Gateway::getClientIdByUid($message_data['to_client_id'])[0];
                // if(empty($toClientState)){
                //     $to_client_state = 0;
                // }else{
                //     $to_client_state = 1;
                // }
                    // 'to_client_state' => $to_client_state,
                $_SESSION['uid'] = $message_data['user_id'];
                // 告知客户端 Client_id
                $client_message = [
                    'type'            => 'client',
                    'client_id'       => $client_id,
                    'user_id'         => $_SESSION['uid'],
                    'time'            => date('Y-m-d H:i:s'),
                ];
                return Gateway::sendToCurrentClient(json_encode($client_message));
                // // 判断是否有房间号
                // if(!isset($message_data['room_id']))
                // {
                //     throw new \Exception("\$message_data['room_id'] not set. client_ip:{$_SERVER['REMOTE_ADDR']} \$message:$message");
                // }
                // // 绑定客户端ID至服务端用户ID
                // Gateway::bindUid($client_id, $message_data['user_id']);
                //
                // // 把房间号昵称放到session中
                // $room_id = $message_data['room_id'];
                // $client_name = htmlspecialchars($message_data['client_name']);
                // $_SESSION['room_id'] = $room_id;
                // $_SESSION['client_name'] = $client_name;
                // // $client_name = Session::get('mobile');//User::where('mobile',)
                //
                // // 获取房间内所有用户列表
                // $clients_list = Gateway::getClientSessionsByGroup($room_id);
                // foreach($clients_list as $tmp_client_id=>$item)
                // {
                //     $clients_list[$tmp_client_id] = $item['client_name'];
                // }
                // $clients_list[$client_id] = $client_name;
                //
                // // 转播给当前房间的所有客户端，xx进入聊天室 message {type:login, client_id:xx, name:xx}
                // $new_message = array('type'=>$message_data['type'], 'client_id'=>$client_id, 'client_name'=>htmlspecialchars($client_name), 'time'=>date('Y-m-d H:i:s'));
                // Gateway::sendToGroup($room_id, json_encode($new_message));
                // Gateway::joinGroup($client_id, $room_id);
                //
                // // 给当前用户发送用户列表
                // $new_message['client_list'] = $clients_list;
                // $new_message['type'] = 'user';
                // Gateway::sendToCurrentClient(json_encode($new_message));
                // return;

            // 客户端发言 message: {type:say, to_client_id:xx, content:xx}
            case 'say':
                // 非法请求
                if(!isset($_SESSION['room_id']))
                {
                    $_SESSION['room_id'] = '1';
                    // throw new \Exception("\$_SESSION['room_id'] not set. client_ip:{$_SERVER['REMOTE_ADDR']}");
                }
                $room_id = $_SESSION['room_id'];
                $client_name = 'asd';//$_SESSION['client_name'];


                // 私聊
                if($message_data['to_client_id'] != 'all')
                {
                    // 发消息人客户端ID
                    $userClientId = Gateway::getClientIdByUid($message_data['user_id']);
                    // 收消息客户端ID
                    $toUserClientId = Gateway::getClientIdByUid($message_data['to_client_id']);
                    // if(empty($toUserClientId)){
                    //     $toUserClientId = '';
                    // }
                    $new_message = [
                        'type'             => 'say',
                        'from_client_id'   => $userClientId[0],
                        'from_client_name' => $client_name,
                        'to_client_id'     => $toUserClientId[0],
                        'content'          => nl2br(htmlspecialchars($message_data['content'])),
                        'time'             => date('Y-m-d H:i:s'),
                    ];
                    Gateway::sendToClient($toUserClientId[0], json_encode($new_message));
                    Gateway::sendToCurrentClient(json_encode($new_message));
                    return;
                }
                $new_message = [
                    'type'             => 'say',
                    'from_client_id'   => $userClientId[0],//$client_id,
                    'from_client_name' => $client_name,
                    'to_client_id'     => $toUserClientId[0],
                    'content'          => nl2br(htmlspecialchars($message_data['content'])),
                    'time'             => date('Y-m-d H:i:s'),
                ];
                // return Gateway::sendToClient($toUserClientId[0], json_encode($new_message));
                // return Gateway::sendToCurrentClient(json_encode($new_message));
                return Gateway::sendToGroup($room_id ,json_encode($new_message));
            // case 'privatesay':
            //     $new_message = array(
            //         'type'             => 'say',
            //         'from_client_id'   => $client_id,
            //         'from_client_name' => $client_name,
            //         'to_client_id'     => $message_data['to_client_id'],
            //         'content'          => "<b>对你说: </b>".nl2br(htmlspecialchars($message_data['content'])),
            //         'time'             => date('Y-m-d H:i:s'),
            //     );
            //     Gateway::sendToClient($message_data['to_client_id'], json_encode($new_message));
            //     $new_message['content'] = "<b>你对".htmlspecialchars($message_data['to_client_name'])."说: </b>".nl2br(htmlspecialchars($message_data['content']));
            //     return Gateway::sendToCurrentClient(json_encode($new_message));
        }
   }

   /**
    * 当客户端断开连接时
    * @param integer $client_id 客户端id
    */
   public static function onClose($client_id)
   {
       $friends_message = [
           'type'           => 'friend_offline',
           // 'from_user_id'   => $user_id,
           'from_user_id' => $_SESSION['uid'],
           'from_client_id' => $client_id,
           // 'to_client_id'   => $to_client_id[0],
           'time'           => date('Y-m-d H:i:s',time()),
       ];
       Gateway::sendToAll(json_encode($friends_message));
       // debug
       echo "client:{$_SERVER['REMOTE_ADDR']}:{$_SERVER['REMOTE_PORT']} gateway:{$_SERVER['GATEWAY_ADDR']}:{$_SERVER['GATEWAY_PORT']}  client_id:$client_id onClose:''\n";

       // 从房间的客户端列表中删除
       if(isset($_SESSION['room_id']))
       {
           $room_id = $_SESSION['room_id'];
           $new_message = [
               'type'=>'logout',
               'from_client_id'=>$client_id,
               'from_client_name'=>'空',
               'time'=>date('Y-m-d H:i:s')
           ];//$_SESSION['client_name']
           Gateway::sendToGroup($room_id, json_encode($new_message));
       }
   }

}
