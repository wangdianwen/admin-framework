<?php
namespace Privilege\Controller;
use Think\Controller;
class IndexController extends BaseController {
    public function logout(){
        $model_Privilege = new \Org\Privilege\PrivilegeModel();
        $model_Privilege->logout();
    }

}
