<?php
namespace Privilege\Controller;
use Think\Controller;
class MenuController extends BaseController {
    public function __construct(){
        parent::__construct();

        $this->model_Menu = new \Org\Privilege\MenuModel();
        $this->model_Resource = new \Org\Privilege\ResourceModel();
        $this->assign('post', $_POST);
        $this->assign('get', $_GET);
    }
    public function menuList(){
        $menus = array();
        if(I('post.fid') != ''){
            $menus = $this->model_Menu->getMenus(I('post.fid'));
            $this->assign('menus', $menus);
            echo $this->fetch("Menu/ajaxMenuList");
            die();
        }
        if(I("post.ajaxAct") && I("post.ajaxAct") == 'updateSort'){
            $menu_id = I("post.menu_id");
            $menu_data['sort'] = I("post.sort");
            $res = $this->model_Menu->updateMenu($menu_id, $menu_data);
            die();
        }
        $topMenus = $this->model_Menu->getTopMenus();
        $this->assign('topMenus', $topMenus);
        $this->assign('menus', $topMenus);

        $this->display();
    }
    //添加菜单
    public function addMenu(){
        if(I('post.ajaxAct') && I('post.ajaxAct') == 'getChildMenu'){
            //获取子菜单
            $fid = I('post.fid');
            $finalChild = I('post.finalChild');
            $menus = $this->model_Menu->getMenus($fid, $finalChild);
            echo json_encode($menus);
            die();

        }
        if(I('post.ajaxAct') && I('post.ajaxAct') == 'getController'){
            //获取module下的controller
            $module = I('post.module');
            $contros = $this->model_Resource->getcontrollers($module);
            echo json_encode($contros);
            die();
        }
        if(I('post.name')){
            $data['name'] = I('post.name');
            $data['fid'] = I('post.fid');
            $data['resource_id'] = I('post.resource_id');
            $data['status'] = I('post.status');

            $this->model_Menu->addMenu($data);
        }
        $modules = $this->model_Resource->getModules();
        $this->assign('modules', $modules);
        //顶级菜单
        $topMenus = $this->model_Menu->getTopMenus();
        $this->assign('topMenus', $topMenus);

        $this->display();
    }
    public function MenuDel(){
        $menu_id = I('get.menu_id');
        
        $this->model_Menu->delMenu($menu_id);
        $this->redirect('Privilege/Menu/menuList');
    }
    
}
