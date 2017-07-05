<?php
namespace Org\Privilege;
use Think\Model;
class UserModel extends BaseModel{

    //添加用户
    public function addUser($data){
        $data['app'] = $this->app_name;
        return $this->table("privilege_user")->add($data);
    }
    //更新用户信息
    public function updateUser($user_id, $data){
        $where['app'] = $this->app_name;
        $where['pk_user_id'] =  $user_id;
        return $this->table("privilege_user")->where($where)->data($data)->save();
    }
    //删除用户
    public function userDel($user_id){
        $where['app'] = $this->app_name;
        $where['pk_user_id'] = $user_id;
        return $this->table("privilege_user")->where($where)->delete();
    }
    //获取用户列表
    public function getUsers($where,$pn=1,$pl=10){
        $where['app'] = $this->app_name; 
        $order = 'pk_user_id DESC';
        return $this->table("privilege_user")->where($where)->limit(($pn-1)*$pl.','.$pl)->order($order)->select();
    }
    //获取用户列表
    public function getUsersCount($where){
        $where['app'] = $this->app_name; 
        return $this->table("privilege_user")->where($where)->count();
    }
    //通过email获取用户信息
    public function getUserInfoByEmail($email){
        $where['app'] = $this->app_name;
        $where['email'] = $email;
        return $this->table('privilege_user')->where( $where )->find();
    }
    //通过email获取用户信息
    public function getUserInfoById($user_id){
        $where['app'] = $this->app_name;
        $where['pk_user_id'] = $user_id;
        return $this->table('privilege_user')->where( $where )->find();
    }

    public function checkLogin($name , $pass): bool {
        if ( empty($name) || empty($pass) ) {
            return false;
        }
        $where['app'] = $this->app_name;
        $where['name'] = $name;
        $where['pass'] = pass;
        $ret = $this->table('privilege_user')->where( $where )->find();
        if ( empty($ret) ) {
            return false;
        }
        return true;
    }

}
