<?php
namespace Org\Privilege;
use Think\Model;
class RoleModel extends BaseModel{
    //添加角色
    public function addRole($data){
        $data['app'] = $this->app_name;
        return $this->table("privilege_role")->add($data);
    }
    //修改角色
    public function updateRole($role_id, $data){
        $where['app'] = $this->app_name;
        $where['pk_role_id'] = $role_id;
        return $this->table("privilege_role")->where($where)->data($data)->save();
    }
    public function delRole($role_id){
        $where['app'] = $this->app_name;
        $where['pk_role_id'] = $role_id;
        return $this->table("privilege_role")->where($where)->delete();
        
    }
    //获取角色列表
    public function getRoles($where){
        $where['app'] = $this->app_name;
        if(!$this->is_super){
            $where['admin_email'] = $this->user_email;
        }
        $order = 'pk_role_id ASC';
        return $this->table('privilege_role')->where( $where )->order($order)->select();
    }
    public function getRoleById($role_id){
        $where['app'] = $this->app_name;
        $where['pk_role_id '] = $role_id;
        $role_info = $this->table('privilege_role')->where( $where )->find();

        return $role_info;
    }

    //分配角色权限
    public function assignPriv($data, $action){
        if($action == 'add'){
            $data['app'] = $this->app_name;
            return $this->table("privilege_role_re_resource")->add($data);
        }else{
            $where = " app='{$this->app_name}' AND role_id = '{$data['role_id']}' AND resource_id = '{$data['resource_id']}' "; 
            return $this->table("privilege_role_re_resource")->where($where)->delete();
        }    
    }
    //角色分配用户
    public function assignRoleUser($data, $action){
        $role_info = $this->getRoleById($data['role_id']);
        if(!$this->is_super && $role_info['admin_email'] != $this->user_email){
            die('无权操作');
        }
        if($action == 'add'){
            $data['app'] = $this->app_name;
            return $this->table("privilege_role_re_user")->add($data);
        }else{
            $where = " app =  '{$this->app_name}' AND role_id = '{$data['role_id']}' AND user_id = '{$data['user_id']}' "; 
            return $this->table("privilege_role_re_user")->where($where)->delete();
        }    
    }
    public function deleteRoleRelationUserId($user_id){
        $where['app'] = $this->app_name; 
        $where['user_id'] = $user_id;
        return $this->table("privilege_role_re_user")->where($where)->delete();
    }
    //获取角色组权限资源ID
    public function getRoleResID($role_id){
        $where['app'] = $this->app_name; 
        $where['role_id'] = $role_id;
        $data = $this->table("privilege_role_re_resource")->field('resource_id')->where($where)->select(); 
        foreach($data as $key => $val){
            $resList[$val['resource_id']] = $val['resource_id'];
        }
        return $resList;
    }
    //获取角色组成员
    public function getRoleUser($role_id){
        $where['app'] = $this->app_name; 
        $where['role_id'] = $role_id;
        $data = $this->table('privilege_role_re_user')->field('user_id')->where($where)->select();
        $uids = array();
        foreach($data as $val){
            $uids[] = $val['user_id'];
        }
        $map['pk_user_id'] = array("in", implode(',', $uids));
        $map['app'] = $this->app_name;
        $users = $this->table('privilege_user')->where($map)->select();
        foreach($users as $user){
            $retUser[$user['pk_user_id']] = $user;
        }
        return $retUser;
    }
    public function roleUserDel($role_id, $user_id){
        $where['app'] = $this->app_name; 
        $where['role_id'] = $role_id; 
        $where['user_id'] = $user_id; 
        return $this->table('privilege_role_re_user')->where($where)->delete();
    }

    // 获取角色id
    public function getRoleIdsByUid ( $uid ) {
        $where['app'] = $this->app_name; 
        $where['user_id'] = $uid;
        $rows = $this->table('privilege_role_re_user')->field('role_id')->where( $where )->select();
        $roleIds = array();
        foreach ($rows as $row){
        	$roleIds[] = $row['role_id'];
        }
        $roleIds = implode(',', $roleIds);
        return $roleIds;
    }
    //获取资源树
    public function getPrivResTree($params = array()){
        if(isset($params['role_id'])){
            //角色组
            $resIds = $this->getRoleResID($params['role_id']);
            $resList = array();
            if ( !empty($resIds) ) {
                $map['pk_resource_id'] = array("in", implode(',', $resIds));
                $resList = $this->table("privilege_resource")->where($map)->select();
            }        
	}else if(isset($params['module'])){
            //module
            $where['module'] = $params['module'];
            $resList = $this->table("privilege_resource")->where($where)->select();
        }else{
            $resList = $this->table("privilege_resource")->select();
        }
        //整理权限资源树
        $resTree = array();
        foreach($resList as $res){
            if(isset($resTree[$res['module']][$res['controller']])){
                $resTree[$res['module']][$res['controller']][] = $res;
            }else{
                $resTree[$res['module']][$res['controller']][0] = $res;
            }
        }
        return $resTree;
    }

}
