<?php
namespace Privilege\Controller;
use Think\Controller;
class ResourceController extends Controller {
    var $model_Privilege;

    public function __construct(){
        parent::__construct();

        $this->model_Resource = new \Org\Privilege\ResourceModel();
        $this->model_Menu = new \Org\Privilege\MenuModel();
        $this->assign('post', $_POST);
        $this->assign('get', $_GET);
    }
    //添加资源
    public function addRes(){
        if(I('post.module')){
            $data = $this->handleData(); 
            $this->model_Resource->addResource($data);
        }
        $topMenus = $this->model_Menu->getTopMenus();
        $this->assign('topMenus', $topMenus);

        $this->display();
    }
    //修改资源
    public function resEdit(){
        if(I('post.module')){
            $res_id = I('get.res_id');
            
            $data = $this->handleData(); 
            $this->model_Resource->updateResource($res_id, $data);
        }
        $res_id = I('get.res_id');
        $res_info = $this->model_Resource->getResourceById($res_id); 
        $this->assign('res_info', $res_info);

        $topMenus = $this->model_Menu->getMenus();
        $this->assign('topMenus', $topMenus);
        $this->display("Resource/addRes");
    }
    private function handleData(){
            $data['module'] = I('post.module');
            $data['controller'] = I('post.controller');
            $data['action'] = I('post.action');
            $data['title'] = I('post.title');
            $data['location_menu_id'] = I('post.fid');
            $data['verify_login'] = I('post.verify_login');
            $data['verify_privilege'] = I('post.verify_privilege');

            return $data;
    }
    //删除资源
    public function resDel(){
        $res_id = I('get.res_id');
        //删除角色资源关联数据
        $this->model_Resource->deletePrivilege($res_id);

        $this->model_Resource->delResource($res_id);
        $this->redirect('privilege/resList');
    }

    //资源列表
    public function resList(){
        if(I('post.ajaxAct') && I('post.ajaxAct') == 'getController'){
            //获取module下的controller
            $module = I('post.module');
            $contros = $this->model_Resource->getcontrollers($module);
            echo json_encode($contros);
            die();
        }
        if(I('post.module')){
            $where = array();
            $where['module'] =  I('post.module');
            $where['controller'] =  I('post.controller');
            $resList = $this->model_Resource->getResource($where);

            $this->assign('resList', $resList);
            $this->assign('listType', I('post.listType'));
            echo $this->fetch("Resource/ajaxResList");
            die;
        }
        $modules = $this->model_Resource->getModules();

        $this->assign('modules', $modules);
        $this->display();
    }
}
