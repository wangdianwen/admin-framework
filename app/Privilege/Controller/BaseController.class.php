<?php
namespace Privilege\Controller;
use Think\Controller;
class BaseController extends Controller {

    public $app_name = '';
    public $user_email='';
    public $is_super = false;

    public function __construct(){
        parent::__construct();

        $this->app_name = C("APP_NAME");
        $this->user_email = I("session.email");
        $this->is_super = array_key_exists($this->user_email, C("SUPER_ADMIN")) ? true : false;

        $this->assign('post', $_POST);
        $this->assign('get', $_GET);
    }
    public function echoExit($msg){
        header("Content-type:text/html;charset=utf-8");
        echo $msg;
        exit;
    }
}
