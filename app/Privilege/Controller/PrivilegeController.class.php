<?php
namespace Home\Controller;
use Think\Controller;
class privilegeController extends Controller {
    var $model_Privilege;

    public function __construct(){
        parent::__construct();

        $this->model_Privilege = D("Privilege");
        $this->assign('post', $_POST);
        $this->assign('get', $_GET);
    }
    //添加资源
    public function addRes(){
        if(I('post.module')){
            $data['module'] = I('post.module');
            $data['controller'] = I('post.controller');
            $data['action'] = I('post.action');
            $data['title'] = I('post.title');
            $data['menu_id'] = I('post.fid');

            $this->model_Privilege->addResource($data);
        }
        $topMenus = $this->model_Privilege->getMenus();
        $this->assign('topMenus', $topMenus);

        $this->display();
    }
    //修改资源
    public function resEdit(){
        if(I('post.module')){
            $res_id = I('get.res_id');
            
            $data['module'] = I('post.module');
            $data['controller'] = I('post.controller');
            $data['action'] = I('post.action');
            $data['title'] = I('post.title');
            $data['menu_id'] = I('post.fid');
            
            $this->model_Privilege->updateResource($res_id, $data);
        }
        $res_id = I('get.res_id');
        $res_info = $this->model_Privilege->getResourceById($res_id); 
        $this->assign('res_info', $res_info);

        $topMenus = $this->model_Privilege->getMenus();
        $this->assign('topMenus', $topMenus);

        $this->display("addRes");
    }
    public function resDel(){
        $res_id = I('get.res_id');
        //删除角色资源关联数据
        $this->model_Privilege->deletePrivilege($res_id);

        $this->model_Privilege->delResource($res_id);
        $this->redirect('privilege/resList');
    }
    //资源列表
    public function resList()
    {
        $where['pk_resource_id'] = 0;
        if(I('post.module')){
            $where = array();
            $where['module'] =  I('post.module');
            $where['controller'] =  I('post.controller');
        }
        $modules = $this->model_Privilege->getModules();
        $contros = $this->model_Privilege->getcontrollers();

        $resList = $this->model_Privilege->getResource($where);

        $this->assign('modules', $modules);
        $this->assign('contros', $contros);
        $this->assign('resList', $resList);

        $this->display();
    }
    //添加管理员
    public function addUser(){
        if(I('post.email')){
            $data['email'] = I('post.email');
            $data['name'] = I('post.name');
            $data['status'] = 1;
            $data['ctime'] = date("Y-m-d H:i:s");

            $user_id = $this->model_Privilege->addUser($data);
        }
        $this->display();
    }

    public function userDel(){
        $user_id = I('get.user_id');
        //删除角色用户关联
        $this->model_Privilege->deleteRoleRelationUserId($user_id);
        
        $this->model_Privilege->userDel($user_id);
        $this->redirect('privilege/userList');
    }

    public function userList(){
        $menus = array();
        if(I('post.fid')){
            $menus = $this->model_Privilege->getMenus(I('post.fid'));
        }
        $users = $this->model_Privilege->getUsers('');
        $this->assign('users', $users);

        $this->display();
    }
    public function userEdit(){
        $user_id = I('get.user_id');
        if(I('post.name')){
            $data['name'] = I('post.name');
            $data['email'] = I('post.email');
            $this->model_Privilege->updateUser($user_id, $data);
        }
        $user_info = $this->model_Privilege->getUserInfoById($user_id);
        $this->assign('user_info', $user_info);
        $this->display("addUser");
        die();
    
    }
    //添加角色
    public function addRole(){
        if(I('post.name')){
            $data['name'] = I('post.name');
            $data['status'] = I('post.status');
            $data['ctime'] = date("Y-m-d H:i:s");

            $this->model_Privilege->addRole($data);
        }
        $this->display();
    }
    //角色列表
    public function roleList(){
        //$m = new \Org\Crm\SendMessageModel();
        //$m->send_sms(array(15810205969,18612649368),'勇士加油吧，为CRM而战！！！');die;
        
        $roles = $this->model_Privilege->getRoles();
        $this->assign('roles', $roles);
        $this->display();
    }
    public function roleUser(){
        $role_id = I('get.role_id');
        $users = $this->model_Privilege->getRoleUser($role_id); 
        $this->assign('users', $users);
        $this->display();
    }
    public function roleUserDel(){
        $role_id = I('post.role_id');
        $user_id = I('post.user_id');
        $ret = $this->model_Privilege->roleUserDel($role_id, $user_id);
        echo $ret;
        die(); 
    }
    public function menuList(){
        $$menus = array();
        if(I('post.fid')){
            $menus = $this->model_Privilege->getMenus(I('post.fid'));
        }
        $topMenus = $this->model_Privilege->getMenus();
        $this->assign('topMenus', $topMenus);
        $this->assign('menus', $menus);

        $this->display();
    }
    //添加角色
    public function addMenu(){
        if(I('post.ajaxAct') && I('post.ajaxAct') == 'getChildMenu'){
            //获取子菜单
            $fid = I('post.fid');
            $menus = $this->model_Privilege->getMenus($fid);
            echo json_encode($menus);
            die();

        }
        if(I('post.name')){
            $data['name'] = I('post.name');
            $data['fid'] = I('post.fid');
            $data['status'] = I('post.status');

            $this->model_Privilege->addMenu($data);
        }

        $topMenus = $this->model_Privilege->getMenus();
        $this->assign('topMenus', $topMenus);

        $this->display();
    }
    
