<?php

define('APP_CN_NAME', '金湖马拉松协会管理后台'); //填写app中文
return array(
	//'配置项'=>'配置值'
	'APP_NAME'      => 'jhmls', //app简称
	'SUPER_ADMIN'   => array("wangdianwen"=>"true",), //无视权限的超级管理员
    "LOGIN"         => "Privilege/User/login",
    "REGISTER"      => "Privilege/User/regiser"

);
