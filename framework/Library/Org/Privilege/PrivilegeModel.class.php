<?php
namespace Org\Privilege;
use Think\Model;
class PrivilegeModel extends BaseModel{

    public $mcenter;
    public $model_Menu;
    public $model_Resource;
   
    public function __construct(){
        parent::__construct();
        $this->model_Menu = new MenuModel();
        $this->model_User = new UserModel();
        $this->model_Resource = new ResourceModel();
    } 
    public function isLogin(){
        $secret = I("session.secret");
        $secret = $this->decode($secret);
        $secret = json_decode($secret, true);
        $name = $secret['name'] ?? "";
        $pass = $secret['pass'] ?? "";
        $is_login = $this->model_User->checkLogin($name, $pass);
        $is_must_login = $this->check_must_login();
        if ( !$is_login && $is_must_login ) {
            return false;
        }
        session("name", $name);
        return true;
    }

    public function logout(){
        session( array('name' => 'secret', 'expire' => time() - 3600) );
        session(array('name' => 'name', 'expire' => time() - 3600));
    }
    //获取权限菜单树
    public function getPrivMenuTree(){
        /*******系统菜单*******/
        $sysMeus = $this->model_Menu->getSysMenus();
        if(!$sysMeus){
            $sysMeus =  array();
        }

        /********APP菜单********/
        $appMenus = $this->model_Menu->getAppMenus();
        if(!$appMenus){
            $appMenus =  array();
        }

        //所有可能有的权限
        $retMenuTree = $sysMeus + $appMenus;

        //用户拥有权限
        $privResList = $this->model_Resource->getUserPrivRes();
        //排除用户没有操作权限的菜单
        foreach($retMenuTree as $key1 => $tree){
            if(isset($tree['child'])){
                foreach($tree['child'] as $key2 =>$child){
                    if(count($child['action']) == 0){
                        unset($retMenuTree[$key1]['child'][$key2]);
                    }else{
                        foreach($child['action'] as $key3 => $action){
                            if(!in_array($action['resource_info']['pk_resource_id'], $privResList)){
                                unset($retMenuTree[$key1]['child'][$key2]['action'][$key3]);
                            }
                        }
                        if(count($retMenuTree[$key1]['child'][$key2]['action']) == 0){
                            unset($retMenuTree[$key1]['child'][$key2]);
                        }
                    }
                }
                if(count($retMenuTree[$key1]['child']) == 0){
                    unset($retMenuTree[$key1]['child']);
                }
            }
            if(isset($tree['action'])){
                foreach($tree['action'] as $key2 => $action){
                    if(!in_array($action['resource_info']['pk_resource_id'], $privResList)){
                        unset($retMenuTree[$key1]['action'][$key2]);
                    }
                }
                if(count($retMenuTree[$key1]['action']) == 0){
                    unset($retMenuTree[$key1]['action']);
                }
            }
            if(!isset($retMenuTree[$key1]['child']) && !isset($retMenuTree[$key1]['action'])){
                unset($retMenuTree[$key1]);
            }
        }
        return $retMenuTree;
    }
    // 获取资源信息
    public function get_resource_info ( $modulename , $controllername , $actionname , $method = 'get') {
        $where = array();
        $where['app']	= $this->app_name;
	$where['module'] = $modulename;
        $where['controller'] = $controllername;
        $where['action'] = $actionname;
        //$where['method'] = $method;
        $ret = $this->table('privilege_resource')->where( $where )->find();
        return $ret;
    }

