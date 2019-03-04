<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 设置IP为授权Key
// Log::key('127.0.0.1');
// 应用公共文件
// 返回
function jsonReturn($data = null, $code = '200', $msg = 'success'){
    $result = [
        'code' => $code,
        'data' => $data,
        'msg'  => $msg
    ];
    echo json_encode($result);
    exit();
}
