<?php
// +----------------------------------------------------------------------
// | youkuSys [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2015 http://youku.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: wangdianwen <wangdianwen@youku.com>
// +----------------------------------------------------------------------

namespace Behavior;
/**
 * 系统行为扩展：crm模式下权限校验
 */
class SysprivilegeBehavior {

    private $model_Privilege;
    private $admin_info;

    public function __construct(){
        
        $this->model_Privilege = new \Org\Privilege\PrivilegeModel();
        $this->admin_info = array();
    }

    // 行为扩展的执行入口必须是run
    public function run(&$params)
    {
        // 调试模式下不进行权限校验
        if (APP_DEBUG == True) {
            //return true;
        }
        $is_login = $this->model_Privilege->isLogin();
        if ( $is_login ) {
            $this->model_Privilege->init();
            /***********权限验证*********/
            //APP验证
            $verify_res = $this->model_Privilege->verify_app();
            if (!$verify_res) {
                die('APP 配置有误！');
            }

            //超级管理员
            if ($this->model_Privilege->is_super) return true;
            //权限判断
            $ret = $this->model_Privilege->check_privilege();
            if ($ret == false) {
                header("Content-type:text/html;charset=utf-8");
                die("您有没权限");
            }
        } else {
            header("location: /" .  C("LOGIN"));
        }
    }
}
