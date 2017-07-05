<?php
namespace Org\Privilege;
use Think\Model;
class MenuModel extends BaseModel{
    //获取菜单
    public function getMenus($fid = 0, $finalChild = 1){
        $where['app'] = $this->app_name;
        $where['status'] = 1;
        $where['fid'] = $fid;
        if($finalChild != 1){
            $where['resource_id'] = array('EQ', 0);
        }
        $order = 'sort ASC';
        return $this->table('privilege_menu')->where( $where )->order($order)->select();
    }
    public function getSysMenus(){
        $where['app'] = 'sys';
        $where['status'] = 1;
        //系统顶级菜单
        $where['fid'] = 0;
        $topMenu = $this->table('privilege_menu')->where( $where )->find();
        //系统子菜单
        unset($where['app']);

        $child_info = $this->TopMenuChildDeal($topMenu['pk_menu_id']);
        $retMenu[$topMenu['pk_menu_id']]['name'] = $topMenu['name'];
        if(isset($child_info['child'])){
            $retMenu[$topMenu['pk_menu_id']]['child'] = $child_info['child'];
        }else if(isset($child_info['action'])){
            $retMenu[$topMenu['pk_menu_id']]['action'] = $child_info['action'];
        }
        return $retMenu;
    }
    public function getAppMenus(){
        $topMenus = $this->getTopMenus();
        foreach($topMenus as $topMenu){
            $child_info = $this->TopMenuChildDeal($topMenu['pk_menu_id']);
            $retMenu[$topMenu['pk_menu_id']]['name'] = $topMenu['name'];
            if(isset($child_info['child'])){
                $retMenu[$topMenu['pk_menu_id']]['child'] = $child_info['child'];
            }
            if(isset($child_info['action'])){
                $retMenu[$topMenu['pk_menu_id']]['action'] = $child_info['action'];
            }
        }
        return $retMenu;
    }
    //整理菜单资源树
    private function TopMenuChildDeal($top_fid){
        $model_Resource = new ResourceModel();
        $retMenu = array();
        $where['fid'] = $top_fid;
        $order = 'sort ASC';
        $childMenu = $this->table('privilege_menu')->where( $where )->order($order)->select();
        foreach($childMenu as $key=>$child){
            if($child['resource_id'] == 0){
                $where['fid'] = $child['pk_menu_id'];
                $finalMenus = $this->table('privilege_menu')->where( $where )->select();

                $retMenu['child'][$child['pk_menu_id']]['name'] = $child['name'];
                foreach($finalMenus as $final){
                    $resource_info = $model_Resource->getResourceById($final['resource_id']);
                    $retMenu['child'][$child['pk_menu_id']]['action'][$final['pk_menu_id']]['name'] = $final['name'];
                    $retMenu['child'][$child['pk_menu_id']]['action'][$final['pk_menu_id']]['resource_info'] = $resource_info;
                }
                
            }else{
                $resource_info = $model_Resource->getResourceById($child['resource_id']);
                $retMenu['action'][$child['pk_menu_id']]['name'] = $child['name'];
                $retMenu['action'][$child['pk_menu_id']]['resource_info'] = $resource_info;
            }
        }
        return $retMenu;
    
    }
    //获取顶级菜单
    public function getTopMenus(){
        $where['app'] = $this->app_name;
        $where['status'] = 1;
        $where['fid'] = 0;
        $order = 'sort ASC';
        return $this->table('privilege_menu')->where( $where )->order($order)->select();
    }
    //获取所有显示菜单
    public function getAllMenu(){
        $menus = $this->table('privilege_menu')->select();
        $retArr = array();
        foreach($menus as $menu){
            $retArr[$menu['pk_menu_id']] = $menu;
        }
        return $retArr;
    }
    
    //添加菜单
    public function addMenu($data){
        $data['app'] = $this->app_name;
        return $this->table("privilege_menu")->add($data);
    }
    //更新菜单
    public function updateMenu($menu_id, $data){
        $where['app'] = $this->app_name;
        $where['pk_menu_id'] = $menu_id;
        return $this->table("privilege_menu")->where($where)->data($data)->save();
    }
    //添加菜单
    public function delMenu($menu_id){
        $where['app'] = $this->app_name;
        $where['pk_menu_id'] = $menu_id;
        return $this->table("privilege_menu")->where($where)->delete();
    }


    //获取资源树
    public function getPrivResTree($params = array()){
        if(isset($params['role_id'])){
            //角色组
            $resIds = $this->getRoleResID($params['role_id']);
            $map['pk_resource_id'] = array("in", implode(',', $resIds));

            $resList = $this->table("privilege_resource")->where($map)->select();
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
    //获取权限菜单树
    public function getPrivMenuTree($params = array()){
        if(isset($params['role_id'])){
            //角色组
            $resIds = $this->getRoleResID($params['role_id']);
            $map['pk_resource_id'] = array("in", implode(',', $resIds));

            $resList = $this->table("privilege_resource")->where($map)->select();
        }else{
            $resList = $this->table("privilege_resource")->select();
        }
        //整理菜单树
        $menuData = array();
        foreach($resList as $res){
            if(isset($menuData[$res['menu_id']])){
                $menuData[$res['menu_id']][] = $res;
            }else{
                $menuData[$res['menu_id']][0] = $res;
            }
        }
        //var_dump($menuData);

        $allMenus = $this->getAllMenu();
        //var_dump($allMenus);
        $menuTree = array();
        foreach($menuData as $key => $menu){
            $fid = $allMenus[$key]['fid'];
            if($fid == 0){
                $menuTree[$key]['child'] = $menu;
            }else{
                $top_fid = $allMenus[$fid]['fid'];
                if($top_fid == 0){
                    $menuTree[$fid]['child'][$key]['child'] = $menu;
                }else{
                    $menuTree[$top_fid]['child'][$fid][$key] = $menu;
                }
            }
        }
        return $menuTree;
    }


}
