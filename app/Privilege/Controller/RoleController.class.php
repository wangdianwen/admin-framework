<?php
namespace Privilege\Controller;
use Think\Controller;
class RoleController extends BaseController {

    public function __construct(){
        parent::__construct();

        $this->model_Role = new \Org\Privilege\RoleModel();
        $this->model_User = new \Org\Privilege\UserModel();
        $this->model_Resource = new \Org\Privilege\ResourceModel();
    }
    //添加角色
    public function addRole(){
        if(I('post.name')){
            $data['name'] = I('post.name');
            $data['admin_email'] = I('post.admin_email');
            $data['status'] = I('post.status');
            $data['ctime'] = date("Y-m-d H:i:s");

            $this->model_Role->addRole($data);
        }
        $users = $this->model_User->getUsers();
        $this->assign('users', $users);

        $this->display();
    }
    //修改角色
    public function editRole(){
        if(I('post.name')){
            $data['name'] = I('post.name');
            $data['admin_email'] = I('post.admin_email');
            $data['status'] = I('post.status');
            $this->model_Role->updateRole(I("get.role_id"),$data);
        }
        $role_id = I("get.role_id");
        $role_info = $this->model_Role->getRoleById($role_id);
        $this->assign('role_info', $role_info);

        $users = $this->model_User->getUsers();
        $this->assign('users', $users);

        $this->display("Role/addRole");
    }
    //删除角色
    public function delRole(){
        $role_id = I("get.role_id");
        $res = $this->model_Role->delRole(I("get.role_id"));
        $this->success('删除成功', '/Privilege/Role/roleList');
    }
    //角色列表
    public function roleList(){
        $roles = $this->model_Role->getRoles();
        $this->assign('roles', $roles);
        $this->display();
    }
    public function roleUser(){
        $role_id = I('get.role_id');
        $users = $this->model_Role->getRoleUser($role_id); 
        $this->assign('users', $users);
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
                $res = $this->model_Role->assignPriv($data, 'add');     
            }else{
                $res = $this->model_Role->assignPriv($data, 'del');
            }
            echo $res;
            die();
        
        }
        if(I('post.ajaxAct') && I('post.ajaxAct') == 'getController'){
            //获取module下的controller
            $module = I('post.module');
            $contros = $this->model_Resource->getcontrollers($module);
            echo (json_encode($contros));
            die();
        }

        //角色信息
        $role_id = I('get.role_id');
        $role_info = $this->model_Role->getRoleById($role_id);
        $rolePrivRes = $this->model_Role->getRoleResID($role_id);
        $roleUsers = $this->model_Role->getRoleUser($role_id);
        
        //权限列表
        $where['pk_resource_id'] = 0;
        if(I('post.module')){
            $where = array();
            $where['module'] =  I('post.module');
            $where['controller'] =  I('post.controller');
        }
        $modules = $this->model_Resource->getModules();
        $resList = $this->model_Resource->getResource($where);

        $this->assign('modules', $modules);
        $this->assign('rolePrivRes', $rolePrivRes);
        $this->assign('roleUsers', $roleUsers);
        $this->assign('resList', $resList);

        $this->assign('role_info', $role_info);
        $this->display();        
    
    }
    public function assignRoleUser(){
        //角色信息
        $role_id = I('get.role_id');
        $role_info = $this->model_Role->getRoleById($role_id);

        if(!$this->is_super && $role_info['admin_email'] != $this->user_email){
            $this->echoExit('无权操作！');
        }
        
        if(I('post.ajaxAct') && I('post.ajaxAct') == 'assignDo'){
            //分配执行 ajax
            $data['role_id'] = I('post.role_id');
            $data['user_id'] = I('post.user_id');
            $data['ctime'] = date("Y-m-d H:i:s");
            
            if(I('post.assignType') == 'add'){
                $res = $this->model_Role->assignRoleUser($data, 'add');     
            }else{
                $res = $this->model_Role->assignRoleUser($data, 'del');
            }
            echo $res;
            die();
        
        }
        //角色用户列表
        $roleUsers = $this->model_Role->getRoleUser($role_id);
        
        $pn = I('page',1,'number_int');
        $pl = 20;
        $users = $this->model_User->getUsers('', $pn, $pl);
        $total = $this->model_User->getUsersCount();
        $page = new \Org\Privilege\Page($total, $pl);

        $this->assign('paginate', $page->showpage());
        
        $this->assign('roleUsers', $roleUsers);
        $this->assign('role_info', $role_info);
        $this->assign('users', $users);

        $this->display();
        
    }
    //展示角色权限
    public function showRolePriv(){
        $role_id = I('get.role_id');         
        $role_info = $this->model_Role->getRoleById($role_id);
        $rolePrivRes = $this->model_Role->getRoleResID($role_id);
        $roleUsers = $this->model_Role->getRoleUser($role_id);

        $privTree = $this->model_Role->getPrivResTree(array('role_id' => $role_id));

        $this->assign('role_info', $role_info);
        $this->assign('rolePrivRes', $rolePrivRes);
        $this->assign('roleUsers', $roleUsers);
        $this->assign('privTree', $privTree);
        
        $this->display();
    
    }
}
