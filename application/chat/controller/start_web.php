<?php
# @Date:   2018-06-13 22:48:16
# @Filename: start_web.php
# @Last modified time: 2018-06-21 09:53:18

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
use \Workerman\Worker;
use \Workerman\WebServer;
use \GatewayWorker\Gateway;
use \GatewayWorker\BusinessWorker;
use \Workerman\Autoloader;

require_once __DIR__ . '/../../../vendor/autoload.php';

// WebServer
$web = new WebServer("http://127.0.0.1:55151");
// WebServer进程数量
$web->count = 4;
// 设置站点根目录
// $web->addRoot('www.your_domain.com', __DIR__.'/../../../public/Web');
// $web->addRoot('www.your_domain.com', __DIR__.'/../../index/controller');
$web->addRoot('www.your_domain.com', __DIR__.'/../../../public/Web');
// 如果不是在根目录启动，则运行runAll方法
if(!defined('GLOBAL_START'))
{
    Worker::runAll();
}
