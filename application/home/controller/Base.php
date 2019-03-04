<?php

namespace app\home\controller;

use think\Db;
use think\Cache;
use think\Config;
use think\Session;
use think\Request;
use think\Controller;

use app\home\model\IpBlacks;

class Base extends Controller
{
    protected $request;

    public function __construct(Request $request){
        $this->request = $request;
        // 检测IP黑名单
        // $this->checkIpBlackList();
        // 检测接口调用频率
        $this->checkApiFreq($this->request);

        // dump($this->request);
        // echo htmlspecialchars($this->request->get('qwe'));
        // exit();
    }
    /**
     * 接收并验证
	 * @param fname 接口名称
     * @param val   接收的参数值
     * @param aname 要验证依据的参数值
     */
    public function RR($val = ''){
		// 获取控制器名称
		$action = $this->request->action();
		// 如果不是登录方法，就自动加上user_id和token的验证
		if($action !== 'login'){
			if(empty($val)){
				$val = ['user_id','token'];
			}else{
				array_push($val,'user_id','token');
			}
			$aname = 'user_id';
		}else{
			$aname = 'phone';
		}
		// 只获取post数据
    	$res = $this->request->only($val,'post');
		// 全名
        $fname = 'Base.'.$action;
        //验证
        // $result = $this->validate($res,$fname);
        // if(true !== $result){
        //     jsonPrint('20000','null',$result);
        // }

    //     if(!empty($res['user_id']) && !empty($res['token'])){
    //         // 获取账户安全码
    //         $term['a.user_id'] = array('eq',$res['user_id']);
    //         $v = Db::name('admin a')->field('a.pwd,a.mcode,a.salt,a.token_time,b.menu_auth,a.status')
	// 								->join('admin_group b','b.id = a.sortid')
	// 								->where($term)
	// 								->find();
    //         if(!$v){
    //             jsonPrint('20000','null','账号密码错误');
    //         }
	// 		if($v['status'] != '1'){
	// 			jsonPrint('20000','null','账户状态异常');
	// 		}
	// 		// 用户权限列表
	// 		$auth = explode(',', $v['menu_auth']);
	// 		// 获取权限ID所属模块
	// 		$map['url'] = array('eq',$action);
	// 		$tid = Db::name('admin_auth')->where($map)->value('tid');
	// 		// 判断权限
	// 		if(!in_array($tid, $auth)){
	// 			jsonPrint('20000','null','权限不足');
	// 		}
	// 		// 服务端Token
    //         $server_token = sha1(md5($res['user_id'].Config::get('encrypt')['admin']).$v['mcode'].$v['salt']);
    //         // 判断token
    //         if($res['token'] == $server_token && $v['token_time'] > time()){
    //             if(($v['token_time'] - time()) < 10000){
    //                 $data = [
    //                     'token_time' => $v['token_time'] + 1800
    //                 ];
    //                 $login = Db::name('admin a')->where($term)->update($data);
    //                 if(!$login){
    //                     jsonPrint('20000','null','账号保存错误');
    //                 }
    //             }
    //             return $res;
    //         }else{
    //             jsonPrint('10000','null','账号登录状态错误,请重新登录');
    //         }
    //     }
    //     return $res;
    }
    /**
     * 检测黑名单
     * 假如IP地址在黑名单内就不允许继续接下来的操作
     */
    private function checkIpBlackList(){
        $ip_list     = Cache::get('ip_list');
        $check_time  = Cache::get('ip_check');
        // 更新缓存
        if(($check_time + 1800) < time()){
            $mapa    = [
                'end_time' => ['gt',date('Y-m-d H:i:s',time())]
            ];
            $ip_list = IpBlacks::where($mapa)->column('ip,end_time');
            Cache::set('ip_check',time());
            Cache::set('ip_list',$ip_list);
        }
        $client_ip   = '192.168.66.999';//$this->getIP();
        $ip_key = array_keys($ip_list);
        if(in_array($client_ip,$ip_key)){
            if(strtotime($ip_list[$client_ip]) > time()){
                // jsonReturn($ip_list[$client_ip]."|||||||".date('Y-m-d H:i:s',time()),'20000','IP在黑名单内');//请求过于频繁，请稍后再试
                jsonReturn('null','20000','请求过于频繁，请稍后再试');
            }
        }
    }
    /**
     * 检测接口调用频率
     * 假如同一个IP地址，调用同一个接口次数每小时超过100次，就将该IP地址封禁1小时
     * 后期还可以将接口调用频率写入后台，可以设置不同接口对应不同的频率
     *
     * $once_time 每次调用接口频率间隔
     * $all_time  每小时调用频率限制
     */
    private function checkApiFreq($req, $once_time = 200, $all_time = 100){
        // 获取控制器名称
		$action    = 'Base.'.$req->action();
        $client_ip = '192.168.66.999';//$this->getIP();
        // 毫秒级时间戳
		list($t1, $t2) = explode(' ', microtime());
		$milli_time    =  (float)sprintf('%.0f',(floatval($t1)+floatval($t2))*1000);
		// 验证API访问频率
		$client_api_time = Cache::get($action.','.$client_ip);

        if($client_api_time !== false){
            // 每次间隔
            $math  = (int)($milli_time - end($client_api_time));
    		if($math < $once_time){
    			jsonReturn('null','20001','操作过于频繁,请稍后再试');
    		}
            array_push($client_api_time,$milli_time);
            // 获取频率
            $api_freq_num = 0;
            foreach ($client_api_time as &$val) {
                if((int)($milli_time - 3600000) < $val){
                    $api_freq_num += 1;
                }
            }
            if($api_freq_num >= $all_time){
                //写入数据库
                $data = [
                    'ip'       => $client_ip,
                    'end_time' => date('Y-m-d H:i:s',time() + 3600)
                ];
                IpBlacks::where('ip',$client_ip)->delete();
                IpBlacks::create($data);
                // 写入缓存
                $ip_black = array_values($data);
                $ip_list = Cache::get('ip_list');
                $ip_list[$ip_black[0]] = $ip_black[1];
                Cache::set('ip_list',$ip_list);
                // 删除当前IP调用次数
                Cache::rm($action.','.$client_ip);

                jsonReturn('null','20002','IP已被拉入黑名单');
            }else{
                Cache::set($action.','.$client_ip,$client_api_time);
            }
        }else{
            Cache::set($action.','.$client_ip,[$milli_time]);
        }
    }
    /**
     * 测试修改缓存内容
     * @return [type] [description]
     */
    public function test(){
        $ip_list = Cache::get('ip_list');
        $ip_list['192.168.66.999'] = '2019-01-11 15:35:00';

        Cache::set('ip_list',$ip_list);
        $action    = 'Base.test';
        $client_ip = '192.168.66.999';//$this->getIP();
        $a = Cache::get($action.','.$client_ip);

        dump($ip_list);
        dump($a);
        exit();
    }
    /**
     * 生成测试IP
     * @return [type] [description]
     */
    // public function test(){
    //     for($i = 0;$i < 255;$i++){
    //         $val = [
    //             'ip' => '192.168.66.'.$i,
    //             'create_time' => '2019-01-09 22:29:31',
    //             'end_time' => '2019-02-01 22:29:35',
    //         ];
    //         IpBlacks::create($val);
    //     }
    // }
    /**
     * 获取用户IP
     * @return [type] [description]
     */
    private function getIP(){
       global $ip;
       if(getenv("HTTP_CLIENT_IP")){
           $ip = getenv("HTTP_CLIENT_IP");
       }else if(getenv("HTTP_X_FORWARDED_FOR")){
           $ip = getenv("HTTP_X_FORWARDED_FOR");
       }else if(getenv("REMOTE_ADDR")){
           $ip = getenv("REMOTE_ADDR");
       }else{
           $ip = "10.10.10.10";
       }
       return $ip;
    }
    /**
     * 备份数据库
     */
    public function importData()
    {
        set_time_limit(0);
        $table    = 'f_signin';//input('param.table');

        $sqlStr   = "SET FOREIGN_KEY_CHECKS=0;\r\n";
        $sqlStr  .= "DROP TABLE IF EXISTS `$table`;\r\n";
        $create   = Db::query('show create table ' . $table);
        $sqlStr  .= $create['0']['Create Table'] . ";\r\n";
        $sqlStr  .= "\r\n";

        $result = Db::query('select * from ' . $table);
        foreach($result as $key=>$vo){
            $keys = array_keys($vo);
            $keys = array_map('addslashes', $keys);
            $keys = join('`,`', $keys);
            $keys = "`" . $keys . "`";
            $vals = array_values($vo);
            $vals = array_map('addslashes', $vals);
            $vals = join("','", $vals);
            $vals = "'" . $vals . "'";
            $sqlStr .= "insert into `$table`($keys) values($vals);\r\n";
        }

        $filename = Config::get('sql_back_path') . $table . "_" . date('Y_m_d_H_i_s') . ".sql";
        $fp = fopen($filename, 'w');
        fputs($fp, $sqlStr);
        fclose($fp);

        jsonReturn(null,1);
    }
}
