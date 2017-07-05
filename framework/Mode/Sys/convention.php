<?php
// +----------------------------------------------------------------------
// | youkuCrm [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2015 http://youku.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: wangdianwen <wangdianwen@youku.com>
// +----------------------------------------------------------------------

/**
 * crm模式惯例配置文件
 * 该文件请不要修改，如果要覆盖惯例配置的值，可在应用配置文件中设定和惯例不符的配置项
 * 配置名称大小写任意，系统会统一转换成小写
 * 所有配置参数都可以在生效前动态改变
 */
defined('THINK_PATH') or exit();
return array(
    'TMPL_ENGINE_TYPE'      => 'Smarty',
    'TMPL_ENGINE_CONFIG'=>array(
        'caching'=>false
    ),
    'TMPL_ACTION_ERROR'     => THINK_PATH . 'Tpl/dispatch_jump_smarty.tpl',
    'TMPL_ACTION_SUCCESS'   => THINK_PATH . 'Tpl/dispatch_jump_smarty.tpl',
    'SHOW_ERROR_MSG' => true,
    'URL_CASE_INSENSITIVE' => false,
);
