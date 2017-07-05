<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用入口文件

// 检测PHP环境
if(version_compare(PHP_VERSION,'5.3.0','<'))  die('require PHP > 5.3.0 !');

// 定义应用目录
define('APP_PATH', $_SERVER['APP_PATH']);

// app环境
define('APP_STATUS', $_SERVER['APP_STATUS']); // online , test , dev , 对应配置请写三份 Conf/online.php ...
//sys模式
define('APP_MODE','sys');

if ( APP_STATUS == 'online' ) {
	// 开启调试模式 建议开发阶段开启 部署阶段注释或者设为false
	define('APP_DEBUG', True);
} else {
	define('APP_DEBUG', True);
}

// 引入ThinkPHP入口文件
require '../../framework/ThinkPHP.php';

// 亲^_^ 后面不需要任何代码了 就是如此简单
