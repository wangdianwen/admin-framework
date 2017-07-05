<?php
namespace Privilege\Controller;
use Think\Controller;
class UserController extends Controller {
    var $model_Privilege;

    public function __construct(){
        parent::__construct();

        $this->model_User = new \Org\Privilege\UserModel();
        $this->model_Role = new \Org\Privilege\RoleModel();
        $this->assign('post', $_POST);
        $this->assign('get', $_GET);
    }
    //添加管理员
    public function addUser(){
        if( I('post.name') && I('post.passwd') ) {
            $data['email']  = I('post.email');
            $data['name']   = I('post.name');
            $data['phone']  = I('post.phone');
            $data['realname'] = I('post.realname');
            $data['status'] = 1;
            $data['ctime']  = date("Y-m-d H:i:s");
            $data['passwd'] = md5(I('post.passwd'));
            $user_id = $this->model_User->addUser($data);
        }
        $this->display();
    }

    public function userDel(){
        $user_id = I('get.user_id');
        //删除角色用户关联
        $this->model_Role->deleteRoleRelationUserId($user_id);
        
        $this->model_User->userDel($user_id);
        $this->redirect('/Privilege/User/userList');
    }

    public function userList(){
        $pn = I('page',1,'number_int');
        $pl = 20;
        $users = $this->model_User->getUsers('', $pn, $pl);
        $total = $this->model_User->getUsersCount();
        $page = new \Org\Privilege\Page($total, $pl);

        $this->assign('users', $users);
        $this->assign('paginate', $page->showpage());

        $this->display();
    }
    public function userEdit(){
        $user_id = I('get.user_id');
        if( I('post.name') ) {
            $data['name'] = I('post.name');
            $data['email'] = I('post.email');
            $data['phone'] = I('post.phone');
            $data['realname'] = I('post.realname');
            $this->model_User->updateUser($user_id, $data);
        }
        $user_info = $this->model_User->getUserInfoById($user_id);
        $this->assign('user_info', $user_info);
        $this->display("addUser");
    }

    public function login() {
        if ( I('post.name') ) {
            $name = I('post.name');
            $passwd = I('post.passwd');
            $passwd = md5($passwd);
            $is_login = $this->model_User->checkLogin($name, $passwd);
            if ( $is_login ) {
                $data = array("name" => $name, "passwd" => $passwd);
                $secret = $this->model_User->encode(json_encode($data));
                session("secret", $secret);
                header("location:/");
            }
        }
        $this->display();
    }
}
