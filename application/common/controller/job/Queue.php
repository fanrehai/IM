<?php

namespace app\common\job;

use think\queue\Job;

class Queue
{
    /**
     * 邮件提醒
     * @param array $data  内容
     * @return
     */
    public function sendMAIL(Job $job, $data)
    {
        $isJobDone = $this->send($data);
        if ($isJobDone) {
            //成功删除任务
            $job->delete();
        } else {
            //任务轮询4次后删除
            if ($job->attempts() > 3) {
                // 第1种处理方式：重新发布任务,该任务延迟10秒后再执行
                //$job->release(10);
                // 第2种处理方式：原任务的基础上1分钟执行一次并增加尝试次数
                //$job->failed();
                // 第3种处理方式：删除任务
                $job->delete();
            }
        }
        // 也可以重新发布这个任务
        // $job->release($delay); //$delay为延迟时间
    }
    /**
     * 根据消息中的数据进行实际的业务处理
     * @param array|mixed    $data     发布任务时自定义的数据
     * @return boolean                 任务执行的结果
     */
    public function send($data){
        $val = [
            'title' => '执行了一次'.time(),
            'icon'  => 'http://www.baidu.com'
        ];
        $result = \think\Db::name('power_config')->save($val);
        if ($result) {
            echo '成功';
            exit();
            return true;
        } else {
            echo '错误';
            exit();
            return false;
        }
    }
    public function failed($data){

       // ...任务达到最大重试次数后，失败了
   }
   /**
    * 写入数据库
    */
   public function saveData(){
       $isJobDone = $this->send($data);
       if($isJobDone){
           //成功删除任务
           $job->delete();
       }else{
           //任务轮询4次后删除
           if ($job->attempts() > 3) {
               // 第1种处理方式：重新发布任务,该任务延迟10秒后再执行
               $job->release(10);
               // 第2种处理方式：原任务的基础上1分钟执行一次并增加尝试次数
               //$job->failed();
               // 第3种处理方式：删除任务
               // $job->delete();
           }
       }
       // 也可以重新发布这个任务
       // $job->release($delay); //$delay为延迟时间
   }
}
