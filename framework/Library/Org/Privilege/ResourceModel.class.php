<?php
namespace Org\Privilege;
use Think\Model;
class ResourceModel extends BaseModel{

    //添加资源
    public function addResource($data){
        $data['app'] = $this->app_name;
        return $this->table("privilege_resource")->add($data);
    }
    //添加资源
    public function delResource($res_id){
        $where['app'] = $this->app_name;
        $where['pk_resource_id'] = $res_id;
        return $this->table("privilege_resource")->where($where)->delete();
    }
    //修改资源
    public function updateResource($res_id, $data){
        $where['app'] = $this->app_name;
        $where['pk_resource_id'] = $res_id;
        return $this->table("privilege_resource")->where($where)->data($data)->save();
    }
    //修改资源
    public function updateResourceByWhere($where, $data){
        $where['app'] = $this->app_name;
        return $this->table("privilege_resource")->where($where)->data($data)->save();
    }
    //获取资源
    public function getResource($where){
        if($this->is_super){
            $where['app'] = array('IN', "sys,{$this->app_name}");
        }else{
            $where['app'] = $this->app_name;
        }
        $order = 'pk_resource_id ASC';
        return $this->table("privilege_resource")->where($where)->order($order)->select();
    }
    //获取资源
    public function getResourceById($res_id){
        $where['pk_resource_id'] = $res_id;
        return $this->table("privilege_resource")->where($where)->find();
    }

    public function getModules(){
        if($this->is_super){
            $where['app'] = array('IN', "sys,{$this->app_name}");
        }else{
            $where['app'] = $this->app_name;
        }
        $where['verify_login'] = 'Y';
        $where['verify_privilege'] = 'Y';

        return $this->table("privilege_resource")->field('module')->where($where)->group('module')->select();
    }
    public function getcontrollers($module){
        if($this->is_super){
            $where['app'] = array('IN', "sys,{$this->app_name}");
        }else{
            $where['app'] = $this->app_name;
        }
        $where['module'] = $module;
        $where['verify_login'] = 'Y';
        $where['verify_privilege'] = 'Y';

        return $this->table("privilege_resource")->field('controller')->where($where)->group('controller')->select();
    }
    //获取用户权限资源ID
    public function getUserPrivRes(){
        if($this->is_super){
            //超级管理员
            $where['app'] = array('IN', "{$this->app_name},sys");
            $data = $this->table("privilege_resource")->field('pk_resource_id')->where($where)->select();
            foreach($data as $key => $val){
                $resList[$val['pk_resource_id']] = $val['pk_resource_id'];
            }
        }else{
            //用户角色列表
            $where['app'] = $this->app_name;
            $where['user_id'] = I("session.uid");
            $ret = $this->table('privilege_role_re_user')->field('role_id')->where( $where )->select();
            $userPrivRole = array();
            foreach($ret as $rid){
                $userPrivRole[] = $rid['role_id'];
            }
            //角色所拥有权限
            $where = array();
            $where['app'] = $this->app_name;
            $where['role_id'] = array('in', implode(',', $userPrivRole));
            $data = $this->table("privilege_role_re_resource")->field('resource_id')->where($where)->select(); 
            foreach($data as $key => $val){
                $resList[$val['resource_id']] = $val['resource_id'];
            }
        }
        return $resList;
    }

}