    //给角色分配权限
    public function assignRolePriv(){
        if(I('post.ajaxAct') && I('post.ajaxAct') == 'assignDo'){
            //分配执行 ajax
            $data['role_id'] = I('post.role_id');
            $data['resource_id'] = I('post.res_id');
            $data['ctime'] = date("Y-m-d H:i:s");
            if(I('post.assignType') == 'add'){
                $res = $this->model_Privilege->assignPriv($data, 'add');     
            }else{
                $res = $this->model_Privilege->assignPriv($data, 'del');
            }
            echo $res;
            die();
        
        }
        if(I('post.ajaxAct') && I('post.ajaxAct') == 'getController'){
            //获取module下的controller
            $module = I('post.module');
            $contros = $this->model_Privilege->getcontrollers($module);
            echo (json_encode($contros));
            die();
        }

        //角色信息
        $role_id = I('get.role_id');
        $role_info = $this->model_Privilege->getRoleById($role_id);
        $rolePrivRes = $this->model_Privilege->getRoleResID($role_id);
        $roleUsers = $this->model_Privilege->getRoleUser($role_id);
        
        //权限列表
        $where['pk_resource_id'] = 0;
        if(I('post.module')){
            $where = array();
            $where['module'] =  I('post.module');
            $where['controller'] =  I('post.controller');
        }
        $modules = $this->model_Privilege->getModules();

        $resList = $this->model_Privilege->getResource($where);

        $this->assign('modules', $modules);
        $this->assign('rolePrivRes', $rolePrivRes);
        $this->assign('roleUsers', $roleUsers);
        $this->assign('resList', $resList);

        $this->assign('role_info', $role_info);
        $this->display();        
    
    }
    public function assignRoleUser(){
        if(I('post.ajaxAct') && I('post.ajaxAct') == 'assignDo'){
            //分配执行 ajax
            $data['role_id'] = I('post.role_id');
            $data['user_id'] = I('post.user_id');
            $data['ctime'] = date("Y-m-d H:i:s");
            
            if(I('post.assignType') == 'add'){
                $res = $this->model_Privilege->assignRoleUser($data, 'add');     
            }else{
                $res = $this->model_Privilege->assignRoleUser($data, 'del');
            }
            echo $res;
            die();
        
        }
        //角色信息
        $role_id = I('get.role_id');
        $role_info = $this->model_Privilege->getRoleById($role_id);
        $roleUsers = $this->model_Privilege->getRoleUser($role_id);
        
        $users = $this->model_Privilege->getUsers('');
        
        $this->assign('roleUsers', $roleUsers);
        $this->assign('role_info', $role_info);
        $this->assign('users', $users);

        $this->display();
        
    }
    //展示角色权限
    public function showRolePriv(){
        $role_id = I('get.role_id');         
        $role_info = $this->model_Privilege->getRoleById($role_id);
        $rolePrivRes = $this->model_Privilege->getRoleResID($role_id);
        $roleUsers = $this->model_Privilege->getRoleUser($role_id);

        $privTree = $this->model_Privilege->getPrivResTree(array('role_id' => $role_id));

        $this->assign('role_info', $role_info);
        $this->assign('rolePrivRes', $rolePrivRes);
        $this->assign('roleUsers', $roleUsers);
        $this->assign('privTree', $privTree);
        
        $this->display();
    
    }
    public function logout(){
        $this->mcenter = new \Org\Crm\MCenterClientModel();
        $this->mcenter->logout();
    }
}
