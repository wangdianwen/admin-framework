<?php
/**
 *
 * 只支持单文件上传,确保有正确的$_FILES数组
 */
function uploadRes2() {
	$upfile = array_values($_FILES);
	if ( count($upfile) != 1 ) {
		return false;
	}
	if ( !isset($upfile[0]['error']) || $upfile[0]['error'] != 0 ) {
		return false;
	}
	// mp3 文件 处理
	if ( $upfile[0]['type'] == 'audio/mp3' ) {
		$move_file_name = '/tmp/framework_' . $upfile[0]['name'];
		move_uploaded_file( $upfile[0]['tmp_name'], $move_file_name );
		$outfilename = $move_file_name . 'out.mp3';
		$cmd = "ffmpeg -i " . $move_file_name . " -y " . $outfilename ." 2>&1;";
		exec($cmd, $ary, $status);
		unlink($move_file_name);
		$upfile[0]['tmp_name'] = $outfilename;
	}

	// 配置
	$appsecret = '50A7F222AC6CF232D081A65B0AF87AD4057C8E7715';
	$appkey = 'uctg';
	$uid = 111792449;       // 优酷君
	$timestamp = time();
	$url = C('RESOURCE_API_HOST') . '/res2/file/upload.json';
	$post_data = array(
	        'did'       => 0,
	        'duplname'  => true,
	        'duplmd5'   => 1,
	        'typelimit' => '',
	        'maxsize'   => 0,
	        'wmoptions' => 0,
	        'filter'    => 'uctg',
	        'processor' => '',
	        'combiner'  => '',
	        'crpoptions'=> ''
	);
	$post_data['timestamp'] = $timestamp;
	$post_data['sign'] = md5($appkey.'&'.$appsecret.'&'.$timestamp);
	$post_data['appkey'] = $appkey;
	$post_data['uid'] = intval($uid);
	$post_data['err_lang'] = 'cn';
	if (version_compare(PHP_VERSION, '7.0.0', 'ge') && class_exists('CURLFile')) {
		$post_data['file'] = new \CURLFile($upfile[0]['tmp_name'], $upfile[0]['type']);
	} else {
		$post_data['file'] = "@".$upfile[0]['tmp_name'] . ';type=' . $upfile[0]['type'];
	}
	// 发送请求
	$ci = curl_init();
	curl_setopt($ci, CURLOPT_USERAGENT, 'Youku Resource Client v2');
	curl_setopt($ci, CURLOPT_CONNECTTIMEOUT,30);
	curl_setopt($ci, CURLOPT_TIMEOUT, 30);
	curl_setopt($ci, CURLOPT_AUTOREFERER, true);
	curl_setopt($ci, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ci, CURLOPT_ENCODING, '');
	curl_setopt($ci, CURLOPT_HEADER, false);
	curl_setopt($ci, CURLOPT_POST, true);
	curl_setopt($ci, CURLOPT_POSTFIELDS, $post_data);
	curl_setopt($ci, CURLOPT_URL, $url);
	$response = curl_exec($ci);
	$http_error = curl_error($ci);
	curl_close ($ci);
	if ( !empty($http_error) ) {
		return false;
	}
	$rt = json_decode($response, true);
	//var_dump($url, $post_data, $rt);exit;
	if ( isset($rt['e']['info']) ) {//重复上传文件
	        $url = json_decode( $rt['e']['info'] , true );
	        return isset($url['url'])?$url['url']:false;
	} elseif ( isset($rt['data']) ) {
	        return isset($rt['data']['url'])?$rt['data']['url']:false;
	}
	return false;
}

function curl_common($hosts, $uri, $params = array(), $method = 'GET', $port = 80) {
	if (is_string($hosts)) {
		$host = $hosts;
	} else if (is_array($hosts)) {
		$host = $hosts[array_rand($hosts)];
	} else {
		return false;
	}
	if (strpos($host, 'http://') !== 0) {
		$host = 'http://' . $host;
	}
	$host = trim($host, '/') . ':' . $port . '/' . $uri;
	$ch = curl_init();
	if (is_array($params)) {
		$params = http_build_query($params);
	}
	if ($method == 'GET') {
		$host = $host . '?' . $params;
	} else if ($method == 'POST') {
		curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
	}
	//var_dump($host);exit;
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
	curl_setopt($ch, CURLOPT_URL, $host);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch,CURLOPT_TIMEOUT,3);
	curl_setopt($ch, CURLOPT_PORT, $port);
	$ret = curl_exec($ch);
	if ($ret) {
		return json_decode($ret, true);
	}
	return false;
}

/**
 * [getYkUserInfo 获取优酷登录用户信息]
 * @param  array  $uids [description]
 * @return [type]       [description]
 */
function getYkUsersInfoByName($unames = array()) {
	if (empty($unames)) {
		return array();
	}
	$params = array();
	$params['nick_names'] = json_encode(array_values($unames));
	$host = C('ucenterAPIServers')[array_rand(C('ucenterAPIServers'))];
	return curl_common($host, '/users/batch_show', $params);
}

/**
 * [getYkUserInfo 获取优酷登录用户信息]
 * @param  array  $uids [description]
 * @return [type]       [description]
 */
function getYkUsersInfoByUids($uids = array()) {
	if (empty($uids)) {
		return array();
	}
	$params = array();
	$uids = array_map("intval", $uids);
	$params['uids'] = json_encode(array_values($uids));
	$host = C('ucenterAPIServers')[array_rand(C('ucenterAPIServers'))];
	return curl_common($host, '/users/batch_show', $params);
}

function getOperator($key) {
	switch ($key) {
		
	case 'name':
		return isset($_SESSION['name']) ? $_SESSION['name'] : 'N/A';
	case 'uid':
		return isset($_SESSION['uid']) ? $_SESSION['uid'] : '0';
	case 'email':
		return isset($_SESSION['email']) ? $_SESSION['email'] : 'N/A';
	default:
		return 'N/A';
	}
}

function encodeUid( $uid ) {
	if ( empty($uid) ) {
		return;
	}
	return 'U'. base64_encode($uid << 2);
}

function homeUrl( $uid ) {
	if ( empty($uid) ) {
		return;
	}
	return 'http://i.youku.com/u/' . encodeUid($uid);
}





