<?php
namespace Home\Model;
use Think\Model;

class UsersModel extends Model {
	protected $connection = 'XXXXXX'; // 改为Common/Conf/config.php 里面的数据配置
	/**
	 * [UserInfo 获取优拍档信息]
	 * @param [type] $ypid [description]
	 */
	public function UserInfo($ypid) {
		return $this->find($ypid);
	}
}