    // 获取当前action信息
    public function get_activeAction_info () {
        $where['module'] = MODULE_NAME;
        $where['controller'] = CONTROLLER_NAME;
        $where['action'] = ACTION_NAME;
        $res_info = $this->table('privilege_resource')->where( $where )->find();
        $ret['resource_id'] = $res_info['pk_resource_id'];
        
        $menu_where['resource_id'] = $res_info['pk_resource_id'];
        $menu_info = $this->table('privilege_menu')->where( $menu_where )->find(); 
        if(!$menu_info && $res_info['location_menu_id'] != 0){
            $menu_where2['pk_menu_id'] = $res_info['location_menu_id'];
            $menu_info = $this->table('privilege_menu')->where( $menu_where2 )->find(); 
            $ret['resource_id'] = $menu_info['resource_id'];
        }
        if($menu_info){ 
            $fmenu_where['pk_menu_id'] = $menu_info['fid'];
            $fmenu_info = $this->table('privilege_menu')->where( $fmenu_where )->find(); 

            if($fmenu_info['fid'] == 0){
                $ret['ffid'] = $fmenu_info['pk_menu_id'];
                $ret['fid'] = $fmenu_info['pk_menu_id'];
            }else{
                $ret['ffid'] = $fmenu_info['fid'];
                $ret['fid'] = $fmenu_info['pk_menu_id'];
            }
        }
        return $ret;
    }

    // 获取资源id
    public function get_resource_id ( $modulename , $controllername , $actionname , $method = 'get') {
        $where = array();
        $where['module'] = $modulename;
        $where['controller'] = $controllername;
        $where['action'] = $actionname;
        //$where['method'] = $method;
        $ret = $this->table('privilege_resource')->field('pk_resource_id')->where( $where )->find();
        return $ret['pk_resource_id'];
    }
    public function get_fmenu_id($menu_id){
        $where['pk_menu_id'] = $menu_id;
        $ret = $this->table('privilege_menu')->where( $where )->find();
        if ( isset($ret['fid']) ) {
            return $ret['fid'];
        }
        return 0;
    }
    //通过email获取用户信息
    public function getUserInfoByName($name){
        $where['app'] = $this->app_name;
        $where['name'] = $name;
        return $this->table('privilege_user')->where( $where )->find();
    }      

    //获取用户权限资源ID
    public function getUserPrivResID(){
        if($this->is_super){
            //超级管理员
            $where['app'] = $this->app_name;
            $data = $this->table("privilege_resource")->field('pk_resource_id')->where($where)->select();
            foreach($data as $key => $val){
                $resList[$val['pk_resource_id']] = $val['pk_resource_id'];
            }
        }else{
            //用户角色列表
            $where['user_id'] = I("session.uid");
            $ret = $this->table('privilege_role_re_user')->field('role_id')->where( $where )->select();
            $userPrivRole = array();
            foreach($ret as $rid){
                $userPrivRole[] = $rid['role_id'];
            }
            //角色所拥有权限
            $where = array();
            $where['role_id'] = array('in', implode(',', $userPrivRole));
            $data = $this->table("privilege_role_re_resource")->field('resource_id')->where($where)->select(); 
            foreach($data as $key => $val){
                $resList[$val['resource_id']] = $val['resource_id'];
            }
        }
        return $resList;
    }
    public function check_must_login(){
        if ( $this->is_super ) {
            return false;
        }
        $path = MODULE_NAME . "/" . CONTROLLER_NAME . "/" . ACTION_NAME;
        if ( $path == C("LOGIN") || $path == C("REGISTER") ) {
            return false;
        }
        $resource_info = $this->get_resource_info(MODULE_NAME,CONTROLLER_NAME,ACTION_NAME);
        if ( empty($resource_info) ) {
            return true;
        }
        if( $resource_info['verify_login'] == 'N' ) {
            return false;
        }else{
            return true;
        }
    } 
    public function verify_app(){
        //获取APP信息
        $where['app_name'] = C("APP_NAME");
        $app_info = $this->table("privilege_app")->where($where)->find();
        if($app_info['token'] == C("APP_PRIVILEGE_TOKEN")){
            return true;
        }else{
            return false;
        }
           
    }
    //权限验证
    public function check_privilege(){
        $path = MODULE_NAME + "/" + CONTROLLER_NAME + "/" + ACTION_NAME;
        if ( $path == C("LOGIN") || $path == C("REGISTER") ) {
            return true;
        }
        $resource_info = $this->get_resource_info(MODULE_NAME,CONTROLLER_NAME,ACTION_NAME);
        if($resource_info['verify_privilege'] == 'N'){
            return true;
        }
        //判断权限
        $privResList = $this->model_Resource->getUserPrivRes(); 
        return in_array($resource_info['pk_resource_id'], $privResList);
    }


}
